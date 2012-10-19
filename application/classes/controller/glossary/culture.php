<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Culture extends Controller_Glossary_Abstract
{

	protected $model_name = 'glossary_culture';
	protected $model_group_name = 'glossary_culturegroup';



	public function inner_edit(&$view){

//		if($view->culture && $view->culture['type']){
//			$view->culture['type'] = array('_id' => $view->culture['type']->id(), 'name' => $view->culture['type']->name);
//		}
		$view->model_fields = array();
		$view->culture_types = Jelly::select('glossary_culturetype')->execute()->as_array();

//		if($view->model && isset($view->model['_id'])){
//			$view->model['name'] = trim(str_replace($view->model['type']->name, '', $view->model['name']));
//		}
	}


	public function action_update(){


		$values = array('name', 'crop_rotation_interest', 'color', 'type', 'group');
        if($culture_id = arr::get($_POST, '_id', NULL)){
			$culture_model = Jelly::select('glossary_culture', (int)$culture_id);
		}else{
			$culture_model = Jelly::factory('glossary_culture');
		}

		$culture_model->update_date = time();
        $_POST['crop_rotation_interest'] = (float)$_POST['crop_rotation_interest'];
		$_POST['group'] = (int)Arr::get($_POST,'group',0);

        if((int)Arr::get($_POST, 'type') == 0)
        {
            $jar = Jelly::select('glossary_culturetype')->where('name', 'LIKE', 'Яр%')->load();

            if(($jar instanceof Jelly_Model) and $jar->loaded())
            {
                $_POST['type'] =  $jar->id();
            }
        }

		$culture_model->set(Arr::extract($_POST, $values));
		$culture_model->name = trim($culture_model->name);


		if($culture_id)
		{
			$check = Jelly::select('glossary_culture')->where('deleted', '=', false)->where('name', 'LIKE', $culture_model->name)->where('group', '!=', (int)$_POST['group'])->where(':primary_key', '!=', $culture_id)->load();
		}
        else
		{
			$check = Jelly::select('glossary_culture')->where('deleted', '=', false)->where('name', 'LIKE', $culture_model->name)->where('group', '!=', (int)$_POST['group'])->load();
        }

        if(($check instanceof Jelly_Model) and $check->loaded())
        {
            $this->request->response = JSON::error('В одной из групп культур уже существует культура с именем "'.$culture_model->name.'"');
            return;
        }

        if($culture_id)
		{
			$check = Jelly::select('glossary_culture')->where('deleted', '=', false)->where('name', 'LIKE', $culture_model->name)->where('group', '=', (int)$_POST['group'])->where('type', '=', (int)$_POST['type'])->where(':primary_key', '!=', $culture_id)->load();
		}
		else
		{
			$check = Jelly::select('glossary_culture')->where('deleted', '=', false)->where('name', 'LIKE', $culture_model->name)->where('group', '=', (int)$_POST['group'])->where('type', '=', (int)$_POST['type'])->load();
		}



        if(($check instanceof Jelly_Model) and $check->loaded())
        {
            $this->request->response = JSON::error('В этой группе культур уже существует культура с именем "'.$culture_model->name.'"  и указанным типом!');
            return;
        }

		$culture_model->save();
		$culture_id = $culture_model->id();

		$this->action_savephoto($culture_id);

		$this->request->response = JSON::success(array('script'	   => 'Культура сохранена успешно!',
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $culture_id));
	}


}

