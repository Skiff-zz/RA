<?php
class Model_Rule extends Jelly_Model
{
	
    const DISABLED      = 0;
    const ENABLED       = 1;
    const READONLY      = 2;
    
    public static $readonly_methods = array('list', 'read', 'tree');
    
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('rules')
			->fields(		
							array(
							'_id'			 => Jelly::field('Primary'),
							'user'		     => Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'user_id',
														'label'		=> 'Пользователь',
													)),
                            'uri'         	 =>  Jelly::field('String', array('label' => 'Секция')),
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
