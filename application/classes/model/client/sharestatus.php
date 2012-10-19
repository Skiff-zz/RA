<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_ShareStatus extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('share_statuses')
			->fields(array(
				
				'_id' => new Field_Primary,
				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
				))),
				'color'	=> Jelly::field('String', array('label' => 'Цвет'))

		));
	}

	
}

