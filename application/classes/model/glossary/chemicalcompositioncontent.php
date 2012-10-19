<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_ChemicalCompositionContent extends Jelly_Model {

    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_chemicalcompositioncontent')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'chemicalcomposition'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'Glossary_ChemicalComposition',
                        'column'	=> 'Glossary_ChemicalComposition_id',
                        'label'		=> 'ChemicalComposition'
                )),	
				
				'color' => Jelly::field('String', array('label' => 'Цвет')),
                'text' => Jelly::field('String', array('label' => 'Текст')),
                'first_lower' => Jelly::field('String', array('label' => 'Первый нижний')),
                'first_upper' => Jelly::field('String', array('label' => 'Первый верхний')),
                'first_units' => Jelly::field('BelongsTo',array(
                    'foreign'	=> 'glossary_units',
                    'column'	=> 'first_units_id',
                    'label'		=> 'Единицы измерения'
                )),
                'second_lower' => Jelly::field('String', array('label' => 'Второй нижний')),
                'second_upper' => Jelly::field('String', array('label' => 'Второй верхний')),
                'second_units' => Jelly::field('BelongsTo',array(
                    'foreign'	=> 'glossary_units',
                    'column'	=> 'second_units_id',
                    'label'		=> 'Единицы измерения'
                )),
                'order' => Jelly::field('String', array('label'=>'Порядок'))
                
		));
	}
}

