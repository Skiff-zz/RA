<?php
class Model_User extends Model_Auth_User
{

      public static $colors = array(
            '000000', //#0
            '0000AA',
            '00AA00',
            '00AAAA',
            'AA0000',
            'AA00AA', //#5
            'AA5500',
            'AAAAAA',
            '555555',
            '5555FF',
            '55FF55', //#10
            '55FFFF',
            'FF5555',
            'FF55FF',
            'FFFF55',
            'FFFFFF', //#15
      );


	public static function initialize(Jelly_Meta $meta)
	{
		parent::initialize($meta);

		//Костыль начало
		$fields = $meta->fields;
		unset($fields['id'],$fields['roles'],$fields['tokens']);
		$meta->fields = $fields;
		//Костыль конец


		$meta->table('users')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							// Активен ли пользователь (0 - не активен, 1 - активен
							'is_active'		=> Jelly::field('Boolean', array('label' => 'Активен')),
							'is_root'		=> Jelly::field('Boolean', array('label' => 'Суперадминистратор')),
							'deleted'		=> Jelly::field('Boolean', array('label' => 'Удалено')),
							'manager'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'manager_id',
														'label'		=> 'Менеджер бэк-офиса',
													)),
							'license'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',
													)),
                            'role'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'role',
														'column'	=> 'role_id',
														'label'		=> 'Роль',
													)),

							'first_name'	=> Jelly::field('String', array('label' => 'Имя',
								'rules'  => array(
								    'max_length' => array(1000)
								))),
							'last_name'		=> Jelly::field('String', array('label' => 'Фамилия',
								'rules'  => array(
								    'max_length' => array(1000)
								))),
							'middle_name'	=> Jelly::field('String', array('label' => 'Отчество',
								'rules'  => array(
								    'max_length' => array(1000)
								))),
							'notes'			=> Jelly::field('Text', array('label' => 'Заметки')),
							'position'		=> Jelly::field('String', array('label' => 'Должность')),
							'birth_date'	=> Jelly::field('String', array('label' => 'Дата рождения')),
							'password_text'		=> Jelly::field('String', array('label' => 'Пароль')),


							'color' => Jelly::field('String', array('label' => 'Цвет',
								'rules'  => array(
								    'max_length' => array(6),
								    'regex' => array('/^[a-fA-F0-9]+$/ui')
								))),

							//адрес
							'address_country'	    => Jelly::field('String', array('label' => 'Страна')),
							'address_region'		=> Jelly::field('String', array('label' => 'Область')),
							'address_city'			=> Jelly::field('String', array('label' => 'Город')),
							'address_zip'			=> Jelly::field('String', array('label' => 'Индекс')),
							'address_street'		=> Jelly::field('String', array('label' => 'Улица и дом')),

							'phone'					=> Jelly::field('String', array('label' => 'Телефон')),
							'settings'				=> Jelly::field('Text', array('label' => 'Сохраненные настройки')),

							'personal'  => Jelly::field('BelongsTo',array(
								'foreign'	=> 'glossary_personal',
								'column'	=> 'personal_id',
								'label'		=> 'Должность',
							)),
							'farm'                         => Jelly::field('BelongsTo',array(
								'foreign'	=> 'farm',
								'column'	=> 'farm_id',
								'label'		=> 'Хозяйство',
							))

							//ВАРИАНТ ДЛЯ ЛУЧШИХ ВРЕМЁН
