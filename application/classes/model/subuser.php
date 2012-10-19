<?php
class Model_SubUser extends AC_APIModel
{
    public $is_dictionary = false;

	public static function initialize(Jelly_Meta $meta, $is_dictionary = false)
	{
		parent::initialize($meta, $is_dictionary);
		
		$meta->table('subusers')
			->fields(array(
				// Активен ли пользователь (0 - не активен, 1 - активен
				'is_active'		=> Jelly::field('Boolean', array('label' => 'Активен')),
				'is_root'		=> Jelly::field('Boolean', array('label' => 'Суперадминистратор')),
				'notes'			=> Jelly::field('Text', array('label' => 'Заметки')),
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm_id',
					'label'		=> 'Хозяйство',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				'login'	 => Jelly::field('String', array(
					'label'  => 'Логин',
					'callbacks' => array(
							'user_unique' => array('Model_SubUser', '__unique_username')
					),
					'rules'  => array(
						'max_length' => array(1000),
						'min_length' => array(3),
						'regex' => array('/^[a-zA-Z0-9_]+$/ui'),
						'not_empty' => NULL
					))
				),
				'password'			=> Jelly::field('String', array(
					'label' => 'Пароль',
					'rules' => array(
						'max_length' => array(1000),
						'not_empty' => NULL
					))
				),
				'first_name'	=> Jelly::field('String', array('label' => 'Имя',
					'rules'  => array(
					    'max_length' => array(1000),
					    'regex' => array('/^[a-zA-Zа-яА-Я_\']+$/ui')
					))),
				'last_name'		=> Jelly::field('String', array('label' => 'Фамилия',
					'rules'  => array(
					    'max_length' => array(1000),
					    'regex' => array('/^[a-zA-Zа-яА-Я_\']+$/ui')
					))),
				'middle_name'	=> Jelly::field('String', array('label' => 'Отчество',
					'rules'  => array(
					    'max_length' => array(1000),
					    'regex' => array('/^[a-zA-Zа-яА-Я_\']+$/ui')
					))),
				'position'		=> Jelly::field('String', array('label' => 'Должность',
					'rules' => array(
						'max_length' => array(1000),
					))),
				//'birth_date'	=> Jelly::field('String', array('label' => 'Дата рождения')),
				//'notes'	=> Jelly::field('Text', array('label' => 'Заметки')),
				//new
				'emails' => Jelly::field('HasMany',array(
					'foreign'	=> 'useremail',
					//'column'	=> 'subuser_id',
					'label'		=> 'Почты пользователя',
				)),
				'phones' => Jelly::field('HasMany',array(
					'foreign'	=> 'userphone',
					//'column'	=> 'subuser_id',
					'label'		=> 'Телефоны пользователя',
				)),
				'addresses' => Jelly::field('HasMany',array(
					'foreign'	=> 'useraddress',
					//'column'	=> 'subuser_id',
					'label'		=> 'Адреса пользователя',
				)),
				'dates' => Jelly::field('HasMany',array(
					'foreign'	=> 'userdate',
					//'column'	=> 'subuser_id',
					'label'		=> 'Даты пользователя',
				)),
				'notes' => Jelly::field('HasMany',array(
					'foreign'	=> 'usernote',
					//'column'	=> 'subuser_id',
					'label'		=> 'Заметки пользователя',
				))
			));
	}
	
	protected $__params = array(
		'_id'		=> '_id',
		'login'		=> 'login',
		'temporary_id'	=> 'temporary_id',
		//'birthDate'	=> 'birth_date',
		//'email'	=> 'email',
		'firstName'    	=> 'first_name',
		'middleName'	=> 'middle_name',
		'lastName'	=> 'last_name',
		'isAdmin'	=> 'is_root',
		'position'	=> 'position',
		'password'	=> 'password',
		'notes'		=> 'notes',
		'emails'	=> 'emails',
  		'phones'	=> 'phones',
		'addresses'	=> 'addresses',
		'dates'		=> 'dates',
	);
	
	
	public function validate_structure($arr)
	{
		foreach($this->__params as $key => $value)
		{
		    if((UTF8::strpos($arr['_id'], 'root_') !== false) && ($key=='dates' || $key=='addresses' || $key=='phones' || $key=='emails' || $key=='temporary_id')) continue;
		    if(!array_key_exists($key, $arr)) return false;
		}
		
		return true;
	}
	
	public function validate($data = NULL)
	{
		if ($data === NULL)
		{
			$data = $this->_changed;
		}

		if (empty($data))
		{
			return $data;
		}
		
		if(!array_key_exists('_id', $data))
			$data['_id'] 	= $this->id();
			
		if(!array_key_exists('farm', $data))	
			$data['farm'] 	= $this->farm->id();

		// Create the validation object
		$data = Validate::factory($data);

		// If we are passing a unique key value through, add a filter to ensure it isn't removed
		if ($data->offsetExists(':unique_key'))
		{
			$data->filter(':unique_key', 'trim');
		}

		// Loop through all columns, adding rules where data exists
		foreach ($this->_meta->fields() as $column => $field)
		{
			// Do not add any rules for this field
			if ( ! $data->offsetExists($column))
			{
				continue;
			}

			$data->label($column, $field->label);
			$data->filters($column, $field->filters);
			$data->rules($column, $field->rules);
			$data->callbacks($column, $field->callbacks);
		}

		if ( ! $data->check())
		{
			throw new Validate_Exception($data);
		}

		return $data->as_array();
	}
	
