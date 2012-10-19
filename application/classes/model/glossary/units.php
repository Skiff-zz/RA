<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Units extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_units', $group_model = 'glossary_unitsgroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(

				'shortname'	   => Jelly::field('String', array('label' => 'Сокращение')),
//				'type'			=> Jelly::field('String', array('label' => 'Тип')),
				'default'		=> Jelly::field('Boolean', array('label' => 'По умолчанию')),
				'SI_equiv'		=> Jelly::field('String', array('label' => 'Эквивалент в СИ')),
				
				'block'          => Jelly::field('String', array('label' => 'Место использования')),
				'order'          => Jelly::field('Integer', array('label' => 'Порядок'))
			));
	}


	public function getUnits($block){
		$units = Jelly::select('glossary_units')->where('block', '=', $block)->order_by('order')->execute()->as_array();

		return $units;
	}
}