//							//добавленные из справочника
//							'personal_card_number' => Jelly::field('String', array('label' => 'Личная карточка №')),
//							'personal_card_date'	 => Jelly::field('Integer', array('label' => 'Дата открытия личной карточки')),
//							'birth_place'				  => Jelly::field('String', array('label' => 'Место рождения')),
//							'farm'                         => Jelly::field('BelongsTo',array(
//								'foreign'	=> 'farm',
//								'column'	=> 'farm_id',
//								'label'		=> 'Хозяйство',
//							))
			));
	}

	public function save($key = null)
	{
		$res = parent::save($key);

		/*
		if(!$key)
		{
			$menus = Jelly::factory('farmpreset')->menu;
			foreach($menus as $menu)
			{
				$perm = Jelly::select('userpreset')->where('user', '=', $this->id())->and_where('menu_item', '=', $menu['id'])->load();
				if(!($perm instanceof Jelly_Model) or !$perm->loaded())
				{
					$perm = Jelly::factory('userpreset');
					$perm->user = $this->id();
					$perm->menu_item = $menu['id'];
					$perm->menu_name = $menu['title'];
					$perm->permission = Model_PersonalPreset::DENIED;
					$perm->lock = Model_PersonalPreset::UNLOCK;
					$perm->save();
				}
			}
		}*/

		return $res;
	}

	public function get_settings()
	{
		if($this->settings == '')
			return array();

		$arr = @unserialize($this->settings);

		if(!is_array($arr))
			return array();

		return $arr;
	}

	public function save_settings($arr)
	{
		if(!is_array($arr))
		{
			return false;
		}

		$this->settings = serialize($arr);
		$this->save();
	}

	public function get_subuser_count() {
		if($this->loaded()){
			$count = Jelly::select('subuser')->where('farm', '=', $this->_id)->and_where('deleted', '=', 0)->count();
			return $count ? $count : false;
		}
		else{
			return false;
		}
	}



	protected function parse_tree_result($level = 0)
	{
	  return array(
	      'id' => $this->id(),
	      'name' => $this->name,
	      'title' =>  $this->name,
	      'link' => $this->username,
	      'level' => $level,
	      'has_leaf' => ($level==0)?true:(bool)((int)$this->get('children_count')),
	      'children' => array(),
	      'color'   =>  $this->color,
	    );
	}

	public function get_tree($license_id){
		$result = array();
		$current_user = Auth::instance()->get_user();

		$users = Jelly::select('user')->where('license', '=', $license_id)->and_where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->and_where_close()->where('is_active', '=', 1)->execute()->as_array();
		$farms = Jelly::factory('farm')->get_full_tree($license_id);

		$not_license_users_ids = array();
		$license_users = array();
		$license_users_ids = array();
		$dont_remove = '';

		for($i=count($farms)-1; $i>=0; $i--){
			$farm_users = array();
			$farm_users_ids = array();
			foreach($users as $user) {
				$farm = Jelly::select('farm', (int)substr($farms[$i]['id'], 1));
				$fullname = trim($user['first_name'].' '.$user['middle_name'].' '.$user['last_name']);

				if(($user['farm']==substr($farms[$i]['id'], 1) || $farm->admin->id()==$user['_id']) && $current_user->license->user->id()!=$user['_id']){

					$farm_users_ids[] = 'n'.$user['_id'];
					$farm_users[] = array(
						'id'	   => 'n'.$user['_id'],
						'title'    => $fullname ? $fullname : ($user['username'] ? $user['username'] : $user['email']),
						'is_group' => true,
						'is_group_realy' => false,
						'level'	   => $farms[$i]['level']+1,
						'children_g' => array(),
						'children_n' => array(),
						'parent'   => str_replace('n', 'g', $farms[$i]['id']),
						'color'    => $user['color'],
						'parent_color' => $farms[$i]['color'],
					);
				}else{
					if(array_search('n'.$user['_id'], $license_users_ids)===false){
						$license_users_ids[] = 'n'.$user['_id'];
						$license_users[] = array(
							'id'	   => 'n'.$user['_id'],
							'title'    => $fullname ? $fullname : ($user['username'] ? $user['username'] : $user['email']),
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => 1,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => 'glic',
							'color'    => $user['color'],
							'parent_color' => '55a7fa',
						);
					}
				}
			}

			if(count($farm_users)){
				$farms[$i]['id'] = str_replace('n', 'g', $farms[$i]['id']);
				$farms[$i]['children_g'] = array_merge($farms[$i]['children_g'], $farm_users_ids);
				$farms[$i]['children_n'] = array();
				$farms[$i]['is_group_realy'] = true;
				array_splice($farms, $i+1, 0, $farm_users);
				$dont_remove =$farms[$i]['parent'];
				$not_license_users_ids  = array_merge($not_license_users_ids, $farm_users_ids);
			}else{
				if($dont_remove==$farms[$i]['id']){
					$dont_remove =$farms[$i]['parent'];
					$farms[$i]['children_n'] = array();
					foreach($farms[$i]['children_g'] as &$child){
						$child = str_replace('n', 'g', $child);
					}
				}
				else array_splice($farms, $i, 1);
			}
		}

		for($i=count($license_users)-1; $i>=0; $i--){
			if(array_search($license_users[$i]['id'], $not_license_users_ids)!==false){
				array_splice($license_users, $i, 1);
				array_splice($license_users_ids, $i, 1);
			}
		}

		if(count($license_users)){
			$farms[] = array(
				'id'	   => 'glic',
				'title'    => $current_user->license->name,
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => 0,
				'children_g' => $license_users_ids,
				'children_n' => array(),
				'parent'   => '',
				'color'    => '55a7fa',
				'parent_color' => 'FFFFFF',
			);
			$farms = array_merge($farms, $license_users);
		}

		return $farms;
	}

	public static function get_special_permissions($users, $menu = null)
	{

		$matrix = array();
		
		for($i = 0; $i < $k = count($users); $i++)
		{
			/*
				foreach($lchecked as &$val1) $val1 = str_replace('g_personal_user_', '', $val1);
				foreach($lchecked as &$val1) $val1 = str_replace('g_user_', '', $val1);
			*/
			if(is_numeric($users[$i]))
			{
				$user = Jelly::select('user', (int)$users[$i]);
			}
			else if(strpos($users[$i], 'g_personal_user_') !== false)
			{
				$real_id = (int)str_replace('g_personal_user_', '', $users[$i]);
				
				$personal = Jelly::select('Client_Handbook_Personal')->with('user')->where('deleted', '=', 0)->where('_id', '=', $real_id)->load();
			
				if(!($personal instanceof Jelly_Model) or !$personal->loaded())
				{
					continue;
				}
				
				$user = $personal->user;	
				
			}
			else if(strpos($users[$i], 'g_user_') !== false)
			{
				$real_id = (int)str_replace('g_user_', '', $users[$i]);
				$user = Jelly::select('user', (int)$real_id);
			}
			else 
			{
				$real_id = (int)str_replace('g_license_', '', $users[$i]);
				
				$license = Jelly::select('license', $real_id);
				
				if($license->id() != Auth::instance()->get_user()->license->id())
					throw new Kohan_Exception('Same license origin policy');
				
				$user = $license->user;
			}
			
			if(!($user instanceof Jelly_Model) or !$user->loaded() or $user->deleted)
			{
				continue;
			}
			
			/** Автозаполнение презетов **/	
			$count = Jelly::select('userpreset')->where('user', '=', (int)$user->id())->count();
			
			if(!$count)
			{
				self::fill_permissions($user->id());
			}
			
			$matrix[$user->id()] = $user->get_permissions($menu);
		}

		return $matrix;
	}

	public static function fill_permissions($user_id)
	{
		$readonly = true;
		
		/*
		$personal = Jelly::select('Client_Handbook_Personal')->where('deleted', '=', 0)->where('user', '=', $user_id)->load();
		
		if(!($personal instanceof Jelly_Model) or !$personal->loaded())
		{
			// Если нет связанного персонала начит пришел админ или приравненные к оному лица
			$readonly = false;
		}*/
		
		$farm_admin = Jelly::select('farm')->where('admin', '=', $user_id)->where('deleted', '=', 0)->load();
		
		if($farm_admin instanceof Jelly_Model and $farm_admin->loaded())
		{
			$readonly = false;
		}
		
		$license = Auth::instance()->get_user()->license;
		
		if($license->user->id() == $user_id)
		{
			$readonly = false;
		}
		
		$menus = Jelly::factory('farmpreset')->menu;
		
		foreach($menus as $menu)
		{
			$perm = Jelly::factory('userpreset');
			$perm->user = $user_id;
			$perm->menu_item  = $menu['id'];
			$perm->menu_name  = $menu['title'];
			$perm->permission = $readonly ? Model_UserPreset::DENIED : Model_UserPreset::WRITE ;
			$perm->lock 	  = Model_UserPreset::UNLOCK;
			$perm->save();
		}
	}	
	

	public function get_permissions($menu =  null)
	{
		/** Автозаполнение презетов **/	
		$count = Jelly::select('userpreset')->where('user', '=', $this->id())->count();
		
		if(!$count)
		{
			self::fill_permissions($this->id());
		}
			
		$matrix = array();

		$permissions = Jelly::select('userpreset')->with('user')->where('user', '=', $this->id());

		if($menu and is_array($menu))
		{
			$permissions->where('menu_item', 'IN', $menu);
		}

		$permissions = $permissions->order_by('menu_order')->execute()->as_array();

		$matrix = $permissions;

		/*
		if($this->farm->id())
		{
			if($this->personal->id())
			{
				$permissions = Jelly::select('personalpreset')->where('personal', '=', $this->personal->id());

				if($menu and is_array($menu))
				{
					$permissions->where('menu_item', 'IN', $menu);
				}

				$permissions = $permissions->execute()->as_array();

				$matrix = $this->update_permissions($matrix, $permissions, $menu);
			}

			$permissions = Jelly::select('farmpreset')->where('farm', '=', $this->farm->id());

			if($menu and is_array($menu))
			{
				$permissions->where('menu_item', 'IN', $menu);
			}

			$permissions = $permissions->execute()->as_array();

			$matrix = $this->update_permissions($matrix, $permissions, $menu);
		}*/

		return $matrix;
	}

	protected function update_permissions($base, $modify, $menu = null)
	{
		// Обновляем старые
		for($i = 0; $i < $k = count($base); $i++)
		{
			for($j = 0; $j < $b = count($modify); $j++)
			{
				if(
					$modify[$j]['menu_item'] == $base[$i]['menu_item']
					and
					$modify[$j]['menu_name'] == $base[$i]['menu_name']
					)
					{
						$base[$i]['permission'] = $modify[$j]['permission'];
						$base[$i]['lock'] 		= $modify[$j]['lock'];

						break;
					}
			}
		}

		// Добавляем новые
		for($i = 0; $i < $k = count($modify); $i++)
		{
			$found = false;

			for($j = 0; $j < $b = count($base); $j++)
			{
				if(
					$modify[$i]['menu_item'] == $base[$j]['menu_item']
					and
					$modify[$i]['menu_name'] == $base[$j]['menu_name']
					)
					{
						$found = true;
						break;
					}
			}

			if(!$found)
			{
				if($menu)
				{
					if(in_array($modify[$i]['menu_item'], $menu))
					{
						$base[] = $modify[$i];
					}
				}
				else
					$base[] = $modify[$i];
			}
		}

		return $base;
	}
}
?>
