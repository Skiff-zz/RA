<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Target_Szr extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_szr_target';
	protected $model_group_name = 'glossary_szrgroup';
    
    public function inner_edit(&$view){
		if(!isset($view->model["_id"])){return;}
		$targetsrzs_with_deleted = Jelly::select('szr2cult_target')->
				where('glossary_szr_target_id', '=', $view->model['_id'])->
				execute()->
                as_array();
        
        $culture_ids = array();
        foreach($targetsrzs_with_deleted as $itm){
            $culture_ids[] = $itm['culture'];
        }
        
        $szr_2_cultures = Jelly::select('Glossary_Szr_SzrCulture')->
				with('szr')->
				with('szr_units')->
				with('producer')->
				with('form')->
				where('_id', 'IN', $culture_ids)->
				where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
				execute()->
                as_array();
        
		$view->model['targetszrs'] = array();
		
        $szr_ids_used = array();
		for($i=0;$i<count($szr_2_cultures);$i++){
			$deleted = $szr_2_cultures[$i][':szr:deleted'];
            $used = in_array((int)$szr_2_cultures[$i]['szr'], $szr_ids_used);
			if(!$deleted && !$used){
                $szr_ids_used[] = (int)$szr_2_cultures[$i]['szr'];
				$szr_2_cultures[$i]['form'] = array();
				$szr_2_cultures[$i]['producer'] = array();

				$szr = Jelly::select('glossary_szr', (int)$szr_2_cultures[$i]['szr'])->as_array();
                if(!$szr['_id'])continue;// бывает что запись удалена физически а не через deleted = 1
                
				for($k=0;$k<count($szr['form']);$k++){
					$szr_2_cultures[$i]['form'][] = $szr['form'][$k]->name;
				}
                
                $szr_2_cultures[$i]['form'] = implode($szr_2_cultures[$i]['form'],', ');

				for($k=0;$k<count($szr['producer']);$k++){
                    $raw_producer = $szr['producer'][$k];
                
                    // --- logo aka first_photo
                    $id = (int)$raw_producer->id();
                    $subdir = floor($id / 2000);
                    $first_photo = null;

                    if(is_dir(DOCROOT.Kohana::config('upload.path').'/client_producer/'.$subdir))
                    {
                        $files = scandir(DOCROOT.Kohana::config('upload.path').'/client_producer/'.$subdir);
                        $file = $files[2];
                        if(is_file(DOCROOT.Kohana::config('upload.path').'/client_producer/'.$subdir.'/'.$file) && ( !(strpos($file, 'item_'.$id.'_')===FALSE) || !(strpos($file, 'item_'.$id.'.')===FALSE)       )   ){
                            $first_photo = Kohana::config('upload.path').'/client_producer/'.$subdir.'/'.$file;
                        }
                    }
                    //---
                    $szr_2_cultures[$i]['producer'][] = array(
                        '_id'=>$raw_producer->id(),
                        'name'=>$raw_producer->name,
                        'first_photo'=>$first_photo,
                        'countryname'=>$raw_producer->country->name,
                        'countrycode'=>$raw_producer->country->countrycode,
                    );
                }

				$szr_2_cultures[$i]['producer'] = json_encode($szr_2_cultures[$i]['producer']);
                
                 // полный список дв у этого сзр.
                $dvsrzs_other = Jelly::select('glossary_szr_szrdv')->
                        with('szr')->
                        with('units')->
                        with('producer')->
                        with('form')->
                        with('dv')->
                        where('szr', '=', $szr_2_cultures[$i]['szr'])->
                        where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
                        execute()->
                        as_array();

                $wide_namings = array();
                foreach($dvsrzs_other as $dvszr){
                    $wide_namings[] = $dvszr[':dv:name'].' '.$dvszr['value'].' '.$dvszr[':units:name'];
                }

                $szr_2_cultures[$i]['widedvs'] = implode($wide_namings,',\n');
                
                
                
                
				$view->model['targetszrs'][] = $szr_2_cultures[$i];
			}
		}
	}
}
