<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_ProducerGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'client_producergroup', $items_model 	= 'client_producer')
	{
		return parent::initialize($meta, $table_name,  $items_model);
	}

	public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items', $with_country = false){
		$this->result = array();
		$this->counter = 0;
		$countries = array();

		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];

		$groups = Jelly::select($this->meta()->model())->with('parent')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->with('country')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, $names, 0, 0, $exclude, $with_cultures, $items_model->foreign['column']);

		if($with_cultures){

			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':'.$items_model->foreign['column'].':_id']==$group_id) {
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['name'].($with_country ? ' ('.$name[':country:name'].')' : ''),
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $this->result[$i]['level']+1,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => $group_id ? 'g'.$group_id : '',
							'color'    => $name['color'],
							'parent_color' => $this->result[$i]['color']
						);
					}
				}
				array_splice($this->result, $i+1, 0, $group_names);
			}
			if(isset($this->result[0]) && $this->result[0]['id']=='g0'){ array_splice($this->result, 0, 1); }
		}

		return $this->result;
	}

	protected function get_groups($groups, $names, $parent, $level, $exclude, $with_cultures, $relation){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}


		foreach($items as $item)
		{
			if(in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)){ continue; }
			$children_g = $this->get_children_ids($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude);
			$children_n = $this->get_children_ids($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array());

			$this->result[$this->counter] = array(
				'id'	   => 'g'.$item['_id'],
				'title'    => $item['name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $level,
				'children_g' => $with_cultures ? array_merge($children_g, $children_n) : $children_g,
				'children_n' => $children_n,
				'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
				'color'    => $item['color'],
				'parent_color' => $item[':parent:color']
			);
			$this->counter++;
			$this->get_groups($groups, $names, $item['_id'], $level+1, $exclude, $with_cultures, $relation);
		}
	}
}
