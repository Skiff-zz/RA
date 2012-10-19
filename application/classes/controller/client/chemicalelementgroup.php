<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_ChemicalelementGroup extends Controller_Glossary_AbstractGroup
{
	protected $model_name = 'client_chemicalelement';
	protected $model_group_name = 'client_chemicalelementgroup';

	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;
		
        if($id && $id!=-2){
            $model = Jelly::select('client_chemicalelementgroup')->with('parent')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/chemicalelement/read_chemicalelementgroup');

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


		$view->fake_group = $id==-2;

		$this->request->response = JSON::reply($view->render());
	}

	
}