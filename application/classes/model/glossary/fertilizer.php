<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Fertilizer extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_fertilizer', $group_model = 'glossary_fertilizergroup')
	{
		parent::initialize($meta,  $table_name, $group_model );

		$meta->table($table_name)
			->fields(array(
				'units'	    => Jelly::field('BelongsTo',array(
						'foreign'	=> 'glossary_units',
						'column'	=> 'units_id',
						'label'		=> 'Единицы измерения'
				)),
				 'form'	=> Jelly::field('ManyToMany',array(
						'foreign'	=>'glossary_preparativeform',
						'column'	=> 'preparative_form_id',
						'label'		=> 'Препаративная форма',
                                                'through'   => array('model'=>'fert2form','columns'=>array('fert_id','form_id'))
				)),
				'producer' => Jelly::field('ManyToMany',array(
						'foreign'	=>'client_producer',
						'label'		=> 'Производитель',
						'column'	=> 'producer',
						'through'   => array('model'=>'fert2producer','columns'=>array('fert_id','producer_id'))
				)),

				'expend'		=> Jelly::field('String', array('label' => 'Средний расход')),
				'expend_units'        => Jelly::field('BelongsTo',array(
						'foreign'	=> 'glossary_units',
						'column'	=> 'expend_units_id',
						'label'		=> 'Единицы измерения'
				)),

				'fertilizer_dvs'         => Jelly::field('HasMany',array(
												'foreign'	=>'glossary_fertilizer_fertilizerdv',
												'label'		=> 'ДВ',
									  )),

				'fertilizer_cultures'  => Jelly::field('HasMany',array(
												'foreign'	=>'glossary_fertilizerculture',
												'label'		=> 'Культуры',
									  )),
			 ));
	}

	public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];

		$groups = Jelly::select($group_model_name)->with('parent')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select($model_name)->with($group_field)->with('form')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
                                $record = Jelly::select($model_name,$item['_id']);
                                $item_form = $record->form;
				if(count($item_form)>0){
					$forms = array();
					for($k=0;$k<count($item_form);$k++){
						$forms[] = $item_form[$k]->short_name;
					}
					$forms = implode(', ',$forms);
//					$forms = '('.$forms.')';
				} else {
					$forms = '';
				}
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'].'</div>  <div style="color: #666666; height: 28px; padding-top:3px; padding-right:4px;">'.$forms.'</div><div>',
					'clear_title' => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}
		return $res;
	}

}

