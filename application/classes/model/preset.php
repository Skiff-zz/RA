<?php
class Model_Preset extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('presets')
			->fields(		
							array(
							'_id'			 => Jelly::field('Primary'),
							'role'		     => Jelly::field('BelongsTo',array(
														'foreign'	=> 'role',
														'column'	=> 'role_id',
														'label'		=> 'Роль',
													)),
                            'uri'       	 =>  Jelly::field('String', array('label' => 'Секция')),
                            'controller'	 =>  Jelly::field('String', array('label'  => 'Контроллер')),
                            'method'	     =>  Jelly::field('String', array('label' => 'Метод')),
                            'rule'	         =>  Jelly::field('Integer', array('label' => 'Правило')),
                            'class' 	     =>  Jelly::field('String', array('label' => 'Класс объекта')),
                            'object_id'      =>  Jelly::field('Integer', array('label' => 'Идентификатор объекта')),                        
			         )
			 );
	}
	
}
?>