	public static function __unique_username(Validate $array, $field)
	{
	   // check the database for existing records
	   
	   $check = $array->as_array();
	   
	   if(array_key_exists('farm', $check))
	   {
	   		$builder = Jelly::select('subuser')->where('login', 'LIKE', $check[$field])->where('farm', '=', $check['farm'])->where('deleted', '=', 0);
	   		
			if(array_key_exists('_id', $check))
	   			$builder = $builder->where('_id', '!=', $check['_id']);
	 	
	 		$username_exists = (bool)$builder->count();
	 	
		   if ($username_exists)
		   {
		       // add error to validation object
		       $array->error($field, 'username_exists');
		   }
	   }
 		else 
			 $array->error($field, 'farm_id_is_not_defined');
	   
	}
	
	public function prepare($arr, $object_id = null)
	{
		if(UTF8::strpos($object_id, 'root_') !== false)
		{
			$r = array();
			
			if(isset($arr['login']))
				$r['username']  = $arr['login'];			
			
			if(isset($arr['birthDate']))
				$r['birth_date']  = $arr['birthDate'];
			
			if(isset($arr['firstName']))
				$r['first_name']  = $arr['firstName'];
			
			if(isset($arr['middleName']))
				$r['middle_name']  = $arr['middleName'];
			
			if(isset($arr['lastName']))
				$r['last_name']  = $arr['lastName'];
			
			if(isset($arr['position']))
				$r['position']  = $arr['position'];
			
			if(isset($arr['password']))
				$r['password']  = $arr['password'];
			
			if(isset($arr['email']))
				$r['email']  = $arr['email'];
			
			if(isset($arr['notes']))
				$r['notes']  = $arr['notes'];
			
			if(isset($arr['email']))
				$r['email']  = $arr['email'];
				
			return $r;
		}
		
		return parent::prepare($arr, $object_id);
	}
	
	public function get_updated($license, $time = 0)
	{
		$time = (int)$time;
		
		if($time < 0) $time = 0;
		
		$return = array();
		
		// Первым идет администратор с лицензии
		if((int)$license->update_date >= $time) 
		{ 
			$return[] = array(
				'_id'			=> 'root_'.$license->id(),
				'login' 		=> $license->username,
				'birthDate' 	=> $license->birth_date,
				'firstName' 	=> $license->first_name,
				'middleName' 	=> $license->middle_name,
				'lastName' 		=> $license->last_name,
				'position' 		=> $license->position,
				'isAdmin'		=> true,
				'password'		=> $license->password_text,
				'email'			=> $license->email,
				'notes'			=> $license->notes
			);
		}
		
		$users = Jelly::select('subuser')->where('farm', '=', $license->id())->where('deleted', '=', 0)->where('update_date', '>=', $time)->execute();

		foreach($users as $user){
		    $return[] = $this->format_data($user);
		}

		return $return;
	}
	
	public function valid_id($farm, $id)
	{
		if(UTF8::strpos($id, 'root_') !== false)
		{
			$id = (int)UTF8::str_ireplace('root_', '', $id);
			
			if($id != $farm->id())
				return false;
			
			return true;
		}
		
		return parent::valid_id($farm, $id);	
	}
	
	
	public function get_valid()
	{
		$builder = Jelly::select('subuser')->where('is_active','=',1)
										   ->where('deleted', '=',0);

		return $builder;
	}
	
	public function create($license, $data, $version_object, $save = true, $object_id = false)
	{
		if(!is_array($data)) return false;
		$data['is_active'] = true;
		return parent::create($license, $data, $version_object, $save, $object_id);
	}
	
	public function update($license, $object_id, $data, $version_object, $save = true)
	{
		if(UTF8::strpos($object_id, 'root_') !== false)
		{
			$object_id = (int)UTF8::str_ireplace('root_', '', $object_id);
			
			if($object_id != $license->id())
				return false;
			
			if(!is_array($data))
				return false;	
			
			if(array_key_exists('_id', $data)) 
				unset($data['_id']);
			
			if(isset($data['is_active']))
					unset($data['is_active']);

			if(isset($data['deleted']))
					unset($data['deleted']);

			if(isset($data['password']))
			{
				$data['password_confirm'] 	= $data['password'];
				$data['password_text'] 		= $data['password'];
			}
			
				
			$license->set($data);
			$license->update_date = time();
			
			if(!$save)
			{
				try
				{
					$license->validate($data);
				}
				catch(Validate_Exception $e)
				{
					$version_object->error(Model_Object::STATUS_ERROR, $e);
					return false;
				}
				
				return true;
			}
			
			try
			{		
				$license->save();
			}
			catch(Validate_Exception $e)
			{
				$version_object->error(Model_Object::STATUS_ERROR, $e);
				return false;
			}
			
			return true;	
		}
		else 
		{
			unset($data['farm']);
			unset($data['farm_id']);

			return parent::update($license, $object_id, $data, $version_object, $save);
		}			
	}
	
	public function set_deleted($license, $object_id, $version_object, $save = true)
	{
		if(UTF8::strpos($object_id, 'root_') !== false)
		{
				$version_object->error(Model_Object::STATUS_ERROR, 'Cannot delete root users');
				return false;
		}
			
		return parent::set_deleted($license, $object_id, $version_object, $save);	
	}
	
}
