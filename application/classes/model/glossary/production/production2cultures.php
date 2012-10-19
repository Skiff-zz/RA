<?php defined('SYSPATH') or die ('No direct script access.');


class Model_Glossary_Production_Production2Cultures extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('production2cultures')
			->fields(array(
					// без первичного ключа вылазит ошибка при попытке обновить базу
					'_id'			=> Jelly::field('Primary'),
					'production' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_production',
							'column'	=> 'glossary_production_id',
							'label'		=> 'Продукция'
					)),

					'culture'      => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_culture',
							'column'	=> 'glossary_culture_id',
							'label'		=> 'Культура'))
			));
	}
}