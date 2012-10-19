<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Predecessor extends Controller_Glossary_Abstract
{

	protected $model_name 		= 'glossary_predecessor';
	protected $model_group_name = 'glossary_predecessor';

	public function action_list(){
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$cultures = Arr::get($_GET, 'checked', '');
		if($cultures!='')$cultures = explode(',', $cultures);
		else $cultures = array();

		$edit = filter_var(Arr::get($_GET, 'editMode', true), FILTER_VALIDATE_BOOLEAN);

		$data =	Jelly::factory('glossary_predecessor')->get_list($cultures, false, $edit);
		$this->request->response = JSON::success(array(
					'data' =>		 $data['result'],
					'count' =>		 count($data['result']),
					'conflicted' => $data['conflicted'],
					'success' =>	true
		));
	}


	public function action_update(){

		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}


		$records = arr::get($_POST, 'records', '');
		$records = json_decode($records, true);

		$update_items = arr::get($_POST, 'update_items', '');
		if(!$update_items){ $this->request->response = JSON::error("Записи для редактирования не найдены."); return; }
		$update_items = explode('_', $update_items);

		if(count($update_items)) Jelly::update('glossary_predecessor')->set(array('deleted' => true))->where('culture', 'IN', $update_items)->execute();


		foreach($records as $record){
			if(UTF8::strpos($record['id'], 'fake_skip') !== false)continue;
			if(UTF8::strpos($record['id'], 'new_') !== false){
				$predecessor = Jelly::factory('glossary_predecessor');
			}else{
				$predecessor = Jelly::select('glossary_predecessor', (int)$record['id']);
			}

			$predecessor->culture = $record['left_id'];
			$predecessor->predecessor = $record['right_id'];
			$predecessor->deleted = false;
			$predecessor->outer_mark = $record['tab'];
			$predecessor->inner_mark = $record['mark'];
			$predecessor->save();
		}

		$this->request->response = JSON::success(array('script'	   => 'Оценки сохранены успешно!', 'url' => null, 'success' => true));
	}

	public function action_read($ids = ''){

		if($ids!='')$cultures = explode('_', $ids);
		else		 $cultures = array();

		$data =	Jelly::factory('glossary_predecessor')->get_list($cultures, true);

		$view = Twig::factory('glossary/predecessor/read_predecessor');
		$view->groups = $data['result'];

		$this->request->response = JSON::reply($view->render());
	}

	public function action_tree(){}
	public function action_move(){}
	public function action_edit($id = null, $read = false, $parent_id = false){}
	public function action_create($parent_id = 0){}
	public function action_delete($id = null){}

}
