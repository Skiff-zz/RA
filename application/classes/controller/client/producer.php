<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Producer extends Controller_Glossary_Abstract
{
	protected $model_name = 'client_producer';
	protected $model_group_name = 'client_producergroup';
	
	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_producer')->with('group')->with('country')->load((int)$id);

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/producer/read_producer');

        if($model)
        {
            $view->model  			               = $model->as_array();
            $view->model['group']                = $model->group->id();
            $view->group_field                    = 'group';
            $this->action_getphoto($view, $model->id());
        }

        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id)
            {
                    $view->model                           = array();
                    $view->model['group']                  = $parent_id;
                    $view->group_field                     = 'group';
					
					$parent = Jelly::select('client_producergroup', (int)$parent_id);
					$view->parent_color                     = $parent->color;
            }
		}
        if(!$view->model){
            $view->model = array();
        }
        if($model){
			$view->model['properties']  = $model->get_properties();
        }
        else
        {
       		$view->model['properties']  = Jelly::factory($this->model_name)->get_properties();
   		}
        

        $fields  = Jelly::meta($this->model_name)->fields();
		$addon_fields = array();
//		$field_types = array('country' => 'textfield');
        foreach($fields as $field)
        {
			if(isset($field_types[$field->name]))
			$addon_fields[] = array(
				'xtype' => $field_types[$field->name],
				'name'  => $field->name,
				'label' => $field->label,
				'value' => (isset($model) and $model instanceof Jelly_Model) ? $model->get($field->name) : null
			);
        }
		
        $view->model_fields = $addon_fields;
		$view->square_names = true;
        
        if($model){
            $seed_links = Jelly::select('seed2prdcr')->
                    where('producer_id','=',$model->id())->
                    execute();
            $seed_ids = array();
            foreach($seed_links as $seed_link){
                $seed_ids[] = (int)$seed_link['seed_id'];
            }
            if(count($seed_ids)==0){$seed = array();}else{
                $seed = Jelly::select('glossary_seed')->
                    with('group')->
                    where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                    where('_id','IN',$seed_ids)->
                    execute()->as_array();
            }
            
            
            // ----------------
            
            
            $szr_links = Jelly::select('szr2producer')->
                    where('producer_id','=',$model->id())->
                    execute();
            $szr_ids = array();
            foreach($szr_links as $szr_link){
                $szr_ids[] = (int)$szr_link['szr_id'];
            }
            if(count($szr_ids)==0){$szr = array();}else{
                $szr = Jelly::select('glossary_szr')->
                        with('group')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('_id','IN',$szr_ids)->
                        execute()->as_array();
            }
            
            // ----------------
            
            $fertilizer_links = Jelly::select('fert2producer')->
                    where('producer_id','=',$model->id())->
                    execute();
            $fertilizer_ids = array();
            foreach($fertilizer_links as $fertilizer_link){
                $fertilizer_ids[] = (int)$fertilizer_link['fert_id'];
            }
            if(count($fertilizer_ids)==0){$fertilizer = array();}else{
                $fertilizer = Jelly::select('glossary_fertilizer')->
                        with('group')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('_id','IN',$fertilizer_ids)->
                        execute()->as_array();
            }
            
            // ----------------
            
            $tm_links = Jelly::select('tm2producer')->
                    where('producer_id','=',$model->id())->
                    execute();
            $tm_ids = array();
            foreach($tm_links as $tm_link){
                $tm_ids[] = (int)$tm_link['tmob_id'];
            }
            if(count($tm_ids)==0){$tm = array();}else{
                $tm = Jelly::select('glossary_techmobile')->
                        with('group')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('_id','IN',$tm_ids)->
                        execute()->as_array();
            }
            // ----------------
            
            $tr_links = Jelly::select('tr2producer')->
                    where('producer_id','=',$model->id())->
                    execute();
            $tr_ids = array();
            foreach($tr_links as $tr_link){
                $tr_ids[] = (int)$tr_link['ttrail_id'];
            }
            if(count($tr_ids)==0){$tr = array();}else{
                $tr = Jelly::select('glossary_techtrailer')->
                        with('group')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('_id','IN',$tr_ids)->
                        execute()->as_array();
            }
            
            foreach($seed as &$s){$s['label']='Семена';}
            foreach($szr as &$s){$s['label']='СЗР';}
            foreach($fertilizer as &$s){$s['label']='Удобрения';}
            foreach($tm as &$s){$s['label']='Техника.Подвижной состав';}
            foreach($tr as &$s){$s['label']='Техника.Прицепной состав';}
            
            $produced = array_merge(
                $seed,
                $szr,
                $fertilizer,
                $tm,
                $tr
            );
//            print_r($produced[1]);exit;
            $view->produced = $produced;
            
        }

		$this->request->response = JSON::reply($view->render());
	}
	
	public function action_update(){


        if($id = arr::get($_POST, '_id', NULL)){
			
			$model = Jelly::select($this->model_name, (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
			
		}else{
			$model = Jelly::factory($this->model_name);
		}

		$model->update_date = time();
		
		if(!$id)
		{
			$check = Jelly::select($this->model_name)->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('group', '=', (int)(arr::get($_POST, 'group', 0))  )->
					where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->load();
        }
        else
        {
       		$check = Jelly::select($this->model_name)->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('group', '=', (int)(arr::get($_POST, 'group', 0))  )->
					where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->where(':primary_key', '!=', (int)$id)->load();
   		}
        
    	if(($check instanceof Jelly_Model) and $check->loaded())
        {
            $this->request->response = JSON::error('Уже есть такая запись в этой группе !'); 
            return;
        }

		$this->validate_data($_POST);
		
		$model->set($_POST);
		
		$model->deleted = 0;
		           
		$model->save();
        $this->action_savephoto($model->id());
        
        $this->inner_update($model->id());
        
        // Допполя
        if(!$this->ignore_addons){
			$add = array();

			/*
			insert_property_
			name_insert
			*/

			// Удаляем старые
			$properties = $model->get_properties();

			foreach($properties as $property_id => $property)
			{
				if(!array_key_exists('property_'.$property_id, $_POST))
				{
					$model->delete_property($property_id);
				}
			}

			//Новые допполя
			foreach($_POST as $key => $value)
			{
				if(UTF8::strpos($key, 'insert_property_') !== false)
				{
					$property_id = (int)UTF8::str_ireplace('insert_property_', '', $key);

					$add[$_POST['name_insert_'.$property_id]] = $_POST['insert_property_'.$property_id];
				}
			}

			foreach($add as $key => $value)
			{
				$model->set_property(0, $key, $value);
			}

			// Старые допполя

			foreach($_POST as $key => $value)
			{
				if(UTF8::strpos($key, 'property_') !== false)
				{
					$id = (int)UTF8::str_ireplace('property_', '', $key);

					if(array_key_exists('property_'.$id.'_label', $_POST))
					{
						  $model->set_property($id, $_POST['property_'.$id.'_label'], $_POST['property_'.$id]);
					}
				}
			}
		}
        
		$culture_id = $model->id();

		$this->request->response = JSON::success(array('script'	   => 'Запись сохранена успешно!',
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $culture_id));
	}
}