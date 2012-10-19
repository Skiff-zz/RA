<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_TechMobileGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_techmobile_group', $items_model 	= 'glossary_techmobile')
	{
		return parent::initialize($meta, $table_name,  $items_model);
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
            
            $children_n_all = $this->get_all_children_ids($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array(), TRUE);

			$this->result[$this->counter] = array(
				'id'	   => 'g'.$item['_id'],
//				'title'    => $item['name'],
                'title'    => $item['name'].'</div> <div style="color: #666666; height: 28px; padding-top:3px; padding-right:4px;">'.count($children_n_all).'</div><div>',
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

