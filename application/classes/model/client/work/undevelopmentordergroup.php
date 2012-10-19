<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_UndevelopmentOrderGroup extends Model_Glossary_AbstractGroup
{
		public static function initialize(Jelly_Meta $meta, $table_name = 'client_work_undevelopmentordergroup', $items_model = 'client_work_undevelopmentorder')
		{
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
	
			$p = parent::initialize($meta, $table_name,  $items_model);
	
			return $p;
		}
}