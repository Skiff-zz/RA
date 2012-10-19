<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_ProductionClass extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_productionclass';
	protected $model_group_name = 'glossary_production';


	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('glossary_productionclass')->with('group')->where(':primary_key', '=', (int)$id)->load();
            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!'); return;
			}
        }

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id || $model->group){
			$parent_id = (int)$parent_id ? (int)$parent_id : $model->group->id();
            $production = Jelly::select('glossary_production')->with('cultures')->where(':primary_key', '=', $parent_id)->load();
            if(!($production instanceof Jelly_Model) or !$production->loaded()){
                $this->request->response = JSON::error('Группа не найдена!'); return;
            }
        }

		$view = Twig::factory('glossary/production/read_productionclass');
		$view->model = array();

       if($model)
        {
            $view->model  			               = $model->as_array();

			$view->model['cultures']             = $model->group->cultures->as_array();
			$view->model['shown_cultures_ids']   = $model->shown_cultures_ids;
            $view->model['group']                = $model->get($this->group_field)->id();
            $view->group_field                    = $this->group_field;

//			$view->model['license']			      = $model->license->id();
			$view->model['seeds']			      = $model->seeds->as_array();
            $this->action_getphoto($view, $model->id());
        }else{
			$view->model['cultures']             = $production->cultures->as_array();
			$view->model['shown_cultures_ids']   = array();
			for($i=0;$i<count($view->model['cultures']);$i++){
				$view->model['shown_cultures_ids'][] = $view->model['cultures'][$i]['_id'] ? $view->model['cultures'][$i]['_id'] : $view->model['cultures'][$i]['id'];
			}
			$view->model['shown_cultures_ids'] = implode(',', $view->model['shown_cultures_ids']);
		}

		$culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();
		for($i=0; $i<count($view->model['cultures']); $i++){
			foreach($culture_types as $culture_type){
				if($culture_type['_id']==$view->model['cultures'][$i]['type']) $view->model['cultures'][$i]['type'] = array('_id' => $culture_type['_id'], 'name' => $culture_type['name']);
			}
			//if(count($culture_types) && $view->model['cultures'][$i]['type']['_id']!=$culture_types[0]['_id']) $view->model['cultures'][$i]['name'] .= ' '.$view->model['cultures'][$i]['type']['name'];
		}

        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id){
                    $view->model['group'] = $parent_id;
                    $view->group_field     = $this->group_field;
					$view->parent_color                     = $production->color;
            }
		}

		$cultures = $production->cultures->as_array();
		$seeds = array();
		foreach ($cultures as $culture) {
			$s = Jelly::select('glossary_seed')->where('deleted', '=', false)->and_where('group', '=', $culture['_id'])->order_by('name', 'asc')->execute()->as_array();
			$seeds = array_merge($seeds, $s);
		}
		$view->seeds =  $seeds;
	
		$this->request->response = JSON::reply($view->render());
	}


	public function inner_update($productionclass_id){
		
		$productionclass = Jelly::select('glossary_productionclass',$productionclass_id);

		$old_cultures = array();
		$new_cultures = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'insert_culture_') !== false){
				$new_cultures[] = arr::get($_POST,$key,'');
			}else if(UTF8::strpos($key, 'culture_') !== false){
				$old_cultures[] = arr::get($_POST,$key,'');
			}
		}


		if(count($old_cultures)){
			Jelly::delete('glossary_production_production2cultures')->where('culture', 'NOT IN', $old_cultures)->and_where('production', '=', $productionclass->group->id())->execute();
		}else{
			Jelly::delete('glossary_production_production2cultures')->where('production', '=', $productionclass->group_id)->execute();
		}

		foreach ($new_cultures as $culture) {
			$relation = Jelly::factory('glossary_production_production2cultures');
			$relation->production = $productionclass->group->id();
			$relation->culture      = $culture;
			$relation->save();
		}

	}
    
    
    public function action_tree(){
        
        $with_cultures = Arr::get($_GET, 'with_cultures', false);
        
		$data =	Jelly::factory('glossary_productionclass')->get_tree('delete_this_shit', $this->group_field);
        
        if($with_cultures){
            Jelly::factory('glossary_production')->update_tree_with_cultures($data);
        }
        
		$this->request->response = Json::arr($data, count($data));
	}

}
