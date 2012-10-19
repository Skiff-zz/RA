<?php
class Model_Farm extends Jelly_Model
{
	
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('farms')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
							'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
								'rules' => array(
									'not_empty' => NULL
								))
							),
                            'path'			=> Jelly::field('String', array('label' => 'Путь')),
							'parent'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'farm',
														'column'	=> 'parent_id',
														'label'		=> 'Род. Хозяйство',
													)),
							'admin'		    => Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'user_id',
														'label'		=> 'Администратор',
													)),
                                                    												
							// Активен ли пользователь (0 - не активен, 1 - активен 
                            'is_group'		=> Jelly::field('Boolean', array('label' => 'Является группой')),
                            'license'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',
                                                        
                                                        'rules' => array(
                                        						'not_empty' => NULL
                                        				)
                                                        
													)),
							'address_country'	    => Jelly::field('String', array('label' => 'Страна')),
							'address_region'	   => Jelly::field('String', array('label' => 'Область')),
							'address_city'			=> Jelly::field('String', array('label' => 'Город')),
							'address_zip'			=> Jelly::field('String', array('label' => 'Индекс')),
							'address_street'		=> Jelly::field('String', array('label' => 'Улица и дом')),
							'phone'	=> Jelly::field('String', array('label' => 'Телефон')),
                            'color' => Jelly::field('String', array('label' => 'Цвет',
								'rules'  => array(
									'max_length' => array(6),
									'regex' => array('/^[a-fA-F0-9]+$/ui')
								))),
							'name'			=> Jelly::field('String', array(
								'label' => 'Название', 
								'rules' => array(
									'not_empty' => NULL
								))
							)

			));
	}
    
     public function save($key = NULL)
	{
	    if(array_key_exists('parent', $this->_changed))
        {
            $license_id = null;
            $license_id = array_key_exists('license', $this->_changed) ? $this->_changed['license'] : $this->_original['license'];  
            
            if((int)$this->_changed['parent'])
            { 
                $parent = Jelly::select('farm')->where('_id', '=', (int)$this->_changed['parent'])->where('deleted', '=', 0)->where('license', '=', $license_id)->load();
                
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
        }
        
        if(array_key_exists('name', $this->_changed) or array_key_exists('deleted', $this->_changed) )
        {
            $license_id = null;
            $license_id = array_key_exists('license', $this->_changed) ? $this->_changed['license'] : $this->_original['license'];  
        
			if(array_key_exists('name', $this->_changed))
			{
				$name = trim($this->_changed['name']);
				$this->_changed['name'] = $name;
			}    
			else
			{
				$name = $this->_original['name'];
			}
             
            $check = Jelly::select('farm')->where('_id', '!=', (int)$this->id())->where('name', 'LIKE', $name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->load();
            
            if(($check instanceof Jelly_Model) and $check->loaded())
            {
                throw new Kohana_Exception('Имя Хозяйства должно быть уникальным!');
            }
            
        }
       
        $res = parent::save($key);
        
        if(!$key)
        {
       		Jelly::factory('farmpreset')->insert_preset($this->id());
   		}

        
        return $res;
    }




	private $result = array();
	private $counter = 0;
	public function get_tree($license_id, $is_group, $parent = 0, $parent_first = false, $previous_first = false){
		if($previous_first)$parent_first = true;
		$this->result = array();
		$this->counter = 0;
		$level = 0;
	
		$groups = Jelly::select('farm')->with('parent')->where('license', '=', $license_id)->and_where('deleted', '=', false)->and_where('is_group', '=', true)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('farm')->with('parent')->where('license', '=', $license_id)->and_where('deleted', '=', false)->and_where('is_group', '=', false)->order_by('name', 'asc')->execute()->as_array();

		if($previous_first){
			$item = Jelly::select('farm')->load($parent);
			$item = Jelly::select('farm')->load($item->parent->id());
			$this->result[$this->counter] = array(
				'id'	   => ($item->is_group ? 'g':'n').$item->id(),
				'title'    => $item->name,
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $level,
				'children_g' => $this->get_children_ids($license_id, $item->id(), $groups),
				'children_n' => $this->get_children_ids($license_id, $item->id(), $names),
				'parent'   => '',
				'color'    => $item->color,
				'parent_color' => $item->parent->color
			);
			$level++;
			$this->counter++;
		}

		if($parent_first){
			$item = Jelly::select('farm')->load($parent);
			if($is_group || !$item->is_group){
				$this->result[$this->counter] = array(
					'id'	   => ($item->is_group ? 'g':'n').$item->id(),
					'title'    => $item->name,
					'is_group' => $item->is_group,
					'is_group_realy' => $item->is_group,
					'level'	   => $item->is_group ? $level : 0,
					'children_g' => $item->is_group ? $this->get_children_ids($license_id, $item->id(), $groups) : array(),
					'children_n' => $item->is_group ? $this->get_children_ids($license_id, $item->id(), $names) : array(),
					//'parent'   => $item->parent->id() ? 'g'.$item->parent->id() : '',
					'parent'   => '',
					'color'    => $item->color,
					'parent_color' => $item->parent->color
				);
			}else{
				$this->result[$this->counter] = $item->id();
			}

			$this->counter++;
			if(!$item->is_group){ return $this->result; }
			else					  { $level++; }
		}

		
		$this->get_groups($groups, $names, $license_id, $parent, $level, array(), !$is_group);

		if(!$is_group){
			$new_res = array();
			if(!$parent){ $this->result[] = 0; }
			foreach($this->result as $parent_farm){
				$items = array();
				foreach($names as $name){
					if($name[':parent:_id']==$parent_farm) $items[] = $name;
				}

				foreach($items as $item) {
					$new_res[] = array(
						'id'	   => 'n'.$item['_id'],
						'title'    => $item['name'],
						'is_group' => false,
						'is_group_realy' => false,
						'level'	   => 0,
						'children_g' => array(),
						'children_n' => array(),
						'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
						'color'    => $item['color'],
						'parent_color' => $item[':parent:color'] ? $item[':parent:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
					);
				}
			}
			$this->result = $new_res;
		}
		return $this->result;
	}

	private function get_groups($groups, $names, $license_id, $parent, $level, $exclude, $ids_only){

		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}

		foreach($items as $item)
		{
			if(in_array($item['_id'], $exclude)){ continue; }
			if($ids_only){
				$this->result[$this->counter] = $item['_id'];
			}else{
				$children_g = $this->get_children_ids($license_id, $item['_id'], $groups);
				$children_n = $this->get_children_ids($license_id, $item['_id'], $names);

				$this->result[$this->counter] = array(
					'id'	   => 'g'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => true,
					'is_group_realy' => true,
					'level'	   => $level,
					'children_g' => $children_g,
					'children_n' => $children_n,
					'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':parent:color'] ? $item[':parent:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
			
			$this->counter++;
			$this->get_groups($groups, $names, $license_id, $item['_id'], $level+1, $exclude, $ids_only);
		}
	}

	private function get_children_ids($license_id, $item_id, $items){
		$res = array();

		foreach($items as $item){
			if($item[':parent:_id']==$item_id) $res[] = ($item['is_group'] ? 'g':'n').$item['_id'];
		}
		return $res;
	}

	public function get_simple_tree($license_id, $exclude, $parent = 0){
		if(in_array($parent, $exclude)){ return array(); }
		$this->result = array();
		$this->counter = 0;

		$groups = Jelly::select('farm')->with('parent')->where('license', '=', $license_id)->and_where('deleted', '=', false)->and_where('is_group', '=', true)->order_by('name', 'asc')->execute()->as_array();

		if($parent){
			$item = Jelly::select('farm')->load($parent);
			$this->result[$this->counter] = array(
				'id'	   => ($item->is_group ? 'g':'n').$item->id(),
				'title'    => $item->name,
				'is_group' => $item->is_group,
				'is_group_realy' => $item->is_group,
				'level'	   => 0,
				'children_g' => $this->get_children_ids($license_id, $item->id(), $groups),
				'children_n' => array(),
				'parent'   => '',
				'color'    => $item->color,
				'parent_color' => $item->parent->color
			);
			$this->counter++;
		}

		$this->get_groups($groups, array(), $license_id, $parent, $parent ? 1:0, $exclude, false);

		return $this->result;
	}


	public function get_full_tree($license_id, $parent = 0, $parent_first = false, $filter = false, $with_fields_count = false){
		$this->result = array();
		$this->counter = 0;
		
		$s_ids = array();
		
		$filter_selected = Arr::get($_GET, 'filter_selected', null);
		
		if($filter or $filter_selected)
        {
	        $session 		= Session::instance();
	        $s_farms 		= $session->get('farms');
	        $s_farm_groups 	= $session->get('farm_groups');
	        
	        if(!is_array($s_farms))
	        	$s_farms = array();
	        
	        if(!is_array($s_farm_groups))
	        	$s_farm_groups = array();
	        
	        $s_ids 			= array_merge($s_farms, $s_farm_groups);
	        $s_ids			= array_unique($s_ids);
			if(count($s_ids)==0)$s_ids = array(-1); //если не выбрано ни одного хозяйства то нужно чтоб и из базы ничего не выбиралось
        }

		$farms = Jelly::select('farm')->with('parent')->where('license', '=', $license_id)->and_where('deleted', '=', false)->order_by('name', 'asc');
		
		
		if($filter)
		{
		   if(count($s_ids))
		   {
	        	$farms->where(':primary_key', 'IN', $s_ids);
	        }/*
	        if(count($s_farm_groups))
	        	$farms->where(':primary_key', 'IN', $s_farm_groups);
	        	*/
		}
		
		$farms = $farms->execute()->as_array();
		
		$names = Jelly::select('farm')->with('parent')->where('license', '=', $license_id)->and_where('deleted', '=', false)->and_where('is_group', '=', false)->order_by('name', 'asc');
		
		if($filter)
		{
		   if(count($s_ids))
	        	$names->where(':primary_key', 'IN', $s_ids);
		}
		
		$names = $names->execute()->as_array();
		
		if($filter)
		{
			foreach($farms as &$farm)
			{
				if(!in_array((int)$farm['parent'], $s_farm_groups))
				{
					$farm['parent'] 		= 0;
					$farm[':parent:_id'] 	= 0;
					$names['parent'] 		= 0;
					$names[':parent:_id'] 	= 0;
				}
			}
		}
		
		//var_dump($farms);
		//exit;

		if($parent_first){
			$item = Jelly::select('farm')->load($parent);
			$this->result[$this->counter] = array(
				'id'	   => ($item->is_group ? 'g':'n').$item->id(),
				'title'    => $item->name,
				'is_group' => true,
				'is_group_realy' => $item->is_group,
				'level'	   => 0,
				'children_g' => $this->get_children_ids($license_id, $item->id(), $farms),
				'children_n' => $this->get_children_ids($license_id, $item->id(), $names),
				'parent'   => $item->parent->id() ? 'g'.$item->parent->id() : '',
				'color'    => $item->color,
				'parent_color' => $item->parent->color
			);
			$this->counter++;

			if(!$item->is_group){ return $this->result; }
		}

		$this->get_full_tree_rec($farms, $names, $license_id, $parent, $parent_first ? 1:0, $filter_selected ? $s_ids : null, $with_fields_count);

		return $this->result;
	}


	private function get_full_tree_rec($farms, $names, $license_id, $parent, $level, $s_farms = null, $with_fields_count = false){
		
		$items = array();
		foreach($farms as $farm){
			if($farm[':parent:_id']==$parent && !$farm['is_group']) {
				$title = $this->get_title($farm['_id'], $farm['name'], $with_fields_count);
				$this->result[$this->counter] = array(
					'id'	   => 'n'.$farm['_id'],
					'title'    => $title,
					'is_group' => true,
					'is_group_realy' => false,
					'level'	   => $level,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $farm[':parent:_id'] ? 'g'.$farm[':parent:_id'] : '',
					'color'    => $farm['color'],
					'parent_color' => $farm[':parent:color'] ? $farm[':parent:color'] : 'BBBBBB'
				);
				
				if(is_array($s_farms) and in_array((int)$farm['_id'], $s_farms))
				{
					$this->result[$this->counter]['checked'] = true;
				}
				
				$this->counter++;
			}
		}

		foreach($farms as $farm){
			if($farm[':parent:_id']==$parent && $farm['is_group']) $items[] = $farm;
		}

		foreach($items as $item)
		{
			$children_g = $this->get_children_ids($license_id, $item['_id'], $farms);
			$children_n = $this->get_children_ids($license_id, $item['_id'], $names);
			$title = $this->get_title($item['_id'], $item['name'], $with_fields_count);

			$this->result[$this->counter] = array(
				'id'	   => ($item['is_group'] ? 'g':'n').$item['_id'],
				'title'    => $title,
				'is_group' => true,
				'is_group_realy' => $item['is_group'],
				'level'	   => $level,
				'children_g' => $children_g,
				'children_n' => $children_n,
				'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
				'color'    => $item['color'],
				'parent_color' => $item[':parent:color'] ? $item[':parent:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
			);
			
			if(is_array($s_farms) and in_array((int)$item['_id'], $s_farms))
			{
				$this->result[$this->counter]['checked'] = true;
			}
				
			$this->counter++;
			$this->get_full_tree_rec($farms, $names, $license_id, $item['_id'], $level+1, $s_farms, $with_fields_count);
		}
	}


	private function get_title($farm_id, $farm_name, $with_fields_count){
		if($with_fields_count){
			$fields_count = Jelly::select('field')->where('deleted', '=', false)->and_where('farm', '=', $farm_id)->execute()->count();
			return $farm_name.'</div>  <div style="color: #666666; height: 28px;">'.$fields_count.'</div><div>';
		}else{
			return $farm_name;
		}
	}


    public function delete($key = NULL)
    {
        //wtf? falling back to parent
        if (!is_null($key))
        {
            return parent::delete($key);
        }

		$fields = Jelly::select('field')->where('deleted', '=', false)->and_where('farm', '=', $this->id())->execute();
		foreach($fields as $field){
			$field->deleted = true;
			$field->save();
		}

		$children = Jelly::select('farm')->where('deleted', '=', false)->and_where('parent', '=', $this->id())->execute();
		foreach($children as $child){
			$child->delete();
		}
        
		$this->deleted = 1;
        
        $same_admin_farms_count = Jelly::select('farm')->where('user_id', '=', $this->admin->id())->and_where('deleted', '=', 0)->count();
        
        //changing admin password to disable login
        if ($same_admin_farms_count<2)
        {
            $adm = $this->admin;
            //checking if it is realy object, not random data
            if ($adm&&($adm instanceof Jelly_Model)&&$adm->loaded())
            {
                $adm->deleted = 1;
                $adm->password = md5(rand().microtime());
                $adm->save();
            }
        }
        
        $this->save();        
        
    }


	public function connectNoGroupChildren($group_id, $license_id){
		$children = Jelly::select('farm')->where('license', '=', $license_id)->and_where('deleted', '=', false)->and_where('parent', '=', '0')->and_where('is_group', '=', false)->execute();
		foreach($children as $child){
			$child->parent = $group_id;
			$child->save();
		}
	}


	public function get_session_farms($in_one_array = true){
		$session = Session::instance();
		$farms_n = $session->get('farms');
		$farms_g = $session->get('farm_groups');
		if(!is_array($farms_n)) $farms_n = array();
		if(!is_array($farms_g)) $farms_g = array();

		if($in_one_array){
			$farms = array_merge($farms_n, $farms_g);
			return array_unique($farms);
		}else{
			return array('groups' => $farms_g, 'names' => $farms_n);
		}
	}


	public function getBreadCrumbs($farm_id){
		$farm = Jelly::select('farm')->load($farm_id);
		if($farm->parent->id()) return array_merge($this->getBreadCrumbs($farm->parent->id()), array($farm->name));
		else return array($farm->name);
	}

	public function get_parent_path($farm_id)
	{
		$farm = Jelly::select('farm')->load($farm_id);
		if($farm->parent->id()) return array_merge($this->get_parent_path($farm->parent->id()), array($farm));
		else return array($farm);
	}

	public function get_farm_subtree($farm_id){
		$res = array();
		$children = Jelly::select('farm')->where('deleted', '=', false)->and_where('parent', '=', $farm_id)->execute()->as_array();

		foreach($children as $child){
			$res[] = $child['_id'];
			$res = array_merge($res, $this->get_farm_subtree($child['_id']));
		}

		return $res;
	}
	
}

