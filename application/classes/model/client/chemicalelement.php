<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Chemicalelement extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_chemicalelement', $group_model = 'client_chemicalelementgroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
				'symbol'	=> Jelly::field('String', array('label' => 'Символ (лат. буква)')),
				'number'	=> Jelly::field('Integer', array('label' => 'Номер в период. системе'))
			 ));
	}

	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];

		$groups = Jelly::select($group_model_name)->with('parent')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select($model_name)->with($group_field)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'].'</div> <div style="color: #666666; height: 28px; padding-top:3px; padding-right:4px;">'.$item['symbol'].'</div><div>',
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

	protected function get_groups($groups, $parent){
		foreach($groups as $group){
			if($group[':parent:_id']==$parent){
				$this->result[$this->counter] = $group['_id'];
				$this->counter++;
				$this->get_groups($groups, $group['_id']);
			}
		}
	}

}

