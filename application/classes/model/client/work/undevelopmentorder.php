<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_UndevelopmentOrder extends Model_Glossary_Abstract
{
	protected $model_name 		= 'client_work_undevelopmentorder';
	
	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_work_undevelopmentorder', $group_model = 					'client_work_undevelopmentordergroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
				'license'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия'
													)),
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm',
					'label'		=> 'Хозяйство'
				)),

				'period'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'period_id',
					'label'		=> 'Период'
				))
			 ));
	}	
}