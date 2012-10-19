<?php defined('SYSPATH') or die('No direct script access.');
/**
* 
*/
class Controller_Auth extends Controller
{
	
	public function action_login(){
				
		$this->request->response = $this->template->render();
	}
	
	public function action_logout()
	{
		Auth::instance()->logout(true);
		Request::instance()->redirect('/admin/');
	}

}
