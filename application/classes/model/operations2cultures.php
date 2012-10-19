<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Operations2Cultures extends Jelly_Model
{
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operations2cultures')
			->fields(array(
				'operation_id'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'operation_id',
                        'label'		=> 'Операция'
                )),
				'culture_id'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_culture',
                        'column'	=> 'culture_id',
                        'label'		=> 'Культура'
                )),	
    	));
	}
}

