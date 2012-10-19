<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Contragent extends Controller_Glossary_Abstract
{

	protected $model_name = 'client_contragent';
	protected $model_group_name = 'client_contragentgroup';

	protected $ignore_addons = true;
	
	
	public function action_tree()
	{
		$user = Auth::instance()->get_user();
		
		$data =	Jelly::factory($this->model_name)->get_tree($user->license->id(), $this->group_field);
		$this->request->response = Json::arr($data, count($data));
	}
	
	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_contragent')->with('group')->where('license', '=', Auth::instance()->get_user()->license->id())->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id)
        {
            $group = Jelly::select('client_contragentgroup')->where(':primary_key', '=', $parent_id)->where('license', '=', Auth::instance()->get_user()->license->id())->load();

            if(!($group instanceof Jelly_Model) or !$group->loaded())
            {
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }
        }


		$view = Twig::factory('client/contragent/read_contragent');

        if($model)
        {
            $view->model  			               = $model->as_array();
            $view->model['group']                = $model->group->id();
            $view->group_field                    = 'group';
            $this->action_getphoto($view, $model->id());
        }
        else
        {
        	$view->model = array();
			$view->group_field                    = 'group';  
		}
		
        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id)
            {
                    $view->model                           = array();
                    $view->model['group']                  = $parent_id;
                    $view->group_field                     = 'group';
					$view->parent_color                     = $group->color;
            }
		}

		$view->model['properties'] 			= Jelly::factory('client_model_properties')->get_properties('contragent_field', $id);
		$view->model['chief_properties'] 	= Jelly::factory('client_model_properties')->get_properties('contragent_chief', $id);

        $fields  = Jelly::meta($this->model_name)->fields();
		$addon_fields = array();
		$field_types = array('phone' => 'textfield', 'email' => 'textfield', 'address' => 'textfield', 'unp' => 'textfield', 'kzpo' => 'textfield');
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

		$this->request->response = JSON::reply($view->render());
	}


	public function inner_update($contragent_id){
		/*
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
		Jelly::factory('extraproperty')->updateOldFields((int)$contragent_id, 'contragent_chief', $addons);
		*/

		Jelly::factory('client_model_properties')->update_properties('contragent_field', $_POST, 'property', $contragent_id);
		Jelly::factory('client_model_properties')->update_properties('contragent_chief', $_POST, 'chief_property', $contragent_id, 'chief');


		$address = arr::get($_POST,'address','');
		$address = @json_decode($address, true);

		$contragent = Jelly::select('client_contragent', (int)$contragent_id);
		if(!($contragent instanceof Jelly_Model) or !$contragent->loaded())
				throw new Kohana_Exception('Record Not Found!');

		$contragent->address_country = $address['country'];
		$contragent->address_region   = $address['region'];
		$contragent->address_city      = $address['city'];
		$contragent->address_zip       = $address['zip'];
		$contragent->address_street   = $address['street'];

		$contragent->license   = Auth::instance()->get_user()->license->id();

		$contragent->save();
	}

}
