<?php defined('SYSPATH') or die('No direct script access.');
/**
* 
*/
class Controller_Email extends Controller
{
	public function action_index()
    {
        $user = Jelly::select('user', 100);
           
        $email =  Twig::factory('user/email');

		$from_email = (string)Kohana::config('application.from_email');

		$email->contact = $user->email;
		$email->user = $user->as_array();
        $email->activate_date = date('d.m.Y', (int)$user->activate_date);
        $email->expire_date = date('d.m.Y', (int)$user->expire_date);
		$email->link = Kohana::config('application.root_url').'client/login?license='.$user->number;

		Email::connect();
		Email::send('sergei@creatiff.com.ua',
					(string)$from_email,
					(string)'Ваша Учетная Запись в системе АгроКлевер',
					(string)$email->render(),
					 true,
					 array($_SERVER['DOCUMENT_ROOT'].'/media/Agro Clever login page.website'));
 	}
	
}
