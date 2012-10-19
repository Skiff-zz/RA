<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_FertilizerDeploymentType extends Controller_Glossary_Abstract {

	protected $model_name = 'glossary_fertilizer_deploymenttype';
	protected $model_group_name = '';

	public function before() {

	}

	public function action_edit($id = null, $read = false, $parent_id = false) {

		$model = null;

		if ($id) {
			$model = Jelly::select($this->model_name)->where(':primary_key', '=', (int) $id)->load();

			if (!($model instanceof Jelly_Model) or !$model->loaded()) {
				$this->request->response = JSON::error('Запись не найдена!');
				return;
			}
		}


		$tpl = str_replace('glossary_', '', $this->model_name);

		$view = Twig::factory('glossary/' . $tpl . '/read_' . $tpl);

		if ($model) {

			$model->group = null;
			$view->model = array();
			$view->model['_id'] = $model->_id;
			$view->model['group'] = null;
			$view->model['name'] = $model->name;
			$view->model['color'] = $model->color;
			$this->action_getphoto($view, $model->id());
		}


		if (!$read) {
			$view->edit = true;

			if ((int) $parent_id) {
				$view->model = array();
			}
		}


		if (!$this->ignore_addons) {
			if (!$model) {
				if (!(int) $parent_id) {
					$view->model = array();
				}
				$view->model['properties'] = Jelly::factory($this->model_name)->get_properties();
			} else {
				$view->model['properties'] = $model->get_properties();
			}
		}

		// Уличная магия -- показываем дополнительные поля из модели, которых нет в абстрактном варианте
		$abstract_fields = Jelly::meta('glossary_abstract')->fields();
		$fields = Jelly::meta($this->model_name)->fields();

		$addon_fields = array();

		foreach ($fields as $field) {
			$field_found = false;

			foreach ($abstract_fields as $abstract) {
				if (strtolower($abstract->name) == strtolower($field->name)) {
					$field_found = true;
					break;
				}
			}

			if (!$field_found and !($field instanceof Jelly_Field_Relationship)) {
				$xtype = 'textfield';

				// Тут добавлять остальные допполя
				$addon_fields[] = array(
					'xtype' => $xtype,
					'name' => $field->name,
					'label' => $field->label,
					'value' => (isset($model) and $model instanceof Jelly_Model) ? $model->get($field->name) : null
				);
			}
		}

		$view->model_fields = $addon_fields;

		$this->inner_edit($view);
		$this->request->response = JSON::reply($view->render());
	}

}