<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Handbook_TechniqueTrailer extends Controller_Glossary_Abstract
{
	protected $model_name = 'client_handbook_techniquetrailer';
	protected $model_group_name = 'client_handbook_techniquetrailergroup';


	public function action_addnomenclature(){
		$model = Arr::get($_POST, 'model', false);
		$model_to = Arr::get($_POST, 'model_to', false);
		$model_ids = Arr::get($_POST, 'ids', '');
		$model_ids = explode(',', $model_ids);
		$farm_id = Arr::get($_POST, 'farm_id', false);

		if(isset($model_ids[0]) && !trim($model_ids[0])) $model_ids = array();

		if(!$model){
			$this->request->response = JSON::error('Номенклатура не найдена.'); return;
		}

		$user = Auth::instance()->get_user();
		$ids_added = Jelly::factory($model_to)->add_nomenclature($model, $model_ids, $user->license->id(), $farm_id);

		$this->request->response = JSON::success(array('script' => "Added", 'url' => null, 'success' => true, 'ids_added'=>$ids_added));
	}

	public function action_tree(){
		$user = Auth::instance()->get_user();
        $with_extras = arr::get($_GET, 'with_extras', false);

		$data =	Jelly::factory($this->model_name)->get_tree($user->license->id(), $this->group_field);

        if($with_extras)
            foreach($data as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras($this->model_name, substr($record['id'], 1));

		$this->request->response = Json::arr($data, count($data));
	}


	public function action_update(){
		if (is_null($user = $this->auth_user())) return;

		$farm = Arr::get($_POST, 'farm', null);
		if($farm)
		{
			$selected_farm_obj = Jelly::select('farm')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->and_where('license', '=', $user->license->id())->load((int)$farm);
			if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded())
			{
				Session::instance()->set('last_create_farm', (int)$farm);
			}
			else throw new Kohana_Exception('Хозяйства не существует');
		}


		$license_id   = (int)(Auth::instance()->get_user()->license->id());

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$_POST['period']           = (int)$periods[0];
		$period_id           = (int)$periods[0];

        if($id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select($this->model_name, (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else{
			$model = Jelly::factory($this->model_name);
		}

		$model->update_date = time();

		$gsm_ids = arr::get($_POST, 'gsm_string', '');
		if(!$gsm_ids){
			$gsm_ids = array(0 => 0);
		} else {
			$gsm_ids = explode(',', $gsm_ids);
			foreach($gsm_ids as $id){
				$id = (int)$id;
			}
		}
		$_POST['gsm'] = $gsm_ids;

		$this->validate_data($_POST);

		$model->set($_POST);

		$model->deleted = 0;

		$model->license   = Auth::instance()->get_user()->license->id();

		$group = (int)(arr::get($_POST, 'group', NULL));
		$group_in_glossary = (int)(arr::get($_POST, 'group_in_glossary', NULL));
		$group_name = arr::get($_POST, 'parent_name', NULL);
		$parent_group_in_handbook = Jelly::select('client_handbook_techniquetrailergroup')->
				where(($group_in_glossary!==-1)?'id_in_glossary':'_id','=',($group_in_glossary!==-1)?$group_in_glossary:$group)->
				where('license','=',$license_id)->
//				where('farm','=',$farm)->
				where('period','=',$period_id)->
				where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
				execute();
		$parent_group_in_handbook = $parent_group_in_handbook[0];

		$parent_group_in_glossary = Jelly::select('glossary_techtrailer_group')->where('_id','=',$parent_group_in_handbook->id_in_glossary)->execute();
		$parent_group_in_glossary = $parent_group_in_glossary[0];

		if ($parent_group_in_handbook && $parent_group_in_handbook->_id>0) {

			$model->group = $parent_group_in_handbook->_id;

		} else if ($parent_group_in_glossary) {
			$new_parent_group_in_handboook = Jelly::factory('client_handbook_techniquetrailergroup');
			$new_parent_group_in_handboook->update_date = time();
			$new_parent_group_in_handboook->name = trim($group_name);
			$new_parent_group_in_handboook->license = $model->license;
			$new_parent_group_in_handboook->deleted = 0;
			$new_parent_group_in_handboook->farm = $selected_farm_obj;
			$new_parent_group_in_handboook->period = $period_id;
			$new_parent_group_in_handboook->color = $parent_group_in_glossary->color;
			$new_parent_group_in_handboook->save();

			$model->group = $new_parent_group_in_handboook->_id;
		} else {
			$model->group = 0;
		}

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

	public function inner_update($contragent_id){

		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'chief_prop_') !== false and UTF8::strpos($key, '_label') === false){
				$addons[] = array('_id'     => (int)UTF8::str_ireplace('chief_prop_', '', $key),
										  'name'  => arr::get($_POST,$key.'_label',''),
										  'value'  => $value);
			}
			if(UTF8::strpos($key, 'insert_chief_property_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'name_chief_insert_'.UTF8::str_ireplace('insert_chief_property_', '', $key),''),
										  'value'  => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$contragent_id, 'handbooktechtrailer_chief', $addons);


		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'end_prop_') !== false and UTF8::strpos($key, '_label') === false){
				$addons[] = array('_id'     => (int)UTF8::str_ireplace('end_prop_', '', $key),
										  'name'  => arr::get($_POST,$key.'_label',''),
										  'value'  => $value);
			}
			if(UTF8::strpos($key, 'insert_end_property_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'name_end_insert_'.UTF8::str_ireplace('insert_end_property_', '', $key),''),
										  'value'  => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$contragent_id, 'handbooktechtrailer_end', $addons);

	}

	private function auth_user()
    {
		if(!(($user = Auth::instance()->get_user()) instanceof Jelly_Model) or !$user->loaded())
        {
            $this->request->response = JSON::error(__("User ID is not specified"));
            return NULL;
        }

        return $user;
    }


	public function action_edit($id = null, $read = false, $parent_id = false){

		if (is_null($user = $this->auth_user())) return;

        $model = null;

        if($id){
            $model = Jelly::select('client_handbook_techniquetrailer')->with('group')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/handbook/techniquetrailer/read_techniquetrailer');
		$view->floating = Arr::get($_GET, 'from_floating', false);

        if($model)
        {
            $view->model  			               = $model->as_array();
            $view->model['group']                = $model->group->id();
			$view->model['parent_color']                = $model->group->color;
			$view->model['parent_name']                = $model->group->name;
			$view->model['farm']                = $model->farm;
            $view->group_field                    = 'group';
            $this->action_getphoto($view, $model->id());
		} else {
			$view->creation = true;
        }

        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id)
            {
                    $view->model                           = array();
                    $view->model['group']                  = $parent_id;
                    $view->group_field                     = 'group';
					$parent = Jelly::select('client_handbook_techniquetrailergroup', (int)$parent_id);
					$view->parent_color                     = $parent->color;
					$view->parent_name                     = $parent->name;
            }
		}

        if($model){
			$view->model['properties']  = $model->get_properties();
			$view->model['chief_properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbooktechtrailer_chief')->execute()->as_array();
			$view->model['end_properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbooktechtrailer_end')->execute()->as_array();
        }

        $fields  = Jelly::meta($this->model_name)->fields();
		$addon_fields = array();
		$field_types = array();


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
		$view->square_names = false;


		$farms = Jelly::select('farm')->
                    where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                    and_where('license', '=', $user->license->id())->
                    //and_where('is_group', '=', false)->
                    order_by('name', 'asc');

        $session 		= Session::instance();
        $s_farms 		= $session->get('farms');
        $s_farm_groups 	= $session->get('farm_groups');

        if(!is_array($s_farms))
        	$s_farms = array();

		if(!is_array($s_farm_groups))
        	$s_farm_groups = array();

		$farm_ids = array_unique(array_merge($s_farms, $s_farm_groups));

		if(count($s_farms) or count($s_farm_groups))
	   {
  			$farms->where(':primary_key', 'IN', $farm_ids);
        }

        $farms  = $farms->execute()->
        as_array();

        $selected_farm = Arr::get($_GET, 'selected', '');
        $selected_farm_name = '';
        $selected_farm_color = '';
		$selected_farm_is_group = '';

        if($selected_farm != '')
        {
            $selected_farm_obj = Jelly::select('farm')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->and_where('license', '=', $user->license->id())->load((int)$selected_farm);

            if(($selected_farm_obj instanceof Jelly_Model)
                and $selected_farm_obj->loaded())
                {
                    $selected_farm_name  = $selected_farm_obj->name;
                    $selected_farm_color = $selected_farm_obj->color;
					$selected_farm_is_group = $selected_farm_obj->is_group;
                }
        }

        /**
         *  3) В случае, когда нет фокуса на хозяйстве, в первый раз при создании поля по умолчанию ставить ему первое хозяйство как родительское, и все последующие разы по умолчанию родительское хозяйство у поля - это последнее выбранное хозяйство, для которого создавалось поле (фокус на хозяйстве приоритетней чем значение по умолчанию)
         * */

        /** ХЗ, что такое "первое хозяйство", надеюсь, это первое по названию, но кто его знает. И не описано, че делать при включенном фильтре.
         *  I was forced to write this code. Forgive me.
         **/

        $last_farm = $session->get('last_create_farm', '');
        if($selected_farm == '')
        {
            if($last_farm != '' && in_array($selected_farm,$farm_ids) ) {
                $selected_farm = $last_farm;

                $selected_farm_obj = Jelly::select('farm')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->and_where('license', '=', $user->license->id())->load((int)$selected_farm);

                if(($selected_farm_obj instanceof Jelly_Model)
                and $selected_farm_obj->loaded())
                {
                    $selected_farm_name = $selected_farm_obj->name;
                    $selected_farm_color = $selected_farm_obj->color;
					$selected_farm_is_group = $selected_farm_obj->is_group;
                }
            }
            else
            {
                if(count($farms))
                {
                    $selected_farm      = $farms[0]['_id'];
                    $selected_farm_name = $farms[0]['name'];
                    $selected_farm_color = $farms[0]['color'];
					$selected_farm_is_group = $farms[0]['is_group'];
                }
            }
        }

        $view->selected_farm = $selected_farm;
        $view->selected_farm_name = $selected_farm_name;
        $view->selected_farm_color = $selected_farm_color;
		$view->selected_farm_is_group = $selected_farm_is_group;

        $view->farms = $farms;
//		$view->edit = true;







		$this->inner_edit($view);


		$this->request->response = JSON::reply($view->render());
	}


	public function inner_edit(&$view){
		if($view->model && isset($view->model['_id'])){
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

		$view->soil_depth_units = Jelly::factory('glossary_units')->getUnits('soil_depth');

		$view->lift_capacity_units = Jelly::factory('glossary_units')->getUnits('lift_capacity');
		$view->weight_units = Jelly::factory('glossary_units')->getUnits('weight');
		$view->length_units = Jelly::factory('glossary_units')->getUnits('length');
        $view->cost_units = Jelly::factory('glossary_units')->getUnits('personal_payment');

		$view->model_fields = array();
	}

	public function action_data($item_id){
		$data = Jelly::select('client_handbook_techniquetrailer')->with('group')->where(':primary_key', '=', (int)$item_id)->load();
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

	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		$groups_of_deleted_items = array();

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='g' ? $this->model_group_name : $this->model_name;
			if ($id==-2 || $id=='-2') {

				$items_to_delete = Jelly::select($this->model_name)->with('_id')->where('group_id','=',NULL || 0)->execute()->as_array();
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select($this->model_name, (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}

			} else {

				$model = Jelly::select($m, (int)$id);

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}

				$model->delete();

				array_push($groups_of_deleted_items, $model->group->id());

			}
		}

		$groups_of_deleted_items = array_unique($groups_of_deleted_items);






		for($i=0; $i<count($groups_of_deleted_items); $i++){
			$id = $groups_of_deleted_items[$i];
			$group = Jelly::select($this->model_group_name, (int)$id);

			$names_left = Jelly::select($this->model_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('group','=',$group->id())->execute();
			$child_groups_left = Jelly::select($this->model_group_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('path','LIKE',  '%/'.$group->id().'/%')->execute();
			if (count($names_left)===0 && count($child_groups_left)===0 ){
				$group->delete();
			}
		}


		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

	public function clean_empty_groups(){

	}


}