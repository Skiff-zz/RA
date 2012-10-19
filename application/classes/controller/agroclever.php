<?php defined('SYSPATH') or die('No direct script access.');

class Controller_AgroClever extends AC_Controller
{
	protected $request_auth = true;
	public $auto_render = false;

	public function action_index()
	{
		$user = Auth::instance()->get_user();
		
		if($user and !$user->is_root)
		{
			Auth::instance()->logout(true);
			Request::instance()->redirect('/admin/login');
			return;
		}
		
		$view = View::factory('index');
		$this->request->response = $view->render();
	}
	
	public function action_build_tree()
	{
		$users = Jelly::select('user')->where('parent', 'IS', null)->or_where('parent', '=', 0);
		
		foreach($users as $user)
		{
			$user->path = '';
			$user->save();
			$this->_append($user);
		}
		
	}
	
	private function _append($user)
	{
		$children = Jelly::select('user')->where('parent', '=', $user->id());
		
		foreach($children as $child)
		{
			$child->path = $user->path.'/'.$user->id().'/';
			$this->_append($child);
		}
	}
	
} // End Welcome
