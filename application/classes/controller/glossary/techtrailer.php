<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_TechTrailer extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_techtrailer';
	protected $model_group_name = 'glossary_techtrailergroup';

	public function inner_edit(&$view){
		$view->floating = Arr::get($_GET, 'from_floating', false);
		if($view->model && isset($view->model['_id'])){
			$producer = array();
			foreach($view->model['producer'] as $raw_producer){
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
                $producer[] = array(
                    '_id'=>$raw_producer->id(),
                    'name'=>$raw_producer->name,
                    'first_photo'=>$first_photo,
                    'countryname'=>$raw_producer->country->name,
                    'countrycode'=>$raw_producer->country->countrycode,
                );
			}
            
			$view->model['producer'] = json_encode($producer);



			$view->model['grasp_units'] = array('_id'=>$view->model['grasp_units']->id(), 'name' => $view->model['grasp_units']->name);
			$gsm = array();
			foreach($view->model['gsm'] as $raw_gsm){
				$gsm['_id'][] = $raw_gsm->id();
				$gsm['name'][] = $raw_gsm->name;
			}
			$gsm['_id'] = isset($gsm['_id']) ? implode(',', $gsm['_id']) : '';
			$gsm['name'] = isset($gsm['name']) ? implode(', ', $gsm['name']) : '';
			$view->model['gsm'] = $gsm;
		}

		$view->grasp_units = Jelly::factory('glossary_units')->getUnits('tech_grasp');
		$view->productivity_units = Jelly::factory('glossary_units')->getUnits('tech_productivity');
		$view->fuel_work_units = Jelly::factory('glossary_units')->getUnits('tech_fuel_work');
		$view->fuel_idle_units = Jelly::factory('glossary_units')->getUnits('tech_fuel_idle');

		$view->model_fields = array();
	}

	public function action_data($item_id){
		$data = Jelly::select('glossary_techtrailer')->with('group')->where(':primary_key', '=', (int)$item_id)->load();
		$data = $data->as_array();

		$data['fuel_idle_units'] = $data['fuel_idle_units']->as_array();
		$data['fuel_work_units'] = $data['fuel_work_units']->as_array();
		$data['grasp_units'] = $data['grasp_units']->as_array();
		$data['group'] = $data['group']->as_array();
		$gsm_arr = array();
		foreach($data['gsm'] as $gsm){
			$gsm_arr[] = $gsm->as_array();
		}
		$data['gsm'] = $gsm_arr;
		$data['productivity_units'] = $data['productivity_units']->as_array();

		$this->request->response = JSON::reply($data);
	}

	public function inner_update($contragent_id){
		$gsm_ids = arr::get($_POST, 'gsm_string', '');
		if($gsm_ids){
			$gsm_ids_arr = explode(',', $gsm_ids);
			foreach($gsm_ids_arr as &$id){
				$id = (int)$id;
			}
		}

		$contragent = Jelly::select('glossary_techtrailer', (int)$contragent_id);

		$producer_ids = arr::get($_POST, 'producer_string', '');
		if($producer_ids){
			$producer_ids_arr = explode(',', $producer_ids);
			foreach($producer_ids_arr as &$id){
				$id = (int)$id;
			}

			$contragent->producer = $producer_ids_arr;
		} else {
			$contragent->producer = array();
		}

		if(!($contragent instanceof Jelly_Model) or !$contragent->loaded())
				throw new Kohana_Exception('Record Not Found!');

		if($gsm_ids){
			$contragent->gsm = $gsm_ids_arr;
		}else{
			$contragent->gsm = array();
		}

		$contragent->save();
	}

    public function action_tree(){
        $extras = Arr::get($_GET, 'with_extras', false);

		$data =	Jelly::factory($this->model_name)->get_tree('delete_this_shit', $this->group_field);

        if($extras) foreach($data as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras('glossary_techtrailer', substr($record['id'], 1));

		$this->request->response = Json::arr($data, count($data));
	}
}