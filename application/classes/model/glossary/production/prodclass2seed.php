<?php defined('SYSPATH') or die ('No direct script access.');


class Model_Glossary_Production_ProdClass2Seed extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('prodclass2seeds')
			->fields(array(
					// без первичного ключа вылазит ошибка при попытке обновить базу
					'_id'			=> Jelly::field('Primary'),
					'prodclass' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_productionclass',
							'column'	=> 'glossary_productionclass_id',
							'label'		=> 'Класс продукции'
					)),

					'seed'      => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_seed',
							'column'	=> 'glossary_seed_id',
							'label'		=> 'Семена'))
			));
	}
}