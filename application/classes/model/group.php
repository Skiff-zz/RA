<?php

class Model_Group extends Jelly_Model
{
    
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('groups')
		    ->fields(array(
			// Первичный ключ
			'_id' 				=> new Field_Primary,
			'deleted' 			=> Jelly::field('Boolean', array('label' => 'Удален')),
			'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
			),	
            'name'	      => Jelly::field('String', array('label' => 'Название')),
            'path'			=> Jelly::field('String', array('label' => 'Путь')),
		    'parent'		=> Jelly::field('BelongsTo',array(
                														'foreign'	=> 'group',
                														'column'	=> 'parent_id',
                														'label'		=> 'Род. группа',
                													)),


			'email' => new Field_Email(array(
				'unique' => TRUE,
				'label' => 'E-Mail',
				'rules' => array(
					'not_empty' => NULL,
					'max_length' => array(1000),
					'min_length' => array(3)
				)
			)),
			'login' => new Field_String(array(
				'unique' => TRUE,
				'rules' => array(
					'not_empty' => NULL,
					'max_length' => array(1000),
					'min_length' => array(3),
					'regex' => array('/^[\pL_.-]+$/ui')
				)
			)),
			'password' => new Field_String(array(
				'label' => 'Пароль',
				'rules' => array(
					'not_empty' => NULL,
					'max_length' => array(1000),
					'min_length' => array(6)
				)
			)),
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
			'address' => Jelly::field('Text', array('label' => 'Aдрес')),
			'phone'	=> Jelly::field('String', array('label' => 'Телефон')),
			'color' => Jelly::field('String', array('label' => 'Цвет',
				'rules'  => array(
					'max_length' => array(6),
					'regex' => array('/^[a-fA-F0-9]+$/ui')
				))),
                							
			'user'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'user_id',
														'label'		=> 'Род. лицензиат',
													)) 
		    ));
	}
}