<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_ContragentGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'client_contragent';
	protected $model_group_name = 'client_contragentgroup';
	
	public function action_tree(){
	
		$user = Auth::instance()->get_user();
	
		$exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
			$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		$with_cultures = Arr::get($_GET, 'both_trees', false);

		$data =	Jelly::factory($this->model_group_name)->get_tree($user->license->id(), $with_cultures, $exclude, $this->items_field);
		
		$this->request->response = Json::arr($data, count($data));
	}


	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;
		
        if($id && $id!=-2){
            $model = Jelly::select('client_contragentgroup')->with('parent')->where('license', '=', Auth::instance()->get_user()->license->id())->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }


		$view = Twig::factory('client/contragent/read_contragentgroup');

        if($id)
			$view->id = $id;

		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($model ? $model->parent->id() : 0);
			$view->hasChildren = false;
		}

        if($model){
			$view->model 	 = $model->as_array();
            $this->action_getphoto($view, $model->id());
        }else{
			$view->model	=	array();
		}

        if($model){
			$view->model['properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'contragentgroup_prop')->execute()->as_array();
			$view->model['chief_properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'contragentgroup_chief')->execute()->as_array();
        }

		$view->fake_group = $id==-2;

		$this->request->response = JSON::reply($view->render());
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
		Jelly::factory('extraproperty')->updateOldFields((int)$contragent_id, 'contragentgroup_chief', $addons);


		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'property_') !== false and UTF8::strpos($key, '_label') === false and UTF8::strpos($key, 'insert') === false){
				$addons[] = array('_id'     => (int)UTF8::str_ireplace('property_', '', $key),
										  'name'  => arr::get($_POST,$key.'_label',''),
										  'value'  => $value);
			}
			if(UTF8::strpos($key, 'insert_property_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'name_insert_'.UTF8::str_ireplace('insert_property_', '', $key),''),
										  'value'  => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$contragent_id, 'contragentgroup_prop', $addons);


		$address = arr::get($_POST,'address','');
		$address = @json_decode($address, true);


		$contragent = Jelly::select('client_contragentgroup', (int)$contragent_id);
		if(!($contragent instanceof Jelly_Model) or !$contragent->loaded())
				throw new Kohana_Exception('Record Not Found!!!');

		$contragent->address_country = $address['country'];
		$contragent->address_region   = $address['region'];
		$contragent->address_city      = $address['city'];
		$contragent->address_zip       = $address['zip'];
		$contragent->address_street   = $address['street'];

		$contragent->license   = Auth::instance()->get_user()->license->id();

		$contragent->save();
	}

}
