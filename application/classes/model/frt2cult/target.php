<?php

class Model_Frt2Cult_Target extends Jelly_Model
{
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('frt2cult_target')
			->fields(array(
					'_id'			=> Jelly::field('Primary'),
					'culture' => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_fertilizerculture',
							'column'	=> 'glossary_fertilizerculture_id',
							'label'		=> 'Культура в Удобрениях'
					)),

					'target'      => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_fertilizer_target',
							'column'	=> 'glossary_fertilizer_target_id',
							'label'		=> 'Целевой объект'))
			));
	}
}

?>