<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Szr extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_szr';
	protected $model_group_name = 'glossary_szrgroup';
        protected $SYSTEM_SZR_GROUPS = array('g1001','g1002','g1003','g1004');


	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('glossary_szr')->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id)
        {
            $group = Jelly::select('glossary_szrgroup')->with('units')->where(':primary_key', '=', $parent_id)->load();

            if(!($group instanceof Jelly_Model) or !$group->loaded())
            {
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }
        }


		$view = Twig::factory('glossary/szr/read_szr');

        if($model)
        {
            $view->model  			               = $model->as_array();
            $view->model['group']                = $model->get($this->group_field)->id();
            $view->group_field                    = $this->group_field;

			$this->action_getphoto($view, $model->id());
        }

        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id)
            {
                    $view->model                           = array();
                    $view->model['group']                  = $parent_id;
                    $view->group_field                     = $this->group_field;
					$view->parent_color                     = $group->color;
            }
		}

        if(!$this->ignore_addons)
		{
			if(!$model)
			{
				if(!(int)$parent_id) $view->model = array();
				$view->model['properties']  = Jelly::factory($this->model_name)->get_properties();
			}
			else
			{
				$view->model['properties']  = $model->get_properties();
			}
        }

        if($model){
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

			$form = array();
			foreach($view->model['form'] as $raw_form){
				$form['_id'][] = $raw_form->id();
				$form['name'][] = ($raw_form->name).' '.($raw_form->short_name);
			}

			$form['_id'] = isset($form['_id']) ? implode(',', $form['_id']) : '';
			$form['name'] = isset($form['name']) ? implode(', ', $form['name']) : '';
			$view->model['form'] = $form;
        }

        $view->model_fields = array();

		$view->units = Jelly::factory('glossary_units')->getUnits('amount');
		$view->norm_units = Jelly::factory('glossary_units')->getUnits('szr_norm_amount');
		$view->dv_choice_units = Jelly::factory('glossary_units')->getUnits('szr_dv_amount');
		$view->preparative_forms = Jelly::select('glossary_preparativeform')->execute()->as_array();
		$view->deployment_types_all = Jelly::select('glossary_szr_deploymenttype')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();

		$szrcultures = $model ? Jelly::select('glossary_szr_szrculture')->
				with('culture')->
				with('targets')->
				with('szr_units')->
				with('mixture_units')->
				with('deployment_type')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				and_where('szr', '=', $model->id())->
				execute()
				: array();

		$szrdvs = $model ? Jelly::select('glossary_szr_szrdv')->with('dv')->with('units')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->and_where('szr', '=', $model->id())->execute() : array();

		$dvs = Jelly::select('glossary_szr_dv')->with('group')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();
		$targets = Jelly::select('glossary_szr_target')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->with('group')->execute()->as_array();
		$deployment_types = Jelly::select('glossary_szr_deploymenttype')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();
		$view->deployment_types = $deployment_types;

		$view->dvs = array(); $i=0;
		foreach($dvs as $dv){
			$view->dvs[$i] = $dv;
			$view->dvs[$i]['parent_color']  =  $dv[':group:color'];
			$i++;
		}

		$view->targets = array();
		$i=0;
		foreach($targets as $target){
			$view->targets[$i] = $target;
			$view->targets[$i]['parent_color']  =  $target[':group:color'];
			$i++;
		}

		$view->szrdvs = array(); $i=0;
		foreach($szrdvs as $szrdv){
			$view->szrdvs[$i] = $szrdv->as_array();
			//$view->szrdvs[$i]['license']  =  $szrdv->license->id();
			$view->szrdvs[$i]['dv'] =  array('_id' => $szrdv->dv->id(), 'name' => $szrdv->dv->name);
			$view->szrdvs[$i]['units'] = array('_id' => $szrdv->units->id(), 'name' => $szrdv->units->name);
			$view->szrdvs[$i]['szr'] = $szrdv->szr->id();
			$i++;
		}

		$view->szrcultures = array(); $i=0;
		$culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();
		foreach($szrcultures as $szrculture){
			$view->szrcultures[$i] = $szrculture->as_array();
			//$view->szrcultures[$i]['license']  =  $szrculture->license->id();
//			$view->szrcultures[$i]['culture'] =  array('_id' => $szrculture->culture->id(), 'name' => $szrculture->culture->name, 'title' => $szrculture->culture->title);
//			foreach($culture_types as $culture_type){
//				if($culture_type['_id']==$szrculture->culture->type->id()) $view->szrcultures[$i]['culture']['type'] = array('_id' => $culture_type['_id'], 'name' => $culture_type['name']);
//			}
			//if(count($culture_types) && $view->szrcultures[$i]['culture']['type']['_id']!=$culture_types[0]['_id']) $view->szrcultures[$i]['culture']['name'] .= ' '.$view->szrcultures[$i]['culture']['type']['name'];
			$view->szrcultures[$i]['szr_units'] =  array('_id' => $szrculture->szr_units->id(), 'name' => $szrculture->szr_units->name);
			$view->szrcultures[$i]['mixture_units'] =  array('_id' => $szrculture->mixture_units->id(), 'name' => $szrculture->mixture_units->name);
			$view->szrcultures[$i]['targets'] = $szrculture->targets->as_array();
			$view->szrcultures[$i]['cultures'] = $szrculture->culture->as_array();
			$view->szrcultures[$i]['deployment_types'] = $szrculture->deployment_type->as_array();

			$deployment_type_names = array();
			$deployment_type_ids = array();
			foreach($szrculture->deployment_type as $deployment_type){
				$deployment_type_names[] = $deployment_type->name;
				$deployment_type_ids[] = (int)$deployment_type->_id;
			}
			$view->szrcultures[$i]['deployment_types_ids'] = count($deployment_type_ids) ? implode(',', $deployment_type_ids) : '';
			$view->szrcultures[$i]['deployment_types_names'] = count($deployment_type_ids) ? implode(', ', $deployment_type_names) : '';

			$target_names = array();
			$target_ids = array();
			foreach($szrculture->targets as $target){
				$target_names[] = $target->name;
				$target_ids[] = $target->_id;
			}
			$view->szrcultures[$i]['target_ids'] = count($target_ids) ? implode(',', $target_ids) : '';
			$view->szrcultures[$i]['target_names'] = count($target_ids) ? implode(', \n', $target_names) : '';

			$culture_names = array();
			$culture_ids = array();
			foreach($szrculture->culture as $culture){
				$culture_names[] = $culture->title;
				$culture_ids[] = (int)$culture->_id;
			}

			$view->szrcultures[$i]['cultures_ids'] = count($culture_ids) ? implode(',', $culture_ids) : '';
			$view->szrcultures[$i]['cultures_names'] = count($culture_names) ? implode(', \n', $culture_names) : '';

			$deployment_types_names = array();
			$deployment_types_ids = array();

			foreach($szrculture->deployment_type as $deployment_type){
				$deployment_types_names[] = $deployment_type->name;
				$deployment_types_ids[] = $deployment_type->_id;
			}
			$view->szrcultures[$i]['deployment_types_ids'] = count($deployment_types_ids) ? implode(',', $deployment_types_ids) : '';
			$view->szrcultures[$i]['deployment_types_names'] = count($deployment_types_ids) ? implode(', ', $deployment_types_names) : '';

			$view->szrcultures[$i]['szr'] = $szrculture->szr->id();
			$i++;
		}
		$this->request->response = JSON::reply($view->render());
	}

	public function action_update() {


		if ($id = arr::get($_POST, '_id', NULL)) {

			$model = Jelly::select($this->model_name, (int) $id);
			if (!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else {
			$group = isset($_POST[$this->group_field]) ? (int) $_POST[$this->group_field] : 0;
//			if ($group && $group=='g1001') {
//				$this->request->response = JSON::error('Нельзя создавать названия непосредственно в Гербицидах.');
//				return;
//			}

			$model = Jelly::factory($this->model_name);
		}

		$model->update_date = time();


		if (!$id) {
			$check = Jelly::select($this->model_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int) $_POST[$this->group_field] : 0)->load();
		} else {
			$check = Jelly::select($this->model_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int) $_POST[$this->group_field] : 0)->where(':primary_key', '!=', (int) $id)->load();
		}

		if (($check instanceof Jelly_Model) and $check->loaded()) {
			$this->request->response = JSON::error('Уже есть такая запись в другой группе (' . $check->group->name . ') !');
			return;
		}


		$this->validate_data($_POST);

		$model->set($_POST);

		$model->deleted = 0;

		$model->save();

		$this->action_savephoto($model->id());

		$this->inner_update($model->id());

		// Допполя
		if (!$this->ignore_addons) {
			$add = array();

			/*
			  insert_property_
			  name_insert
			 */

			// Удаляем старые
			$properties = $model->get_properties();

			foreach ($properties as $property_id => $property) {
				if (!array_key_exists('property_' . $property_id, $_POST)) {
					$model->delete_property($property_id);
				}
			}

			//Новые допполя
			foreach ($_POST as $key => $value) {
				if (UTF8::strpos($key, 'insert_property_') !== false) {
					$property_id = (int) UTF8::str_ireplace('insert_property_', '', $key);

					$add[$_POST['name_insert_' . $property_id]] = $_POST['insert_property_' . $property_id];
				}
			}

			foreach ($add as $key => $value) {
				$model->set_property(0, $key, $value);
			}

			// Старые допполя

			foreach ($_POST as $key => $value) {
				if (UTF8::strpos($key, 'property_') !== false) {
					$id = (int) UTF8::str_ireplace('property_', '', $key);

					if (array_key_exists('property_' . $id . '_label', $_POST)) {
						$model->set_property($id, $_POST['property_' . $id . '_label'], $_POST['property_' . $id]);
					}
				}
			}
		}

		$culture_id = $model->id();

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!',
					'url' => null,
					'success' => true,
					'item_id' => $culture_id));
	}


	public function inner_update($szr_id){
		$szr = Jelly::select('glossary_szr',(int)$szr_id);

		$producer_ids = arr::get($_POST, 'producer_string', '');
		if($producer_ids){
			$producer_ids_arr = explode(',', $producer_ids);
			foreach($producer_ids_arr as &$id){
				$id = (int)$id;
			}

			$szr->producer = $producer_ids_arr;
		} else {
			$szr->producer = array();
		}

		$form_ids = arr::get($_POST, 'form_string', '');
		if($form_ids){
			$form_ids_arr = explode(',', $form_ids);
			foreach($form_ids_arr as &$id){
				$id = (int)$id;
			}

			$szr->form = $form_ids_arr;
		} else {
			$szr->form = array();
		}

		$szr->expend_units = array_key_exists('expend_units', $_POST) ? (int)(json_decode($_POST['expend_units'])) : NULL;
		$szr->expend_units_id = array_key_exists('expend_units', $_POST) ? (int)(json_decode($_POST['expend_units'])) : NULL;
		$arr = array_key_exists('expend', $_POST) ? explode(' ', (    $_POST['expend'] )) : null;
		$szr->expend = $arr ? $arr[0] : 0;
		$szr->save();

		$dvs= array();
		setlocale(LC_NUMERIC, 'C');
		$dv_arr = json_decode($_POST['szrdv_list']);
		for($i=0;$i<count($dv_arr);$i++){
			$dv = $dv_arr[$i];
			$id = (int)$dv->name->id;
			$value_n_units = explode(' ', isset($dv->count->fullNumber) ? $dv->count->fullNumber : $dv->count->value);
			$value = $value_n_units[0];
			$units_id = $dv->count->selectedUnits;
			$dvs[] = array(
				'dv' => (int)$id,
				'szr_dvs' => (int)$id,
				'value' => $value,
				'units' => $units_id
			);
		}

		Jelly::factory('glossary_szr_szrdv')->updateRecords((int)$szr_id, $dvs,  Auth::instance()->get_user()->license->id());


		$cultures= array();
		setlocale(LC_NUMERIC, 'C');
		$cultures_arr = json_decode($_POST['szrculture_list']);

		for($i=0;$i<count($cultures_arr);$i++){

			$culture = $cultures_arr[$i];

			$min = (explode(' ', isset($culture->szr_crop_norm_min->fullNumber) ? $culture->szr_crop_norm_min->fullNumber : $culture->szr_crop_norm_min->value));
			$min = $min[0];
			$mid = (explode(' ', isset($culture->szr_crop_norm_mid->fullNumber) ? $culture->szr_crop_norm_mid->fullNumber : $culture->szr_crop_norm_mid->value));
			$mid = $mid[0];
			$max = (explode(' ', isset($culture->szr_crop_norm_max->fullNumber) ? $culture->szr_crop_norm_max->fullNumber : $culture->szr_crop_norm_max->value));
			$max = $max[0];

			foreach(get_object_vars($culture) as $key => $value){
				if(strrpos($key, 'deployment_type_name_')!==FALSE){
					$deployment_type_id = $culture->$key->selectedUnits;
					break;
				}
			}
			$culture_arr = get_object_vars($culture);
			$cultures[] = array(
				'cultures' => $culture_arr['szrculture_cultures_'.($culture->rowId)],
				'szr_crop_norm_min' => $min,
				'szr_crop_norm_mid' => $mid,
				'szr_crop_norm_max' => $max,
				'szr_units' => $culture->szr_crop_norm_min->selectedUnits,
				'szr_max_kratn_obrab' => $culture->szr_max_kratn_obrab,
				'szr_processing_deadline' => $culture->szr_processing_deadline,
				'targets' => $culture_arr['szrculture_targets_'.($culture->rowId)],
				'deployment_types' => $culture_arr['deployment_type_name_'.($culture->rowId)]->selectedUnits
			);
		}

		Jelly::factory('glossary_szr_szrculture')->updateRecords((int)$szr_id, $cultures,  Auth::instance()->get_user()->license->id());
	}

}
