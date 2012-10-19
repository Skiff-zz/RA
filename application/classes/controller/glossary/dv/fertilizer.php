<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Dv_Fertilizer extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_fertilizer_dv';
	protected $model_group_name = 'glossary_fertilizergroup';

	public function inner_edit(&$view){
		if(!isset($view->model["_id"])){return;}

		$dvsrzs_with_deleted = Jelly::select('glossary_fertilizer_fertilizerdv')->
				with('fertilizer')->
				with('units')->
//				distinct('TRUE')->
				with('producer')->
				where('dv', '=', $view->model['_id'])->
				where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
//				where_open()->where('fertilizer:deleted','=',0)->or_where('fertilizer:deleted','IS',null)->where_close()->
				execute()->as_array();

		$view->model['dvfertilizers'] = array();
		for($i=0;$i<count($dvsrzs_with_deleted);$i++){
			$deleted = $dvsrzs_with_deleted[$i][':fertilizer:deleted'];
			if(!$deleted){
				$fertilizer = Jelly::select('glossary_fertilizer', (int)$dvsrzs_with_deleted[$i][':fertilizer:_id'])->as_array();
				
				$form_title = array();
				foreach($fertilizer['form'] as $form){
					$form_title[] = $form->name;
				}
				$form_title = implode(', ', $form_title);
                $dvsrzs_with_deleted[$i]['form'] = $form_title;
                

				for($k=0;$k<count($fertilizer['producer']);$k++){
                    $raw_producer = $fertilizer['producer'][$k];
                
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
                
                $dvsrzs_with_deleted[$i]['producer'] = json_encode($dvsrzs_with_deleted[$i]['producer']);
                
				$view->model['dvfertilizers'][] = $dvsrzs_with_deleted[$i];
			}
		}

	}
}
