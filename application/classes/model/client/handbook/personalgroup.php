<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Handbook_PersonalGroup extends Model_Glossary_AbstractGroup
{

	public static function initialize(Jelly_Meta $meta, $table_name = 'client_handbook_personalgroup', $items_model = 'client_handbook_personal')
	{
		$meta->table($table_name)
			->fields(array(
				'license'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',

                                                        'rules' => array(
                                        						'not_empty' => NULL
                                        				)

													)),
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm',
					'label'		=> 'Хозяйство',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'period'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'period_id',
					'label'		=> 'Период',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				'is_position' => Jelly::field('Integer', array('label' => 'Является группой или должностью - аналог is_group и is_group_really .....')),
				'id_in_glossary'			=> Jelly::field('Integer', array('label' => 'Номер в таблице глоссария')),
				'shifts_per_day' => Jelly::field('String', array('label' => 'Количество смен в сутки')),
				'hours_in_shift' => Jelly::field('String', array('label' => 'Количество часов в смене')),
				'hours_in_shift_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'hours_in_shift_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'productivity_per_hour' => Jelly::field('String', array('label' => 'Производительность в час')),
				'productivity_per_hour_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'productivity_per_hour_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'average_salary' => Jelly::field('String', array('label' => 'Средняя заработная плата')),
				'average_salary_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'average_salary_units_id',
							'label'		=> 'Единицы измерения'
					))
		));

		$p = parent::initialize($meta, $table_name,  $items_model);

		return $p;
	}

	public function get_tree($license_id, $both_trees = false, $exclude = array(), $items_field = 'items', $check = false, $jobs_are_groups = false, $with_friends = false, $linked_with_names = true, $only = '', $farm_id = null){
		$this->result = array();
		$this->counter = 0;
		$friends = $with_friends ? Jelly::factory('user')->get_tree($license_id) : array();

		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];

		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		if($farm_id)
		{
			$farms = array($farm_id);
		}
		
		$names = Jelly::select($items_model_name)->
                        with($items_model->foreign['column'])->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('license', '=', $license_id)->
                        where('period', 'IN', $periods)->
                        where('farm', 'IN', $farms)->
                        order_by('name', 'asc')->
                        execute()->
                        as_array();


		$names_groups = array();
		foreach($names as $name){
			$names_groups[] = $name['group'];
			$names_groups = array_merge($names_groups, explode('/',$name[':group:path']));
		}
		$names_groups = array_unique($names_groups);


		if($linked_with_names){
			if(count($names_groups)==0){
				$groups = array();
			} else {
				$groups = Jelly::select('client_handbook_personalgroup')->
						with('parent')->
						where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
						where('license', '=', $license_id)->
						and_where('_id', 'IN', $names_groups)->
						and_where('period', 'IN', $periods)->
						where('farm', 'IN', $farms)->
						order_by('name', 'asc')->
						execute()->
						as_array();
			}
		}else{
			$groups = Jelly::select('client_handbook_personalgroup')->
                with('parent')->
                where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                where('farm', 'IN', $farms)->
                where('license', '=', $license_id)->
                and_where('period', 'IN', $periods)->
                order_by('name', 'asc')->
                execute()->
                as_array();

		}
		
		//new AC_Profiler;

		$this->get_groups_custom($groups, $names, 0, 0, array(), $items_model->foreign['column'], $both_trees, $jobs_are_groups, $only);

		if($both_trees){
			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':group:_id']==$group_id) {
						$children_n = array();
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['name'],
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $this->result[$i]['level']+1,
							'children_g' => array(),
							'children_n' => $children_n,
							'id_in_glossary' => 0,//$name['id_in_glossary'],
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

		return array_merge($friends, $this->result);
	}




	public function copy_fields($from_period, $to_period, $license_id){
		$delete_fields = Jelly::select('field')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->and_where('period', '=', $to_period)->execute();
		foreach($delete_fields as $delete_field){
			$delete_field->deleted = true;
			$delete_field->save();
		}

		$copy_fields = Jelly::select('field')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				and_where('period', '=', $from_period)->execute()->as_array();
		foreach($copy_fields as $copy_field) {
			$new_one = Jelly::factory('field');
			$new_one->license = $license_id;
			$new_one->farm = $copy_field['farm'];
			$new_one->name = $copy_field['name'];
			$new_one->crop_rotation_number = $copy_field['crop_rotation_number'];
			$new_one->number = $copy_field['number'];
			$new_one->sector_number = $copy_field['sector_number'];
			$new_one->kadastr_area = $copy_field['kadastr_area'];
			$new_one->area = $copy_field['area'];
			$new_one->culture = 0;
			$new_one->culture_before = $copy_field['culture'];
			$new_one->period = $to_period;
			$new_one->coordinates = $copy_field['coordinates'];
			$new_one->save();
		}
	}

	public function save($key = NULL)
	{
	    /*
		if(array_key_exists('parent', $this->_changed))
        {
			$license_id = null;
            $license_id = array_key_exists('license', $this->_changed) ? $this->_changed['license'] : $this->_original['license'];

            if((int)$this->_changed['parent'])
            {
                $parent = Jelly::select($this->meta()->model())->where('_id', '=', (int)$this->_changed['parent'])->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->load();

                if(!($parent instanceof Jelly_Model) or !$parent->loaded())
                {
                    unset($this->_changed['parent']);
                }
                else
                {
                    $this->_changed['path'] = $parent->path.$parent->id().'/';
                }
            }
            else
            {
                $this->_changed['path']         = '/';
                $this->_changed['parent']       = 0;
            }
        }*/


        return parent::save($key);
    }


	private function get_groups_custom($groups, $names, $parent, $level, $exclude, $relation, $both_trees = false, $jobs_are_groups=false, $only = ''){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}


		foreach($items as $item)
		{
			if(in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)){ continue; }

			if(!$only){
				$children_g = $this->get_children_ids_custom($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude);
				$children_n = $this->get_children_ids_custom($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array());

				$this->result[$this->counter] = array(
					'id'	   => 'g'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => true,
					'is_group_realy' => $jobs_are_groups ? true : ($item['is_position']==1?false:true),
					'level'	   => $level,
					'children_g' => (($item['is_position']==1) && (!$both_trees)) ? array():($both_trees ? array_merge($children_g, $children_n) : $children_g),
					'children_n' => $children_n,
					'id_in_glossary' => $item['id_in_glossary'],
					'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':parent:color']
				);
				$this->counter++;
			} else if( $only=='positionsgroups' && !$item['is_position']  ){

				$children_g = $this->get_children_ids_custom($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude, 'positionsgroups');
				$children_n = $this->get_children_ids_custom($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude, 'positions');

				$this->result[$this->counter] = array(
					'id'	   => 'g'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => true,
					'is_group_realy' => $jobs_are_groups ? true : ($item['is_position']==1?false:true),
					'level'	   => $level,
					'children_g' => (($item['is_position']==1) && (!$both_trees)) ? array():($both_trees ? array_merge($children_g, $children_n) : $children_g),
					'children_n' => $children_n,
					'id_in_glossary' => $item['id_in_glossary'],
					'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':parent:color']
				);
				$this->counter++;
			} else if($only=='positions' && $item['is_position']){
				$this->result[$this->counter] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'id_in_glossary' => $item['id_in_glossary'],
					'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':parent:color']
				);
				$this->counter++;

			}

			$this->get_groups_custom($groups, $names, $item['_id'], $level+1, $exclude, $relation, $both_trees, $jobs_are_groups, $only);
		}
	}

	protected function get_children_ids_custom($children, $item_id, $is_group, $relation, $exclude, $only = ''){
		$res = array();
		foreach($children as $child){

			if(in_array($child['_id'], $exclude)){ continue; }
			if($is_group && $child[':parent:_id']==$item_id){

				if(!$only){
					$res[] = 'g'.$child['_id'];
				}else if($only=='positionsgroups'){
					if($child['is_position']){

					}else{
						$res[] = 'g'.$child['_id'];
					}
				}else if($only=='positions'){
					if($child['is_position']){
						$res[] = 'n'.$child['_id'];
					}else{

					}
				}

			}
			if(!$is_group && $child[':'.$relation.':_id']==$item_id ){ $res[] = 'n'.$child['_id']; }
		}
		return $res;
	}


    public function delete($key = NULL)
    {
        //wtf? falling back to parent
        if (!is_null($key)){
            return parent::delete($key);
        }

		$this->deleted = true;
        $this->save();
    }

	public function add_nomenclature($model_ids, $farm_id, $license_id) 
	{
        
		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);
		$period_id = (int) $periods[0];

		if ($farm_id) {
			$selected_farm_obj = Jelly::select('farm', $farm_id);
			if (($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded()) {
				Session::instance()->set('last_create_farm', (int) $farm_id);
			}
			else
				throw new Kohana_Exception('Хозяйства не существует');
		}

		// -----------
		$names  = array();
		$groups = array(); 
		
		foreach ($model_ids as $id) 
		{
			if (strcmp(substr($id, 0, 1), 'n') == 0) {
				$names[] = (int) (substr($id, 1));
			}
			else
			{
				$groups[] = (int) (substr($id, 1));
			}
		}
		
		/** Шаг первый - просто заносим записи в базу, что бы получить айдишники **/
		
		$glossary_items = array();
		$dictionary_items = array();
		
		$glossary_items_result = Jelly::select('glossary_personal')
		 				   ->with('group')
						  ->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
						  ->where(':primary_key', 'IN', $names)
						  ->execute();
        
		foreach($glossary_items_result as $item)
		{
            
			$glossary_items[$item->id()] = $item;
			
			$n_item = Jelly::select('client_handbook_personalgroup')
						  ->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
						  ->where('is_position', '=', 1)
						  ->where('farm', '=', $farm_id)
						  ->where('period', '=', $period_id)
						  ->where('license', '=', $license_id)
						  ->where('id_in_glossary', '=', $item->id())
						  ->load();
			
			if(!($n_item instanceof Jelly_Model) or !$n_item->loaded())
			{
				$n_item = Jelly::factory('client_handbook_personalgroup');
				
				$values = $item->as_array();
				
				unset($values['_id'], $values['group'], $values['deleted'] );
				
				$n_item->set($values);
				
				$n_item->id_in_glossary = $item->id();
				$n_item->is_position 	= 1;
                
                $potential_parent = Jelly::select('client_handbook_personalgroup')
						  ->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
						  ->where('is_position', '=', 0)
						  ->where('farm', '=', $farm_id)
						  ->where('period', '=', $period_id)
						  ->where('license', '=', $license_id)
						  ->where('id_in_glossary', '=', $item->group->id())
						  ->load();
                
                if(!($n_item instanceof Jelly_Model) or !$n_item->loaded()){
                    $n_item->path 	= '/';
                    $n_item->parent 	= 0;
                }else{
                    $n_item->path 	= $potential_parent->path;
                    $n_item->parent = $potential_parent->id();
                }
				
				$n_item->license		= $license_id;	
				$n_item->farm			= $farm_id;
				$n_item->period			= $period_id;
				$n_item->update_date	= time();
                
				$n_item->save();
			}
			
			$dictionary_items[$item->id()] = $n_item;
		}
		
		$glossary_groups 	= array();
		$dictionary_groups 	= array();
		
		$glossary_groups_result = Jelly::select('glossary_personalgroup')
		 				   ->with('parent')
						  ->where_open()
						  ->where('deleted', '=', 0)
						  ->or_where('deleted', 'IS', null)
						  ->where_close()
						  ->where(':primary_key', 'IN', $groups)
						  ->execute();
		
		foreach($glossary_groups_result as $group)
		{
			$glossary_groups[$group->id()] = $group;
			
			$n_group = Jelly::select('client_handbook_personalgroup')
						  ->where_open()
						  ->where('deleted', '=', 0)
						  ->or_where('deleted', 'IS', null)
						  ->where_close()
						  ->where('is_position', '=', 0)
						  ->where('farm', '=', $farm_id)
						  ->where('period', '=', $period_id)
						  ->where('license', '=', $license_id)
						  ->where('id_in_glossary', '=', $group->id())
						  ->load();
			
			if(!($n_group instanceof Jelly_Model) or !$n_group->loaded())
			{
				$n_group = Jelly::factory('client_handbook_personalgroup');
				
				$values = $group->as_array();
				
				unset($values['_id'], $values['parent'], $values['deleted'] );
				
				$n_group->set($values);
				
				$n_group->id_in_glossary = $group->id();
				$n_group->is_position 	 = 0;
				
				$n_group->license		= $license_id;	
				$n_group->farm			= $farm_id;
				$n_group->period		= $period_id;
				$n_group->update_date	= time(); 
				
				$n_group->save();
			}
			
			$dictionary_groups[$group->id()] = $n_group;
		}
				
		/** Шаг два: айдишники в базе уже есть, надо правильно развесить "родителей" и пути **/		
		
		/** Пути групп **/
		foreach($dictionary_groups as $gl_id => $dictionary_model)
		{
			$glossary_model = $glossary_groups[$gl_id];
			
			$old_path = explode('/', $glossary_model->path);
			
			$n_path   = array();
			
			foreach($old_path as $p)
			{
				if($p == '')
					continue;
				
				$n_path[] = $dictionary_groups[$p]->id();	
			}
			
			if($glossary_model->parent->id())
			{
				$dictionary_model->parent = $dictionary_groups[$glossary_model->parent->id()]->id();
				
				// Это важно ибо парентовая модель перепишет путь
				$dictionary_model->save();
				
				$dictionary_model->path = '/'.implode('/', $n_path).'/'.$dictionary_model->id().'/';
			}
			else
			{
				$dictionary_model->path = '/'.$dictionary_model->id().'/';
			}
			
			$dictionary_model->save();
			
			$dictionary_groups[$gl_id] = $dictionary_model;
		}
		
		/** Пути должностей **/
		
		foreach($dictionary_items as $gl_id => $dictionary_model)
		{
			$glossary_model = $glossary_items[$gl_id];
						
			if($glossary_model->group->id())
			{
				$parent_id = $glossary_model->group->id();
				
				$dictionary_model->parent = $dictionary_groups[$parent_id]->id();
				
				// Это важно ибо парентовая модель перепишет путь
				$dictionary_model->save();
				
				// Пофиксим путь самостоятельно
				$path = $dictionary_groups[$parent_id]->path;
				
				$dictionary_model->path   = $path.$dictionary_model->id();
			}
			else
			{
				$dictionary_model->path = '/'.$dictionary_model->id().'/';
			}
						
			$dictionary_model->save();
		}
		
		
		//new AC_Profiler;
	}

	public function create_parent_node($group_from_glossary_id){

	}
	
	
    public function clear_nomenclature($license_id){
		$farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms)) $farms = array(-1);

        $periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		Jelly::delete('client_handbook_personalgroup')->where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->execute();
	}
	
	public function get_farm_tree()
	{
	
	}

	private function fix_children(&$data)
	{
		for($i = 0; $i < $k = count($data); $i++)
		{
			if(!$data[$i]['parent'] or  $data[$i]['parent'] == '')
				continue;

			$parent = $this->tree_get_element_by_id($data, $data[$i]['parent']);

			if(!$parent)
				continue;

			$found = false;

			for($j = 0; $j < $tt = count($data[$parent]['children_g']); $j++)
			{
				if($data[$parent]['children_g'][$j] == $data[$i]['id'])
				{
					$found = true;
					break;
				}
			}

			if(!$found)
			{
				$data[$parent]['children_g'][] = $data[$i]['id'];
			}
		}
	}

}
