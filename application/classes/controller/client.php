<?php
defined('SYSPATH') or die('No direct script access.');

class Controller_Client extends Controller
{
	protected $request_auth = false;
	public $auto_render = true;

	public function action_index()
	{
		$view = Twig::factory('index');
		$this->request->response = $view->render();

	}

	public function action_login()
	{

		//Если пользоавтель уже авторизирован
		if (Auth::instance()->logged_in())
		{
			Request::instance()->redirect('/');
		}
		
		$this->template = Twig::factory('client/login');

		if (isset($_GET['license'])) //если первый вход по ссылке с мыла

		{
			$first_login = Twig::factory('client/first_login1');

			$license = Jelly::select('license')->with('user')->where('number', '=', $_GET['license'])->limit(1)->execute();
			if ($license instanceof Jelly_Model and $license->loaded())
			{
				if ((int)$license->user->last_login > 0)
				{
					$remember = (int)Cookie::get('remember', 1);
					$first_login = Twig::factory('client/second_login');
					$first_login->remember = $remember;
				}
			}
		}
		else //если не первый вход

		{
			if (isset($_POST['lic_number']) && (! Arr::get($_POST, 'login', '')))
			{
				$_POST['lic_number'] = str_replace(' ', '', $_POST['lic_number']);
				$license = Jelly::select('license')->where('number', '=', $_POST['lic_number'])->limit(1)->execute();
				if ($license instanceof Jelly_Model and $license->loaded() and $license->status != Model_License::STATUS_STOP)
				{
					$first_login = Twig::factory('client/first_login2');
					$first_login->show_form = true;
					$first_login->lic_number = $_POST['lic_number'];
				}
				else
				{
					$first_login = Twig::factory('client/first_login1');
					$first_login->lic_number = ''; //$_POST['lic_number'];
					if (! $_POST['lic_number'])
						$this->template->__errors[] = array('name' => 'lic_number', 'msg' => 'Введите номер лицензии');
					else
						$this->template->__errors[] = array('name' => 'lic_number', 'msg' => 'Некорректный номер лицензии');
				}
			} elseif (isset($_POST['license']))
			{
				$login = arr::get($_POST, 'login', null);
				$password = arr::get($_POST, 'password', null);

				$licensee = Jelly::select('user')->with('license')->where('username', '=', $login)->and_where('deleted', '=', 0)->limit(1)->execute();

				$remember = (bool)Arr::get($_POST, 'remember', false);

				if (($licensee instanceof Jelly_Model) && ($licensee->loaded()) && ($licensee->license->number == str_replace(' ', '', $_POST['license'])) && ($licensee->license->status != Model_License::STATUS_STOP) && (Auth::instance()->login($login, $password, $remember)))
				{

					if (! $remember)
					{
						Jelly::delete('user_token')->where('user', '=', Auth::instance()->get_user()->id())->execute();
					}
					
					Cookie::set('remember', (int)$remember, 2*24*3600);

					setcookie("last_login", $login, time() + 3600 * 24 * 14, '/');
					Request::instance()->redirect('/');
				}
				else
				{
					$first_login = Twig::factory('client/first_login2');
					$first_login->show_form = true;
					$first_login->lic_number = arr::get($_POST, 'license', '');
					$first_login->login = arr::get($_POST, 'login', '');
					$first_login->password = '';

					if (! $login)
					{
						$this->template->__errors[] = array('name' => 'login_input', 'msg' => 'Введите свой логин');
					} elseif (! $password)
					{
						$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Введите свой пароль');
					}
					else
					{
						$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Некорректный логин или пароль');
					}
				}
			}
			//elseif(!isset($_POST['license']) && isset($_POST['login']))
			elseif (isset($_POST['login']))
			{
				$login = arr::get($_POST, 'login', null);
				$password = arr::get($_POST, 'password', null);

				$first_login = Twig::factory('client/second_login');
				
				$remember = (bool)Arr::get($_POST, 'remember', false);
				Cookie::set('remember', (int)$remember, 2*24*3600);
				
				$first_login->remember = $remember;
				
				$first_login->login = $login; //arr::get($_POST,'login','');
				$first_login->password = ''; //arr::get($_POST,'password','');

				if (! $login)
				{
					$this->template->__errors[] = array('name' => 'login_input', 'msg' => 'Введите свой логин');
				} elseif (! $password)
				{
					$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Введите свой пароль');
				}
				else
				{
					$licensee = Jelly::select('user')->with('license')->where('username', '=', $login)->and_where('password_text', '=', $password)->and_where('deleted', '=', 0)->limit(1)->execute();
					
					if($licensee instanceof Jelly_Model and $licensee->loaded() and (!$licensee->license->is_active or (int)$licensee->license->expire_date < time()))
					{
						Auth::instance()->logout(true);
						
						$this->template = Twig::factory('client/login');
						
						$first_login = Twig::factory('client/second_login');
						$first_login->login = Arr::get($_POST, 'login');
						
						$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Срок действия лицензии истек');
						
						$this->template->content_block = $first_login;
						$this->request->response = $this->template->render();
						
						$licensee->license->is_active = 0;
						$licensee->license->save();
						
						$email = Twig::factory('user/email_license_exired');
						
						$from_email = (string)Kohana::config('application.from_email');
			
						$email->contact = $licensee->email;
						$email->user = $licensee->as_array();
					    $email->activate_date = date('d.m.Y', (int)$licensee->license->activate_date);
					    $email->expire_date = date('d.m.Y', (int)$licensee->license->expire_date);
								
						Email::connect();
						Email::send($licensee->email,
									(string)$from_email,
									(string)'Срок действия Вашей лицензии в системе АгроКлевер истек',
									(string)$email->render(),
									 true);
						
						return;
					}
					
					
					
					if ($licensee instanceof Jelly_Model and $licensee->loaded() and ! $licensee->license->user->last_login and ! isset($_POST['lic_number']) and ($licensee->license->status != Model_License::STATUS_STOP))
					{
						//Request::instance()->redirect('/client/login?license='.$licensee->license->number);
						$first_login = Twig::factory('client/first_login1');
						$first_login->login = arr::get($_POST, 'login', '');
						$first_login->password = arr::get($_POST, 'password', '');
					} elseif (($licensee->license->deleted) || ((Arr::get($_POST, 'lic_number', null)) && (str_replace(' ', '', $_POST['lic_number']) != $licensee->license->number)) || ($licensee->license->status == Model_License::STATUS_STOP))
					{
						$first_login = Twig::factory('client/first_login1');
						$first_login->login = arr::get($_POST, 'login', '');
						$first_login->password = arr::get($_POST, 'password', '');
						$this->template->__errors[] = array('name' => 'lic_number', 'msg' => trim(Arr::get($_POST, 'lic_number', null)) ? 'Некорректный номер лицензии' : 'Введите номер лицензии');
					} elseif (($licensee->license->deleted) || ((isset($_POST['license'])) && (str_replace(' ', '', $_POST['lic_number']) != $licensee->license->number)) || ($licensee->license->status == Model_License::STATUS_STOP))
					{
						$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Некорректный логин или пароль');
					} elseif (isset($_POST['show_info']))
					{
						$first_login = Twig::factory('client/first_login2');
						$first_login->lic_number = arr::get($_POST, 'lic_number', '');
						$first_login->login = arr::get($_POST, 'login', '');
						$first_login->password = arr::get($_POST, 'password', '');
					}
					else
					{
						$remember = (bool)Arr::get($_POST, 'remember', false);

						$login_result = Auth::instance()->login($login, $password, $remember);

						if ($login_result)
						{
							if (! $remember)
							{
								Jelly::delete('user_token')->where('user', '=', Auth::instance()->get_user()->id())->execute();
							}
							
							Cookie::set('remember', (int)$remember,  2*24*3600);

							setcookie("last_login", $login, time() + 3600 * 24 * 14, '/');
							Request::instance()->redirect('/');
						}
						else
						{
							$this->template->__errors[] = array('name' => 'password_input', 'msg' => 'Некорректный логин или пароль');
						}
					}
				}
			}
			else
			{
				$first_login = Twig::factory('client/second_login');
				
				$remember = (int)Cookie::get('remember', 1);
				$first_login->remember = $remember;
				
				$first_login->login = arr::get($_POST, 'login', '');
			}
		}

		$this->template->content_block = $first_login;

		$this->request->response = $this->template->render();
	}

	public function action_logout()
	{
		Auth::instance()->logout(true);
		Request::instance()->redirect('/login');
	}
}
