<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Permission extends AC_Controller{

	public $auto_render = false;

	public function action_index(){}


	public function action_menu_tree(){
		$data = Jelly::factory('farmpreset')->get_menu_tree();
		$this->request->response = Json::arr($data, count($data));
	}

	public function action_update_farm(){

		$permissions = arr::get($_POST, 'perms', '');
		$permissions = @json_decode($permissions, true);
		$user = Auth::instance()->get_user();

		$updated = array();

		foreach($permissions as $permission){

			//достаём пермишн из базы
			$perm = Jelly::select('farmpreset')->load((int)$permission['id']);
			if(!$perm instanceof Jelly_Model || !$perm->loaded()){
				continue;
			}

			if(!array_key_exists($perm->farm->id(), $updated))$updated[$perm->farm->id()] = array();
			$updated[$perm->farm->id()][] = $perm->id();

			//ставим сам пермишн
			$perm->permission = $permission['write'] ? Model_FarmPreset::WRITE : ($permission['read'] ? Model_FarmPreset::READ : Model_FarmPreset::DENIED);
			//если текущий юзер не админ хозяйства, для которого ставится пермишн, значит он круче админа и его установка непоколебима
			if($perm->farm->admin->id()!=$user->id()){
				$perm->set_by_admin = true;
				$perm->permission_by_admin = $perm->permission;
			}
			$perm->save();

			//текущая ферма и пункт меню
			$farm = $perm->farm->id();
			$menu = $perm->menu_item;

			//получаем поддерево дочерних хозяйств
			$farm_subtree = Jelly::factory('farm')->get_farm_subtree($farm);

			//получаем список пермишенов для дочерних хозяйств по текущему пункту меню, которые надо апдэйтить
			$perms = count($farm_subtree) ? Jelly::select('farmpreset')->where('farm', 'IN', $farm_subtree)->and_where('menu_item', '=', $menu)->execute() : array();
			foreach($perms as $p){
				//если у дочернего хозяйства было больше прав на этот раздел, то урезаем права
				if($p->permission>$perm->permission) $p->permission = $perm->permission;
				//ставим запрет на изменение
				$p->lock = $perm->permission==Model_FarmPreset::WRITE ? Model_FarmPreset::UNLOCK : ($perm->permission==Model_FarmPreset::READ ? Model_FarmPreset::LOCK_W : Model_FarmPreset::LOCK);
				$p->save();
			}
		}

		$this->remove_access('farmpreset', $updated);

		$this->request->response = Json::success(array());
	}

	public function action_read_farm(){
		return $this->action_edit_farm(true);
	}

	public function action_edit_farm($read = false){

		$lchecked = arr::get($_GET, 'l_checked', '');
		$rchecked = arr::get($_GET, 'r_checked', '');
		$just_checked = arr::get($_GET, 'just_checked', '');

		$lchecked = explode('_', $lchecked);
		if(isset($lchecked[0]) && !trim($lchecked[0])) $lchecked = array();
		$rchecked = explode('_', $rchecked);
		if(isset($rchecked[0]) && !trim($rchecked[0])) $rchecked = array();
		$just_checked = explode('_', $just_checked);
		if(isset($just_checked[0]) && !trim($just_checked[0])) $just_checked = array();

		foreach($lchecked as &$val) $val = substr($val, 1);
		foreach($rchecked as &$val) $val = substr($val, 1);

		if(!$read && !count($rchecked)) $rchecked = Jelly::factory('farmpreset')->get_checked_menus('farmpreset', 'farm', $lchecked);
		

		$data = Jelly::factory('farmpreset')->get_permissions(!$read, $lchecked, $rchecked);
		$data = Jelly::factory('farmpreset')->group_by($data, !$read);

		$view = Twig::factory('permission/list');
		$view->edit = !$read;
		$view->permissions = $data;
		$view->just_checked = $just_checked;

		$this->request->response = JSON::reply($view->render());
	}

	public function action_read_personal(){
		return $this->action_edit_personal(true);
	}

	public function action_edit_personal($read = false){

		$lchecked = arr::get($_GET, 'l_checked', '');
		$rchecked = arr::get($_GET, 'r_checked', '');
		$just_checked = arr::get($_GET, 'just_checked', '');

		$lchecked = explode('_', $lchecked);
		if(isset($lchecked[0]) && !trim($lchecked[0])) $lchecked = array();
		$rchecked = explode('_', $rchecked);
		if(isset($rchecked[0]) && !trim($rchecked[0])) $rchecked = array();
		$just_checked = explode('_', $just_checked);
		if(isset($just_checked[0]) && !trim($just_checked[0])) $just_checked = array();

		foreach($rchecked as &$val) $val = substr($val, 1);

		if(!$read && !count($rchecked)) $rchecked = Jelly::factory('personalpreset')->get_checked_menus($lchecked);

		$data = Jelly::factory('personalpreset')->get_permissions(!$read, $lchecked, $rchecked);
		$data = Jelly::factory('personalpreset')->group_by($data, !$read);

		$view = Twig::factory('permission/list');
		$view->edit = !$read;
		$view->permissions = $data;
		$view->just_checked = $just_checked;

		$this->request->response = JSON::reply($view->render());
	}

	public function action_update_personal(){

		$permissions = arr::get($_POST, 'perms', '');
		$permissions = @json_decode($permissions, true);

		$updated = array();

		foreach($permissions as $permission){
			$perm = Jelly::select('personalpreset')->load((int)$permission['id']);
			if(!$perm instanceof Jelly_Model || !$perm->loaded()){
				continue;
			}

			$key = ($perm->personal_is_group ? 'g' : 'n').$perm->personal;
			if(!array_key_exists($key, $updated))$updated[$key] = array();
			$updated[$key][] = $perm->id();

			$perm->permission = $permission['write'] ? Model_PersonalPreset::WRITE : ($permission['read'] ? Model_PersonalPreset::READ : Model_PersonalPreset::DENIED);
			$perm->save();

			$personal = $perm->personal;
			$personal_is_group = $perm->personal_is_group;
			$menu = $perm->menu_item;

//			$menu_subtree = Jelly::factory('farmpreset')->get_menu_subtree($menu);
//			$menu_subtree[] = $menu;
			$personal_subtree = Jelly::factory('glossary_personal')->get_personal_subtree($personal, $personal_is_group);
//			$personal_subtree[] = array('_id' => $personal, 'is_group' => $personal_is_group);

			$personal_subtree_g = array();
			$personal_subtree_n = array();
			foreach($personal_subtree as $p) {
				if($p['is_group']) $personal_subtree_g[] = $p['_id'];
				else				  $personal_subtree_n[] = $p['_id'];
			}
			if(!count($personal_subtree_g)) $personal_subtree_g = array(-1);
			if(!count($personal_subtree_n)) $personal_subtree_n = array(-1);

			$perms = Jelly::select('personalpreset')->where_open()
																  ->where_open()->where('personal', 'IN', $personal_subtree_g)->and_where('personal_is_group', '=', true)->where_close()
																  ->or_where_open()->where('personal', 'IN', $personal_subtree_n)->and_where('personal_is_group', '=', false)->or_where_close()
																  ->where_close()
																  ->and_where('menu_item', '=', $menu)->execute();
			foreach($perms as $p){
				if($p->permission>$perm->permission){
					$p->permission = $perm->permission;
				}
				$p->lock = $perm->permission==Model_PersonalPreset::WRITE ? Model_PersonalPreset::UNLOCK : ($perm->permission==Model_PersonalPreset::READ ? Model_PersonalPreset::LOCK_W : Model_PersonalPreset::LOCK);
				$p->save();
			}
		}

		$this->remove_access('personalpreset', $updated);

		$this->request->response = Json::success(array());
	}


	public function action_read(){
		return $this->action_edit(true);
	}

	public function action_edit($read = false){
		$view = Twig::factory('permission/list');
		$view->edit = false;
		$view->type = 'l';
		$view->permissions = array();
		$this->request->response = JSON::reply($view->render());
	}

	public function action_update(){
		$this->request->response = Json::success(array());
	}
	
	public function action_read_users(){
		return $this->action_read_user_list(true);
	}
	
	public function action_read_user_list($read = false){

		$type = arr::get($_GET, 'type', false);
		$lchecked = arr::get($_GET, 'l_checked', '');
		
		$rchecked = arr::get($_GET, 'r_checked', '');
		
		

		$lchecked = explode('_', $lchecked);
		if(isset($lchecked[0]) && !trim($lchecked[0])) $lchecked = array();
		$rchecked = explode('_', $rchecked);
		if(isset($rchecked[0]) && !trim($rchecked[0])) $rchecked = array();

		foreach($rchecked as &$val) $val = substr($val, 1);

		$data = Jelly::factory('personalpreset')->get_permissions($type, !$read, $lchecked, $rchecked);
		$data = Jelly::factory('personalpreset')->group_by($data, $type=='l' ? 'personal' : 'menu');

		$view = Twig::factory('permission/list');
		$view->edit = !$read;
		$view->type = $type;
		$view->permissions = $data;

		$this->request->response = JSON::reply($view->render());
	}





	public function action_read_user(){
		return $this->action_edit_user(true);
	}

	public function action_edit_user($read = false){

		$lchecked = arr::get($_GET, 'l_checked', '');
		
		preg_match_all('/g_personal_user_[0-9]{1,10}/ui', $lchecked, $lchecked);
		if(count($lchecked)) $lchecked = array_unique($lchecked[0]);
		
		//Licensee
		preg_match_all('/g_license_[0-9]{1,10}/ui', arr::get($_GET, 'l_checked', ''), $lchecked_license);
		if(count($lchecked_license)) $lchecked_license = array_unique($lchecked_license[0]);
		
		// Admins
		preg_match_all('/g_user_[0-9]{1,10}/ui', arr::get($_GET, 'l_checked', ''), $lchecked_admins);
		if(count($lchecked_admins)) $lchecked_admins = array_unique($lchecked_admins[0]);
		
		$lchecked = array_merge($lchecked, $lchecked_admins, $lchecked_license);
		
		
		$rchecked = arr::get($_GET, 'r_checked', '');
		
		$rchecked = explode('_', $rchecked);
		if(count($rchecked)) $rchecked = array_unique($rchecked);
		
		foreach($rchecked as &$r)
		{
			$r = substr($r, 1, strlen($r) - 1);
		}
		
		$just_checked = arr::get($_GET, 'just_checked', '');
		
		$just_checked = explode('_', $just_checked);
		if(count($just_checked)) $just_checked = array_unique($just_checked);
		
		foreach($just_checked as &$r)
		{
			$r = substr($r, 1, strlen($r) - 1);
		}
		

		if(isset($lchecked[0]) && !trim($lchecked[0])) $lchecked = array();
		if(isset($rchecked[0]) && !trim($rchecked[0])) $rchecked = array();
		if(isset($just_checked[0]) && !trim($just_checked[0])) $just_checked = array();

		/*
		foreach($lchecked as &$val1) $val1 = str_replace('g_personal_user_', '', $val1);
		foreach($lchecked as &$val1) $val1 = str_replace('g_user_', '', $val1);
		*/
		
		/*		
		foreach($rchecked as &$val1) $val1 = str_replace('g_personal_user_', '', $val1);
		foreach($just_checked as &$val1) $val1 = str_replace('g_personal_user_', '', $val1);
		*/
		
		if(!$read && !count($rchecked))
		{
			$rchecked = Jelly::factory('userpreset')->get_checked_menus('userpreset', 'user', $lchecked);
		}	

		$data = array();
		
		/*
		if((count($lchecked) && count($rchecked)) || $read)
		{
			*/
			$data = Jelly::factory('userpreset')->get_permissions($lchecked, $rchecked);
			$data = Jelly::factory('userpreset')->group_by($data, !$read);
		/*
		}*/
		
		//new AC_Profiler;

		$view = Twig::factory('permission/list');
		$view->edit = !$read;
		$view->permissions = $data;
		$view->just_checked = $just_checked;

		$this->request->response = JSON::reply($view->render());
	}

	public function action_update_user(){

		$permissions = arr::get($_POST, 'perms', '');
		$permissions = @json_decode($permissions, true);

		$updated = array();

		foreach($permissions as $permission){
			$perm = Jelly::select('userpreset')->load((int)$permission['id']);
			if(!$perm instanceof Jelly_Model || !$perm->loaded()){
				continue;
			}

			if(!array_key_exists($perm->user->id(), $updated))$updated[$perm->user->id()] = array();
			$updated[$perm->user->id()][] = $perm->id();

			$perm->permission = $permission['write'] ? Model_UserPreset::WRITE : ($permission['read'] ? Model_UserPreset::READ : Model_UserPreset::DENIED);
			$perm->save();

//			$user = $perm->user->id();
//			$menu = $perm->menu_item;
//			$menu_subtree = Jelly::factory('farmpreset')->get_menu_subtree($menu);
//			$perms = count($menu_subtree) ? Jelly::select('userpreset')->where('user', '=', $user)->and_where('menu_item', 'IN', $menu_subtree)->execute() : array();
//
//			foreach($perms as $p){
//				if($p->permission>$perm->permission){
//					$p->permission = $perm->permission;
//				}
//				$p->lock = $perm->permission==Model_UserPreset::WRITE ? Model_UserPreset::UNLOCK : ($perm->permission==Model_UserPreset::READ ? Model_UserPreset::LOCK_W : Model_UserPreset::LOCK);
//				$p->save();
//			}
		}

		//$this->remove_access('userpreset', $updated);

		$this->request->response = Json::success(array());
	}


	private function remove_access($preset_model, $updated){
		foreach($updated as $obj_id => $permissions) {

			$all_permissions = Jelly::select($preset_model);
			if($preset_model=='personalpreset'){
				$all_permissions = $all_permissions->where('personal', '=', (int)substr($obj_id, 1))->and_where('personal_is_group', '=', (substr($obj_id, 0,1)=='g'));
			}else{
				$all_permissions = $all_permissions->where(($preset_model=='farmpreset' ? 'farm' : 'user'), '=', $obj_id);
			}
			$all_permissions = $all_permissions->execute();

			foreach($all_permissions as $permission){
				if(array_search($permission->id(), $permissions)===false){
					$permission->permission =  Model_UserPreset::DENIED;
					$permission->save();
				}
			}
		}
	}

}