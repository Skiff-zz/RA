<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Production extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'glossary_productionclass';
	protected $model_group_name = 'glossary_production';


	public function action_edit($id = null, $read = false, $parent_id = false){

        $production = null;

        if($id){
            $production = Jelly::select('glossary_production')->with('cultures')->load((int)$id);
            if(!($production instanceof Jelly_Model) or !$production->loaded()){
                $this->request->response = JSON::error('Не найдена Запись');
				return;
			}
        }

		$view = Twig::factory('glossary/production/read_production');

		if($id)
			$view->id = $id;

		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($production ? $production->parent->id() : 0);
			$view->hasChildren = false;
		}

		$view->units = Jelly::factory('glossary_units')->getUnits('amount');

        if($production){
			$culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();

			$view->model 	 = $production->as_array();
//			$view->model['license'] 	 = $production->license->id();
			$view->model['parent'] 	 = $production->parent->id();
			$view->model['cultures'] 	 = $production->cultures->as_array();
			$view->model['items'] 	 = $production->items->as_array();
			$view->model['units'] 	 = array('_id' => $production->units->id(), 'name' => $production->units->name);

			for($i=0; $i<count($view->model['cultures']); $i++){
				foreach($culture_types as $culture_type){
					if($culture_type['_id']==$view->model['cultures'][$i]['type']) $view->model['cultures'][$i]['type'] = array('_id' => $culture_type['_id'], 'name' => $culture_type['name']);
				}
				//if(count($culture_types) && $view->model['cultures'][$i]['type']['_id']!=$culture_types[0]['_id']) $view->model['cultures'][$i]['name'] .= ' '.$view->model['cultures'][$i]['type']['name'];
			}
            $this->action_getphoto($view, $production->id());
        }else{
			$view->model	=	array();
			$view->model['cultures'] 	 = array();
			$view->model['units']  = array('_id' => 0, 'name' => '(не задано)');
		}

		//print_r($view->model); exit;
		$this->request->response = JSON::reply($view->render());
	}


	public function inner_update($production_id){

		$old_cultures= array();
		$new_cultures= array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'insert_culture_') !== false){
				$new_cultures[] = arr::get($_POST,$key,'');
			}else if(UTF8::strpos($key, 'culture_') !== false){
				$old_cultures[] = arr::get($_POST,$key,'');
			}
		}


		if(count($old_cultures)){
			Jelly::delete('glossary_production_production2cultures')->where('culture', 'NOT IN', $old_cultures)->and_where('production', '=', $production_id)->execute();
		}else{
			Jelly::delete('glossary_production_production2cultures')->where('production', '=', $production_id)->execute();
		}

		foreach ($new_cultures as $culture) {
			$relation = Jelly::factory('glossary_production_production2cultures');
			$relation->production = $production_id;
			$relation->culture      = $culture;
			$relation->save();
		}

	}


	public function validate_data($data){
		$cultures= array();
		foreach($data as $key => $value){
			if(UTF8::strpos($key, 'culture_') !== false){
				$cultures[] = arr::get($_POST,$key,'');
			}
		}

		if(!count($cultures)){
			throw new Kohana_Exception('Необходимо выбрать культуры');
		}
	}
    
    
    public function action_tree(){
	
		$user = Auth::instance()->get_user();
	
		$exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
			//  $exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
				$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g'										? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		$both_trees = Arr::get($_GET, 'both_trees', false);
        $with_cultures = Arr::get($_GET, 'with_cultures', false);

		$data =	Jelly::factory('glossary_production')->get_tree('delete_this_shit', $both_trees, $exclude, $this->items_field);
        
        if($with_cultures){
            Jelly::factory('glossary_production')->update_tree_with_cultures($data);
        }
		
		$this->request->response = Json::arr($data, count($data));
	}
}

