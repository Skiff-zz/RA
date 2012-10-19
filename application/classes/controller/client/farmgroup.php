<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_FarmGroup extends AC_Controller
{

	public $auto_render  = false;

	public function action_index()
	{
		$user = Auth::instance()->get_user();
		if(!($user instanceof Jelly_Model) or !$user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

	    $data =	Jelly::factory('group')->get_tree($user->id(), 0);

		$this->request->response = Json::arr($data, count($data));
	}




	public function action_list($ids = '')
	{
	  $elems = array();
	  foreach (explode('-', $ids) as $id)
	  {
	    $elems[] = (int)trim($id);
	  }

          if (count($elems)==1)
          {
              //согласно требованям тикета AGC-24
              return $this->action_edit($elems[0], true);
          }

	  $users = array(); $it = 0;
	  $addons = Jelly::select('field')->order_by('_id', 'ASC')->execute();
	  foreach (Jelly::select('user')->
                        where(':primary_key', 'IN', $elems)->
                        //EPIC hack - substr_count in mysql :)
                        order_by(db::expr('(LENGTH(`path`)-LENGTH(REPLACE(`path`,\'/\', \'\')))'), 'ASC')->
                        order_by('name', 'ASC')->
                        execute() as $user)
	  {
			$users[$it] = $user->as_array();


			$values = Jelly::select('value')->
                                    with('field')->
                                    where('user', '=', $user->id())->
                                    execute();

                        $v = array();
			foreach($values as $t){
				$v[$t->field->id()] = $t->value;
			}
			$r = array();
			foreach($addons as $a){
				$r[] = array('_id' 	=> $a->id(),
							 'name'	=> $a->name,
							 'value' => array_key_exists($a->id(), $v) ? $v[$a->id()] : '');
			}
			$users[$it]['global_addons'] = $r;

			$users[$it]['org_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user->id())->and_where('block', '=', 'organization')->execute()->as_array();
			$users[$it]['admin_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user->id())->and_where('block', '=', 'admin')->execute()->as_array();

			//цвета
			$format = Jelly::select('client_format')->where('farm', '=', $user->id())->and_where('name', '=', 'colors_count')->limit(1)->execute();
			$users[$it]['max_colors'] = ($format instanceof Jelly_Model && $format->loaded()) ? $format->value : 8;

			$it++;
	  }

        if (empty($users))
		{
			$this->request->response = JSON::error(__("User IDs not specified or wrong."));
			return;
		}

		$view = Twig::factory('client/user/list');
		$view->users = $users;
		$view->colors = Model_User::$colors;
		$this->request->response = JSON::reply($view->render());
	}

	public function action_read($id = null)
	{
		return $this->action_edit($id, true);
	}

	public function action_edit($id = null, $read = false, $parent_id = false)
	{

		$root_user = Auth::instance()->get_user();
		if(!($root_user instanceof Jelly_Model) or !$root_user->loaded()){
			$this->request->response = JSON::error(__("User ID is not specified"));
			return;
		}

        $group = null;

        if($id)
        {
            $group = Jelly::select('group')->with('parent')->load((int)$id);
            if(!($group instanceof Jelly_Model) or !$group->loaded()){
                $this->request->response = JSON::error('Не найдено Хозяйство!');
				return;
			}
        }

		if(!$read)
		{
			$view = Twig::factory('client/farm/edit');
			$view->edit			 	= true;

			$view->parent_id = $parent_id;
			$view->hasChildren = Jelly::select('group')->where('parent_id', '=', $id)->count() > 0;
		}
		else
		{
			$view = Twig::factory('client/farm/read');
		}	  


        if($group)
        {
			$view->farm 			= $group->as_array();
            //локальные доп. поля
            $user_uid = $group->id();
        }
        else
        {
       		$view->farm 			= array();
		}
        
        $view->farm['org_addons'] 			= Jelly::factory('client_model_properties')->get_properties('organization', $id);
		$view->farm['admin_addons'] 		= Jelly::factory('client_model_properties')->get_properties('admin', $id);
        
        /*
        $view->farm['org_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user_uid)->and_where('block', '=', 'organization')->and_where('farm_type', '=', 'group')->execute()->as_array();
$view->farm['admin_addons'] = Jelly::select('client_extraproperties')->where('farm', '=', $user_uid)->and_where('block', '=', 'admin')->and_where('farm_type', '=', 'group')->execute()->as_array();
		*/

		$format = Jelly::select('client_format')->where('farm', '=', $root_user->id())->and_where('name', '=', 'colors_count')->limit(1)->execute();
		$view->farm['max_colors'] = ($format instanceof Jelly_Model && $format->loaded()) ? $format->value : 8;

		$view->colors = Model_User::$colors;
		//$view->parent_list = Jelly::factory('user')->get_children_list($root_user->id(), $user ? $user->id() : 0);

		$this->request->response = JSON::reply($view->render());
	}

    public function action_create($parent_id)
    {
        if(array_key_exists(Jelly::meta('user')->primary_key(), $_POST))
            unset($_POST[Jelly::meta('user')->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }

	public function action_update()
	{
		$model_meta = Jelly::meta('user');
		//Редактирование или обновление



        if($primary_key = arr::get($_POST, $model_meta->primary_key(), NULL))
		{
			$values = array('name', 'address', 'phone', 'color', 'first_name', 'last_name', 'middle_name', 'username', 'password_text', 'parent');
			$model = Jelly::select('user', (int)$primary_key);
		}
		else
		{
			$values = array('name', 'address', 'phone', 'color', 'first_name', 'last_name', 'middle_name', 'username', 'password_text', 'email', 'parent');
			$model = Jelly::factory('user');
			$model->username = 'ac'.text::random('alpha', 6);

			//TMP
			$parent = Jelly::select('user')->load(Arr::get($_POST,'parent', 0));
			if(!($parent instanceof Jelly_Model) or !$parent->loaded()){
                $this->request->response = JSON::error('Родительское хозяйство не найдено!');
				return;
			}
			$childrenCount = Jelly::select('user')->where('parent', '=', Arr::get($_POST,'parent', 0))->count();
			$model->number = $parent->number.($childrenCount+1);
			$model->activate_date	= $parent->activate_date;
			$model->expire_date	    = $parent->expire_date;
			$model->max_ms	        = $parent->max_ms;
			//$model->max_pc	        = $parent->max_pc;
			$model->max_users	    = $parent->max_users ? $parent->max_users : 1;
			$model->square	        = $parent->square;

			if(!Arr::get($_POST,'password_text', NULL))
			{
				$this->request->response = JSON::error('Не задан пароль!');
				return;
			}
		}

		$model->update_date = time();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);

		if(Arr::get($_POST,'password_text',NULL))
		{
			$_POST['password_confirm'] = $_POST['password'] = $_POST['password_text'];

			$values[]='password';
			$values[]='password_text';
			$values[]='password_confirm';
		}

		$model->set(Arr::extract($_POST, $values));

		$model->deleted = 0;
		//$model->is_root = 0;

		$model->save();
		$primary_key = $model->id();

		$model->set_status();

		/*
		//локальные доп. поля
		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'org_add_field_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('org_add_field_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'org_insert_field_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'org_name_insert_'.UTF8::str_ireplace('org_insert_field_', '', $key),''),
								  'value' => $value);
			}
		}
		Jelly::factory('client_extraproperties')->updateOldFields((int)$primary_key, 'organization', $addons);

		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'adm_add_field_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('adm_add_field_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'adm_insert_field_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'adm_name_insert_'.UTF8::str_ireplace('adm_insert_field_', '', $key),''),
								  'value' => $value);
			}
		}
		Jelly::factory('client_extraproperties')->updateOldFields((int)$primary_key, 'admin', $addons);
		*/
		
		Jelly::factory('client_model_properties')->update_properties('organization', $_POST, 'org', $model->id());
		Jelly::factory('client_model_properties')->update_properties('admin', $_POST, 'adm', $model->id());

		/*
		//глобальные доп.поля
		$addons = Jelly::select('field')->execute();
		foreach($addons as $add) {
			$val = arr::get($_POST,'add_field_'.$add->id(),NULL);
			if(!is_null($val)){
				$a = Jelly::select('value')->where('user', '=', (int)$primary_key)->and_where('field', '=', $add->id())->limit(1)->execute();
				if(!($a instanceof Jelly_Model) or !$a->loaded()){
					$a = Jelly::factory('value');
				}
				$a->user = (int)$primary_key;
				$a->field = $add->id();
				$a->value = $val;
				$a->save();
			}
		}*/

		$this->request->response = JSON::reply("Лицензиат сохранен успешно!");
	}

}
