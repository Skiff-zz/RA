<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Seed extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_seed';
	protected $model_group_name = 'glossary_culture';
	protected $ignore_addons = false;

	public function action_move(){

		$target = arr::get($_POST, 'target', '');
		$target = mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='s' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
			$seed_model = Jelly::select('glossary_seed', (int)$id);

			if(!($seed_model instanceof Jelly_Model) or !$seed_model->loaded())	{
				$this->request->response = JSON::error(__("Seed is not specified"));
				return;
			}

			$seed_model->group = $target;
			$seed_model->save();
		}

		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}


	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' || mb_substr($del_ids[$i], 0, 1)=='s'  ? mb_substr($del_ids[$i], 1) : $del_ids[$i];
			$seed_model = Jelly::select('glossary_seed', (int)$id);

			if(!($seed_model instanceof Jelly_Model) or !$seed_model->loaded())	{
				$this->request->response = JSON::error(__("Farm is not specified"));
				return;
			}

			$seed_model->delete();
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
  }


	public function inner_edit(&$view){
		setlocale(LC_NUMERIC, 'C');
		
		
		if(isset($view->model['_id'])){
			
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
		}
		$view->model_fields = array();

        $view->units = array_merge(Jelly::factory('glossary_units')->getUnits('amount'), Jelly::factory('glossary_units')->getUnits('amount_seed'));
		$view->crop_norm_units = Jelly::factory('glossary_units')->getUnits('seed_crop_norm');
	}
    
    public function action_update(){
        $_POST['bio_crop'] = (float)$_POST['bio_crop'];
        parent::action_update();
    }

	public function inner_update($seed_id)
	{
		$seed = Jelly::select('glossary_seed')->load($seed_id);
		$producer_ids = arr::get($_POST, 'producer_string', '');
		if($producer_ids){
			$producer_ids_arr = explode(',', $producer_ids);
			foreach($producer_ids_arr as &$id){
				$id = (int)$id;
			}

			$seed->producer = $producer_ids_arr;
		} else {
			$seed->producer = array();
		}


		if($seed instanceof Jelly_Model && $seed->loaded()){
			$seed->crop_norm_min = (float)arr::get($_POST, 'crop_norm_hiddenValue_1', '0.00');
			$seed->crop_norm_mid = (float)arr::get($_POST, 'crop_norm_hiddenValue_3', '0.00');
			$seed->crop_norm_max = (float)arr::get($_POST, 'crop_norm_hiddenValue_2', '0.00');
			$seed->save();
		}
	}
    
    public function action_tree(){
        
        $extras = Arr::get($_GET, 'with_extras', false);
        
		$data =	Jelly::factory('glossary_seed')->get_tree('delete_this_shit', $this->group_field, array(), $extras);
		$this->request->response = Json::arr($data, count($data));
	}

}
