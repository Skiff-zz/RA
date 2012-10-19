<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Cron extends Controller
{
	
	public    $auto_render  = false;
	
	public function action_user_status()
	{
		$users = Jelly::select('user')->where('is_root', '=', 0)->where('deleted', '=', 0)->where('manual', '=', 0)->execute();
		
		foreach($users as $user) 
		{
			$user->set_status();
		}
	}
	
} // End Welcome
