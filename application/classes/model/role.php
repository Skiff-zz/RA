<?php
class Model_Role extends Jelly_Model
{
	
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('roles')
			->fields(		
							array(
							'_id'			=> Jelly::field('Primary'),
							'name'	=> Jelly::field('String', array('label' => 'Название роли'))
			         )
			 );
	}
	
}
?>
