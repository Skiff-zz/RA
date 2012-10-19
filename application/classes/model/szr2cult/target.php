<?php

class Model_SZR2Cult_Target extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('szr2cult_target')
			->fields(array(
					// без первичного ключа вылазит ошибка при попытке обновить базу
					'_id'			=> Jelly::field('Primary'),
					'culture' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_szr_szrculture',
							'column'	=> 'glossary_szr_szrculture_id',
							'label'		=> 'Культура в СЗР'
					)),

					'target'      => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_szr_target',
							'column'	=> 'glossary_szr_target_id',
							'label'		=> 'Целевой объект'))
			));
	}
}
