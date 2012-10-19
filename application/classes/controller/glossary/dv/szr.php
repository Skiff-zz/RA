<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Dv_Szr extends Controller_Glossary_Abstract
{
	protected $model_name = 'glossary_szr_dv';
	protected $model_group_name = 'glossary_szrgroup';

	public function inner_edit(&$view){
		if(!isset($view->model["_id"])){return;}
		$dvsrzs_with_deleted = Jelly::select('glossary_szr_szrdv')->
				with('szr')->
				with('units')->
				with('producer')->
				with('form')->
				where('dv', '=', $view->model['_id'])->
				where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
				execute()->as_array();

		$view->model['dvszrs'] = array();
		
		for($i=0;$i<count($dvsrzs_with_deleted);$i++){
			$deleted = $dvsrzs_with_deleted[$i][':szr:deleted'];
			if(!$deleted){
				$dvsrzs_with_deleted[$i]['form'] = array();
				$dvsrzs_with_deleted[$i]['producer'] = array();

				$szr = Jelly::select('glossary_szr', (int)$dvsrzs_with_deleted[$i]['szr'])->as_array();
                if(!$szr['_id'])continue;// бывает что запись удалена физически а не через deleted = 1
                
				for($k=0;$k<count($szr['form']);$k++){
					$dvsrzs_with_deleted[$i]['form'][] = $szr['form'][$k]->name;
				}

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
                    $dvsrzs_with_deleted[$i]['producer'][] = array(
                        '_id'=>$raw_producer->id(),
                        'name'=>$raw_producer->name,
                        'first_photo'=>$first_photo,
                        'countryname'=>$raw_producer->country->name,
                        'countrycode'=>$raw_producer->country->countrycode,
                    );
                }
                
                
                // полный список дв у этого сзр.
                $dvsrzs_other = Jelly::select('glossary_szr_szrdv')->
                        with('szr')->
                        with('units')->
                        with('producer')->
                        with('form')->
                        with('dv')->
                        where('szr', '=', $dvsrzs_with_deleted[$i][':szr:_id'])->
                        where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
                        execute()->
                        as_array();

                $wide_namings = array();
                foreach($dvsrzs_other as $dvszr){
                    $wide_namings[] = $dvszr[':dv:name'].' '.$dvszr['value'].' '.$dvszr[':units:name'];
                }

                $dvsrzs_with_deleted[$i]['widedvs'] = implode($wide_namings,',\n');
                
                
                

				$dvsrzs_with_deleted[$i]['form'] = implode($dvsrzs_with_deleted[$i]['form'],', ');
				$dvsrzs_with_deleted[$i]['producer'] = json_encode($dvsrzs_with_deleted[$i]['producer']);;

				$view->model['dvszrs'][] = $dvsrzs_with_deleted[$i];
			}
		}
        
	}
}
