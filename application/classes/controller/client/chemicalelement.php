<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Chemicalelement extends Controller_Glossary_Abstract
{
	protected $model_name = 'client_chemicalelement';
	protected $model_group_name = 'client_chemicalelementgroup';
	
	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select('client_chemicalelement')->with('group')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/chemicalelement/read_chemicalelement');

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
					$parent = Jelly::select('client_chemicalelementgroup', (int)$parent_id);
					$view->parent_color                     = $parent->color;
            }
		}

        if(!$this->ignore_addons)
		{
			if(!$model)
			{
				if(!(int)$parent_id) {
					$view->model = array();
				}
				$view->model['properties'] = Jelly::factory($this->model_name)->get_properties();
			}
			else
			{
				$view->model['properties']  = $model->get_properties();
			}
        }

        $fields  = Jelly::meta($this->model_name)->fields();
		$addon_fields = array();
		$field_types = array('symbol' => 'textfield');
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

		$this->request->response = JSON::reply($view->render());
	}
}