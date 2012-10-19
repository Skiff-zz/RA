<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_ProductionClass extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_productionclass', $group_model = 'glossary_production')
	{
		parent::initialize($meta,  $table_name, $group_model );

		$meta->table($table_name)
			->fields(array(
				'seeds'      => Jelly::field('ManyToMany',array(
						'foreign'	=> 'glossary_seed',
						'label'	=> 'Культуры',
						'through'   => 'glossary_production_prodclass2seed'
					)),
				'shown_cultures_ids' => Jelly::field('String', array('label' => 'Идшники культур через запятую'))
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
		$names = Jelly::select($model_name)->with($group_field)->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group['_id']){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
					'clear_title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF'),
					'production_name' => $group['name']
				);
			}
		}
		return $res;
	}

	protected function get_groups($groups, $parent){
		foreach($groups as $group){
			if($group[':parent:_id']==$parent){
				$this->result[$this->counter] = $group;
				$this->counter++;
				$this->get_groups($groups, $group['_id']);
			}
		}
	}
}

