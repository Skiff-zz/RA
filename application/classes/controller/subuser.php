<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Subuser extends AC_Controller
{
	public    $auto_render  = false;

	public function action_list($id) {
		if($id and (int)$id > 0) {
			
			$id = intval($id);
			
			// Не забываем про удаленных ;)
			$user = Jelly::select('user')->where('deleted', '=', 0)->load($id);
			
			if ( ($user instanceof Jelly_Model) and $user->loaded() ) {
				$subusers = Jelly::select('subuser')->where('farm', '=', $id)->where('deleted', '=', 0)->execute();
				
				$view = Twig::factory('subuser/list');
				$view->user_id 	= $user->id();
				$view->licensee = $user->name;
				$view->subusers = $subusers->as_array();

				$this->request->response = JSON::reply($view->render());
			} else {
				$this->request->response = JSON::error(__("No such user"));
			}
		} else {
			$this->request->response = JSON::error(__("Lisensee ID is not specified"));
		}
	}

	public function action_create($id) {
		if($id and (int)$id > 0) {
			
			$user = Jelly::select('user')->where('deleted', '=', 0)->load($id);
			
			if ( ($user instanceof Jelly_Model) and $user->loaded() ) {
				$view = Twig::factory('subuser/create');
				$view->user_id 	= $user->id();
				$view->edit		= true;
				$time = time();
				$view->birth_date 	= array('day' => date('d', $time), 'month' => date('m', $time), 'year' => date('Y', $time) );

				$this->request->response = JSON::reply($view->render());
			} else {
				$this->request->response = JSON::error(__("No such user"));
			}
		} else {
			$this->request->response = JSON::error(__("Subuser ID is not specified"));
		}
	}


	public function action_read($id) {
		return $this->action_edit($id, false);
	}

	public function action_edit($id, $edit = true) {
		if((int)$id > 0) {
			$subuser = Jelly::select('subuser')->where('deleted', '=', 0)->load($id);
			if ( ($subuser instanceof Jelly_Model) and $subuser->loaded() ) {
				$view = Twig::factory('subuser/read');
				$view->user_id		= Arr::get($_GET, 'user_id', null);
				$view->subuser		= $subuser->as_array();

				$view->emails = array();
				foreach($subuser->emails as $email){
					$view->emails[]	= $email->as_array();
				}

				$view->dates = array();
				foreach($subuser->dates as $date){
					$view->dates[]	= $date->as_array();
				}

				$view->phones = array();
				foreach($subuser->phones as $phone){
					$view->phones[]	= $phone->as_array();
				}

				$view->addresses = array();
				foreach($subuser->addresses as $address){
					$view->addresses[]	= $address->as_array();
				}

				$view->notes = array();
				foreach($subuser->notes as $note){
					$view->notes[]	= $note->as_array();
				}
				
				$view->edit		= $edit;
				$time = strtotime($subuser->birth_date);
				$view->birth_date   = array('day'   => date("d", $time), 'month' => date("m", $time), 'year'  => date("Y", $time));

				//print_r($view->subuser); exit;
				$this->request->response = JSON::reply($view->render());
			} else {
				$this->request->response = JSON::error(__("No such user"));
			}
		} else {
			$this->request->response = JSON::error(__("Subuser ID is not specified"));
		}
	}
	

	private function subuser_save($subuser, $edit = true) {
		$values = array('first_name', 'last_name', 'middle_name', 'password', 'login', 'position');
		$infos = array('emails', 'phones', 'addreses', 'dates', 'notes');

		$subuser->update_date = time();
		$changed = Arr::extract($_POST, $values);
		$infos = Arr::extract($_POST, $infos);
		
		$subuser->set($changed);

		/*if(Arr::get($_POST, 'birth_date', null)) {
			$time = ACDate::convertMonth($_POST['birth_date']);
			$subuser->birth_date = date("Y-m-d", strtotime($time));
			unset($_POST['birth_date']);
		}*/
		if($user_id = Arr::get($_POST, 'user_id', null)) {
			$subuser->farm	= $user_id;
			unset($_POST['user_id']);
		}
		$subuser->is_active = true;

		try{
			$subuser->save();
			$infos = $this->prepareInfo($infos);

			//Jelly::factory('subuser')->setSubuserInfo($subuser->id(), $infos, true);

			if ($edit) {
				$this->request->response = JSON::reply("Информация о пользователе успешно обновлена", '/subuser/read/' . $subuser->id() . '/?user_id=' . $user_id);
			} else {
				$this->request->response = JSON::reply("Информация о пользователе успешно обновлена", '/subuser/list/' . $user_id);
			}


		} catch(Validate_Exception $e) {
			//$this->request->response = JSON::error($e->getMessage());
			$this->request->response = JSON::error(implode(' ', $e->array->errors('validate',true)));
		}

	}

	public function action_delete() {
		$id = Arr::get($_POST, 'subuser_id', null);
		
		if($id) {
			$subuser = Jelly::select('subuser')->where('deleted', '=', 0)->load((int)$id);
			
			if(($subuser instanceof Jelly_Model) and $subuser->loaded()) {
				$subuser->deleted = 1;
				$subuser->update_date = time();
				$subuser->save();
				$this->request->response = JSON::reply("Пользователь удален");
			} else {
				$this->request->response = JSON::error(__("No such subuser"));
			}
		} else {
			$this->request->response = JSON::error(__("Subuser ID is not specified"));
		}
	}
	
	public function action_update() {
		$id = Arr::get($_POST, 'subuser_id', null);
		
		if($id) {
			if((int)$id>0){
				$subuser = Jelly::select('subuser')->where('deleted', '=', 0)->load((int)$id);

				if(($subuser instanceof Jelly_Model) and $subuser->loaded()) {
					$this->subuser_save($subuser);
				} else {
					$this->request->response = JSON::error(__("No such user"));
				}
			}else{
				$this->request->response = JSON::error(__("No such user"));
			}
		} else {
			$subuser = Jelly::factory('subuser');
			$this->subuser_save($subuser, false);
		}
	}
	
	
	public function action_admin($id) {
		return $this->action_adminedit($id, false);
	}

	public function action_adminedit($id, $edit = true) {
		if($id) {
			$user = Jelly::select('user')->load($id);
			if ( ($user instanceof Jelly_Model) and $user->loaded() ) {
				$view = Twig::factory('subuser/admin');
				$view->user_id 		= $user->id();
				$view->licensee 	= $user->name;
				$view->admin		= true;
				$view->edit			= $edit;
				$view->subuser		= $user->as_array();
				$time = strtotime($user->birth_date);
				$view->birth_date   = array('day'   => date("d", $time), 'month' => date("m", $time), 'year'  => date("Y", $time));
		
				$this->request->response = JSON::reply($view->render());
			} else {
				$this->request->response = JSON::error(__("No such user"));
			}
		} else {
			$this->request->response = JSON::error(__("Licensee ID is not specified"));
		}
	}

	public function action_adminupdate() {
		$id = Arr::get($_POST, 'user_id', null);

		if($id) {
			$user = Jelly::select('user')->where('deleted', '=', 0)->load($id);
			
			if(($user instanceof Jelly_Model) and $user->loaded()) {
				$values = array('first_name', 'last_name', 'middle_name', 'birth_date', 'password_text', 'username', 'position', 'notes', 'email');
		
				$user->update_date = time();
				$user->set(Arr::extract($_POST, $values));
				if(Arr::get($_POST, 'birth_date', null)) {
					$time = ACDate::convertMonth($_POST['birth_date']);
					$user->birth_date = date("Y-m-d", strtotime($time));
					unset($_POST['birth_date']);
				}

				try {
					$user->save();
					$this->request->response = JSON::reply("Информация о пользователе успешно обновлена", '/subuser/admin/' . $user->id());
				} catch(Validate_Exception $e) {
					//$this->request->response = JSON::error($e->getMessage());
					$this->request->response = JSON::error(implode(' ', $e->array->errors('validate',true)));
				}

			} else {
				$this->request->response = JSON::error(__("No such user"));
			}
			
		} else {
			$this->request->response = JSON::error(__("User ID is not specified"));
		}
	}


	private function prepareInfo($infos){
	    $result = array();
	    $info_types = array('emails', 'phones', 'addreses');

	    foreach($info_types as $info_type){
		if(isset($infos[$info_type])){
		    $result[$info_type] = array();
		    $infos[$info_type] = explode(" ", $infos[$info_type]);
		    foreach($infos[$info_type] as $inf){
			if(trim($inf)==""){ continue; }
			$result[$info_type][] = trim($inf);
		    }
		}
	    }

	    if(isset($infos['dates'])){
		$result['dates'] = array();
		$infos['dates'] = explode(";", $infos['dates']);
		foreach($infos['dates'] as $d){
		    $arr = explode("::", $d);
		    if(count($arr)!=2) { continue; }
		    $time = ACDate::convertMonth($arr[1]);
		    $result['dates'][] = array('date'=>date("Y-m-d", strtotime($time)),'label'=>$arr[0]);
		}
	    }

	    return $result;
	}

}