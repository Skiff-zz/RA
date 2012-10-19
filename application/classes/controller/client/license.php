<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_License extends AC_Controller
{

	public $auto_render  = false;

	
	public function action_read(){

		$user = Auth::instance()->get_user();

		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

		$user = Jelly::select('user')->with('license')->load($user->license->user->id());
		
		$view = Twig::factory('client/license/read');

		$view->user 	= $user->as_array('address_country', 'address_region', 'address_city', 'address_zip', 'address_street', 'phone', 'last_name', 'first_name', 'middle_name', 'email', 'username', 'password_text');
		$view->license  = $user->license->as_array('name', 'number', 'max_users', 'max_fields', 'square', 'status');
		
    // if($user->license->manual==1){ $stat = $user->license->status + 3; }
    // else{ $stat = $user->license->status; }
    // 
    // switch($stat)
    // {
    //  case 1:  $view->status_txt = 'Лицензия активна (авто)'; break;
    //  case 2:  $view->status_txt = 'Обратить внимание (авто)'; break;
    //  case 3:  $view->status_txt = 'Лицензия заблокирована (авто)'; break;
    //  case 4:  $view->status_txt = 'Лицензия активна'; break;
    //  case 5:  $view->status_txt = 'Обратить внимание'; break;
    //  case 6:  $view->status_txt = 'Лицензия заблокирована'; break;
    //  default: $view->status_txt = 'Не определено'; break;
    // }

		//глобальные доп.поля
		$addons = Jelly::select('licenseproperty')->order_by('_id', 'ASC')->execute();
		$v = array();
		$values = Jelly::select('licensepropertyvalue')->with('field')->where('license', '=', $user->license->id())->execute();
		foreach($values as $t){
			$v[$t->field->id()] = $t->value;
		}
		$r = array();
		foreach($addons as $a){
			$r[] = array('_id' 	=> $a->id(),
						 'name'	=> $a->name,
						 'value' => array_key_exists($a->id(), $v) ? $v[$a->id()] : '');
		}
		$view->global_addons = $r;

		$view->count_fields		= (int)$user->license->get_fields_count();
		$view->count_fields_sq	= (float)$user->license->get_square();
		$view->count_subuser	= (int)$user->license->get_subuser_count();

		$view->activate_date 	= array('day' => date('d', (int)$user->license->activate_date), 'month' => date('m', (int)$user->license->activate_date), 'year' => date('Y', (int)$user->license->activate_date) );
		$view->expire_date 		= array('day' => date('d', (int)$user->license->expire_date), 'month' => date('m', (int)$user->license->expire_date), 'year' => date('Y', (int)$user->license->expire_date) );

		setlocale(LC_NUMERIC, 'C');
		$this->request->response = JSON::reply($view->render());
	}

}
