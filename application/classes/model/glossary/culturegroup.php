<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_CultureGroup extends Model_Glossary_AbstractGroup
{

	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_culturegroups', $items_model 	= 'glossary_culture')
	{
		parent::initialize($meta, $table_name,  $items_model);

		$meta->table($table_name)
		->fields(array(

				'crop_rotation_interest' =>  Jelly::field('String', array('label' => 'Процент в севообороте')),
		 ));
	}

	public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items', $with_seeds = false){
		$this->result = array();
		$this->counter = 0;

		$groups = Jelly::select('glossary_culturegroup')->with('parent')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('glossary_culture')->with('group')->with('type')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		if($with_cultures && $with_seeds) $seeds = Jelly::select('glossary_seed')->with('group')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, $names, 0, 0, $exclude, $with_cultures);

		if($with_cultures){
			$types = Jelly::select('glossary_culturetype')->execute()->as_array();
			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':group:_id']==$group_id) {

						$children_n = $with_seeds ? $this->get_children_seeds($seeds, $name['_id']) : array();
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['title'],
							'type'     => $name[':type:name'] ,
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $this->result[$i]['level']+1,
							'children_g' => array(),
							'children_n' => $children_n,
							'parent'   => $group_id ? 'g'.$group_id : '',
							'color'    => $name['color'],
							'parent_color' => $this->result[$i]['color']
						);
					}
				}
				array_splice($this->result, $i+1, 0, $group_names);
			}
			if(isset($this->result[0]) && $this->result[0]['id']=='g0'){ array_splice($this->result, 0, 1); }

//			return Jelly::factory('glossary_culture')->prepareResult($this->result, isset($types[0]) ? $types[0]['name'] : '');
			return $this->result;
		}

		return $this->result;
	}

	protected function get_groups($groups, $names, $parent, $level, $exclude, $with_cultures, $relation=false){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}


		foreach($items as $item)
		{
			if(in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)){ continue; }
			$children_g = $this->get_children_ids($groups, $item['_id'], true, false, isset($exclude['groups']) ? $exclude['groups'] : $exclude);
			$children_n = $this->get_children_ids($names, $item['_id'], false, false, isset($exclude['names']) ? $exclude['names'] : array());

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
			$this->get_groups($groups, $names, $item['_id'], $level+1, $exclude, $with_cultures);
		}
	}


	protected function get_children_ids($children, $item_id, $is_group, $relation = false, $exclude = array()){
		$res = array();
		foreach($children as $child){
			if(in_array($child['_id'], $exclude)){ continue; }
			if($is_group && $child[':parent:_id']==$item_id){ $res[] = 'g'.$child['_id']; }
			if(!$is_group && $child[':group:_id']==$item_id){ $res[] = 'n'.$child['_id']; }
		}
		return $res;
	}


	private function get_children_seeds($seeds, $culture_id){
		$res = array();
		foreach($seeds as $seed){
			if($seed[':group:_id']==$culture_id) $res[] = 's'.$seed['_id'];
		}
		return $res;
	}


//	private function updateTitle($title, $current_type, $types, $skip_default = false){
//		$c_type = array('_id'=>0, 'name' => '');
//		foreach($types as $type){
//			if($type['_id']==$current_type){ $c_type = $type; }
//		}
//		if($skip_default){
//			if($c_type['_id']!=1){ $title .= ' '.$c_type['name']; }
//		}else{
//			$title .= ' '.$c_type['name'];
//		}
//		return $title;
//	}


	public function connectNoGroupChildren($group_id, $license_id){
		$children = Jelly::select('glossary_culture')->where('deleted', '=', false)->and_where('group', '=', '0')->execute();
		foreach($children as $child){
			$child->group = $group_id;
			$child->save();
		}
	}

}

