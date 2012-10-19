<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Fertilizer extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_fertilizer';
	protected $model_group_name = 'glossary_fertilizergroup';

	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('glossary_fertilizer')->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id)
        {
            $group = Jelly::select('glossary_fertilizergroup')->with('units')->where(':primary_key', '=', $parent_id)->load();

            if(!($group instanceof Jelly_Model) or !$group->loaded())
            {
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }
        }


		$view = Twig::factory('glossary/fertilizer/read_fertilizer');

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
		$view->dv_choice_units = Jelly::factory('glossary_units')->getUnits('szr_dv_amount');// общее с сзр (пока)

		$view->preparative_forms = Jelly::select('glossary_preparativeform')->execute()->as_array();
		$view->deployment_types = Jelly::select('glossary_fertilizer_deploymenttype')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();
		$view->deployment_types_all = Jelly::select('glossary_fertilizer_deploymenttype')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->execute()->as_array();

		$fertilizercultures = $model ? Jelly::select('glossary_fertilizerculture')->with('culture')->with('fertilizer_units')->with('mixture_units')->with('deployment_type')->and_where('fertilizer', '=', $model->id())->execute() : array();
		$fertilizerdvs = $model ? Jelly::select('glossary_fertilizer_fertilizerdv')->with('dv')->with('units')->and_where('fertilizer', '=', $model->id())->execute() : array();

		$dvs = Jelly::select('glossary_fertilizer_dv')->with('group')->execute()->as_array();

		$view->dvs = array(); $i=0;
		foreach($dvs as $dv){
			$view->dvs[$i] = $dv;
			$view->dvs[$i]['parent_color']  =  $dv[':group:color'];
			$i++;
		}


		$view->fertilizerdvs = array(); $i=0;
		foreach($fertilizerdvs as $fertilizerdv){
			$view->fertilizerdvs[$i] = $fertilizerdv->as_array();
//			$view->fertilizerdvs[$i]['license']  =  $fertilizerdv->license->id();
			$view->fertilizerdvs[$i]['dv'] =  array('_id' => $fertilizerdv->dv->id(), 'name' => $fertilizerdv->dv->name);
			$view->fertilizerdvs[$i]['units'] = array('_id' => $fertilizerdv->units->id(), 'name' => $fertilizerdv->units->name);
			$view->fertilizerdvs[$i]['fertilizer'] = $fertilizerdv->fertilizer->id();
			$i++;
		}

		$view->fertilizercultures = array(); $i=0;
		$culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();
		foreach($fertilizercultures as $fertilizerculture){
			$view->fertilizercultures[$i] = $fertilizerculture->as_array();
//			$view->fertilizercultures[$i]['license']  =  $fertilizerculture->license->id();
//			$view->fertilizercultures[$i]['culture'] =  array('_id' => $fertilizerculture->culture->id(), 'name' => $fertilizerculture->culture->name, 'title' => $fertilizerculture->culture->title);
//			foreach($culture_types as $culture_type){
//				if($culture_type['_id']==$fertilizerculture->culture->type->id()) $view->fertilizercultures[$i]['culture']['type'] = array('_id' => $culture_type['_id'], 'name' => $culture_type['name']);
//			}
			//if(count($culture_types) && $view->fertilizercultures[$i]['culture']['type']['_id']!=$culture_types[0]['_id']) $view->fertilizercultures[$i]['culture']['name'] .= ' '.$view->fertilizercultures[$i]['culture']['type']['name'];
			$view->fertilizercultures[$i]['fertilizer_units'] =  array('_id' => $fertilizerculture->fertilizer_units->id(), 'name' => $fertilizerculture->fertilizer_units->name);
			$view->fertilizercultures[$i]['mixture_units'] =  array('_id' => $fertilizerculture->mixture_units->id(), 'name' => $fertilizerculture->mixture_units->name);
			$view->fertilizercultures[$i]['fertilizer'] = $fertilizerculture->fertilizer->id();
			$view->fertilizercultures[$i]['cultures'] = $fertilizerculture->culture->as_array();
			$view->fertilizercultures[$i]['deployment_types'] = $fertilizerculture->deployment_type->as_array();

			$deployment_type_names = array();
			$deployment_type_ids = array();
			foreach($fertilizerculture->deployment_type as $deployment_type){
				$deployment_type_names[] = $deployment_type->name;
				$deployment_type_ids[] = (int)$deployment_type->_id;
			}
			$view->fertilizercultures[$i]['deployment_types_ids'] = count($deployment_type_ids) ? implode(',', $deployment_type_ids) : '';
			$view->fertilizercultures[$i]['deployment_types_names'] = count($deployment_type_ids) ? implode(', ', $deployment_type_names) : '';

			$culture_names = array();
			$culture_ids = array();
			foreach($fertilizerculture->culture as $culture){
				$culture_names[] = $culture->title;
				$culture_ids[] = (int)$culture->_id;
			}

			$view->fertilizercultures[$i]['cultures_ids'] = count($culture_ids) ? implode(',', $culture_ids) : '';
			$view->fertilizercultures[$i]['cultures_names'] = count($culture_names) ? implode(', \n', $culture_names) : '';

			$deployment_types_names = array();
			$deployment_types_ids = array();

			foreach($fertilizerculture->deployment_type as $deployment_type){
				$deployment_types_names[] = $deployment_type->name;
				$deployment_types_ids[] = $deployment_type->_id;
			}
			$view->fertilizercultures[$i]['deployment_types_ids'] = count($deployment_types_ids) ? implode(',', $deployment_types_ids) : '';
			$view->fertilizercultures[$i]['deployment_types_names'] = count($deployment_types_ids) ? implode(', ', $deployment_types_names) : '';

			$view->fertilizercultures[$i]['fertilizer'] = $fertilizerculture->fertilizer->id();
			$i++;
		}
		$this->request->response = JSON::reply($view->render());
	}


	public function inner_update($fertilizer_id){
		$fertilizer = Jelly::select('glossary_fertilizer',(int)$fertilizer_id);

		$producer_ids = arr::get($_POST, 'producer_string', '');
		if($producer_ids){
			$producer_ids_arr = explode(',', $producer_ids);
			foreach($producer_ids_arr as &$id){
				$id = (int)$id;
			}

			$fertilizer->producer = $producer_ids_arr;
		} else {
			$fertilizer->producer = array();
		}

		$form_ids = arr::get($_POST, 'form_string', '');
		if($form_ids){
			$form_ids_arr = explode(',', $form_ids);
			foreach($form_ids_arr as &$id){
				$id = (int)$id;
			}

			$fertilizer->form = $form_ids_arr;

		} else {
			$fertilizer->form = array();
		}

		$fertilizer->expend_units = array_key_exists('expend_units', $_POST) ? (int)(json_decode($_POST['expend_units'])) : NULL;
		$fertilizer->expend_units_id = array_key_exists('expend_units', $_POST) ? (int)(json_decode($_POST['expend_units'])) : NULL;
		$arr = array_key_exists('expend', $_POST) ? explode(' ', (    $_POST['expend'] )) : null;
		$fertilizer->expend = $arr ? $arr[0] : 0;
		$fertilizer->save();

		$dvs= array();
		setlocale(LC_NUMERIC, 'C');
		$dv_arr = json_decode($_POST['fertilizerdv_list']);
		for($i=0;$i<count($dv_arr);$i++){
			$dv = $dv_arr[$i];
			$id = (int)$dv->name->id;
			$value_n_units = explode(' ', isset($dv->count->fullNumber) ? $dv->count->fullNumber : $dv->count->value);
			$value = $value_n_units[0];
			$units_id = $dv->count->selectedUnits;
			$dvs[] = array(
				'dv' => (int)$id,
				'fertilizer_dvs' => (int)$id,
				'value' => $value,
				'units' => $units_id
			);
		}

		Jelly::factory('glossary_fertilizer_fertilizerdv')->updateRecords((int)$fertilizer_id, $dvs,  Auth::instance()->get_user()->license->id());


		$cultures= array();
		setlocale(LC_NUMERIC, 'C');
		$cultures_arr = json_decode($_POST['fertilizerculture_list']);
		for($i=0;$i<count($cultures_arr);$i++){

			$culture = $cultures_arr[$i];
//			$id = (int)$culture->name->id;

			$min = (explode(' ', isset($culture->fertilizer_crop_norm_min->fullNumber) ? $culture->fertilizer_crop_norm_min->fullNumber : $culture->fertilizer_crop_norm_min->value));
			$min = $min[0];
			$mid = (explode(' ', isset($culture->fertilizer_crop_norm_mid->fullNumber) ? $culture->fertilizer_crop_norm_mid->fullNumber : $culture->fertilizer_crop_norm_mid->value));
			$mid = $mid[0];
			$max = (explode(' ', isset($culture->fertilizer_crop_norm_max->fullNumber) ? $culture->fertilizer_crop_norm_max->fullNumber : $culture->fertilizer_crop_norm_max->value));
			$max = $max[0];

			foreach(get_object_vars($culture) as $key => $value){
				if(strrpos($key, 'deployment_type_name_')!==FALSE){
					$deployment_type_id = $culture->$key->selectedUnits;
					break;
				}
			}
			$culture_arr = get_object_vars($culture);
			$cultures[] = array(
				'cultures' => $culture_arr['fertilizerculture_cultures_'.($culture->rowId)],
				'fertilizer_crop_norm_min' => $min,
				'fertilizer_crop_norm_mid' => $mid,
				'fertilizer_crop_norm_max' => $max,
				'fertilizer_units' => $culture->fertilizer_crop_norm_min->selectedUnits,
				'fertilizer_max_kratn_obrab' => $culture->fertilizer_max_kratn_obrab,
				'fertilizer_processing_deadline' => $culture->fertilizer_processing_deadline,
				'deployment_types' => $culture_arr['deployment_type_name_'.($culture->rowId)]->selectedUnits
			);
		}

		Jelly::factory('glossary_fertilizerculture')->updateRecords((int)$fertilizer_id, $cultures,  Auth::instance()->get_user()->license->id());
	}

}
