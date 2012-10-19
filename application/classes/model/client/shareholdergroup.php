<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_ShareholderGroup extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('shareholdergroups')
			->fields(array(
				
				'_id' => new Field_Primary,
				
				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
					))),

				'color'	=> Jelly::field('String', array('label' => 'Цвет')),

                'parent'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_shareholdergroup',
                        'column'	=> 'parent_id',
                        'label'		=> 'Группа'
                )),
				
				'license' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'license',
                        'column'	=> 'license_id',
                        'label'		=> 'Лицензия'
                )),

				'farm'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm_id',
					'label'	=> 'Хозяйство',
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
				))

		));
	}
	
	
	protected $result = array();
	protected $counter = 0;
	public function get_shareholder_tree($license_id, $both_trees = true, $with_extras = false){
		$this->result = array();
		$this->counter = 0;
		$exclude = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$groups = Jelly::select('Client_ShareholderGroup')->with('parent')->where('license', '=', $license_id)->where('period', '=', $periods[0])->where('farm', 'IN', $farms)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('Client_Shareholder')->with('parent')->where('license', '=', $license_id)->where('period', '=', $periods[0])->where('farm', 'IN', $farms)->order_by('last_name', 'asc')->execute()->as_array();
		
		$this->get_groups($groups, $names, 0, 0, $exclude, $both_trees);
		
		if($both_trees){
			
			$this->result = array_merge($this->result, array(array(
				'id'=>'g-2', 
				'title'    => 'Без группы',
				'level'=>0, 
				'is_group' => true,
				'is_group_realy' => true,
				'children_g' => array(),
				'children_n' => array(),
				'color'=>'BBBBBB',
				'parent'   => '',
				'parent_color'   => 'BBBBBB',
				'farm'    => 0
			)));
			
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':parent:_id']==$group_id || ($name[':parent:_id']==0 && $group_id=='-2')) {
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $this->compose_full_name($name),
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $this->result[$i]['level']+1,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => $group_id ? 'g'.$group_id : '',
							'color'    => $name['color'],
							'parent_color' => $this->result[$i]['color'],
							'farm'    => $name['farm']
						);
					}
					if($name[':parent:_id']==0 && $group_id=='-2'){
						$this->result[$i]['children_g'][] = 'n'.$name['_id'];
					}
				}
				array_splice($this->result, $i+1, 0, $group_names);
			}
			
			$count = count($this->result);
			if($count && isset($this->result[$count-1]) && $this->result[$count-1]['id']=='g-2'){ array_splice($this->result, $count-1, 1); }
		}
		
		if($with_extras) $this->construct_extras($license_id, $periods[0], $farms);
		
		return $this->result;
	}
	
	
	
	private function construct_extras($license_id, $period, $farms){
		$records = Jelly::select('client_share')->where('license', '=', $license_id)
											    ->where('period', '=', $period)
												->where('farm', 'IN', $farms)->execute()->as_array();
		
		$total_area = 0.0;
		$areas = array();
		$shares_count = array();
		foreach($records as $record){
			$total_area += $record['area'];
			$areas['n'.$record['shareholder']] = isset($areas['n'.$record['shareholder']]) ? $areas['n'.$record['shareholder']]+$record['area'] : $record['area'];
			$shares_count['n'.$record['shareholder']] = isset($shares_count['n'.$record['shareholder']]) ? $shares_count['n'.$record['shareholder']]+1 : 1;
		}
		
		for($i=count($this->result)-1; $i>=0; $i--){
			if(!isset($this->result[$i]['area'])){
				$this->result[$i]['area'] = isset($areas[$this->result[$i]['id']]) ? $areas[$this->result[$i]['id']] : 0.00;
				$this->result[$i]['shares_count'] = isset($shares_count[$this->result[$i]['id']]) ? $shares_count[$this->result[$i]['id']] : 0;
			}
			
			$this->result[$i]['area_percent'] = $total_area>0 ? ($this->result[$i]['area']/$total_area)*100 : 0;
			$this->result[$i]['clear_title'] = $this->result[$i]['title'];
			$this->result[$i]['title'] .= '</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.$this->result[$i]['area'].' га</div><div>';

			if($this->result[$i]['parent']){
				$areas[$this->result[$i]['parent']] = isset($areas[$this->result[$i]['parent']]) ? $areas[$this->result[$i]['parent']]+$this->result[$i]['area'] : $this->result[$i]['area'];
				$shares_count[$this->result[$i]['parent']] = isset($shares_count[$this->result[$i]['parent']]) ? $shares_count[$this->result[$i]['parent']]+$this->result[$i]['shares_count'] : $this->result[$i]['shares_count'];
			}
		}
	}
	
	
	
	protected function get_groups($groups, $names, $parent, $level, $exclude, $both_trees){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}


		foreach($items as $item)
		{
			if(in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)){ continue; }
			$children_g = $this->get_children_ids($groups, $item['_id'], 'g', isset($exclude['groups']) ? $exclude['groups'] : $exclude);
			$children_n = $this->get_children_ids($names, $item['_id'], 'n', isset($exclude['names']) ? $exclude['names'] : array());

			$this->result[$this->counter] = array(
				'id'	   => 'g'.$item['_id'],
				'title'    => $item['name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $level,
				'children_g' => $both_trees ? array_merge($children_g, $children_n) : $children_g,
				'children_n' => $children_n,
				'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
				'color'    => $item['color'],
				'parent_color' => $item[':parent:color'],
				'farm'    => $item['farm']
			);
			$this->counter++;
			$this->get_groups($groups, $names, $item['_id'], $level+1, $exclude, $both_trees);
		}
	}
	
	
	
	protected function get_children_ids($children, $item_id, $prepender, $exclude){
		$res = array();
		foreach($children as $child){
			if(in_array($child['_id'], $exclude)){ continue; }
			if($child[':parent:_id']==$item_id){ $res[] = $prepender.$child['_id']; }
		}
		return $res;
	}
	
	
	
	public function compose_full_name($record){
		$data = array($record['last_name'], $record['first_name'], $record['middle_name']);
		return implode(' ', $data);
	}
	
	
	
	public function get_properties(){
		$properties = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->execute();
		$t = array();
		foreach($properties as $property){
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
			if(($v instanceof Jelly_Model) and $v->loaded()) {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}else{
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}
		}
		return $t;
	}


	
	public function set_property($id, $property_name, $property_value = ''){
		$property = null;
        if($id){
            $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();
            if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		}
		if(!$id){
			$property = Jelly::factory('client_model_properties');
			$property->model 	= $this->_meta->model();
//			$property->license 	= $this->license;
			$property->name 	= $property_name;
			$property->save();
		}else{
            $property->name 	= $property_name;
			$property->save();
        }

		$value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
		if(!($value instanceof Jelly_Model) or !$value->loaded()){
			$value = Jelly::factory('client_model_values');
			$value->property 	= $property;
			$value->item_id 	= $this->id();
		}

		$value->value	 	= $property_value;
		$value->save();
	}



	public function delete_property($id){
		$property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();
		if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		Jelly::delete('client_model_values')->where('property', '=', $property->id())->execute();
		Jelly::delete('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->execute();
	}

	
	public function delete($key = NULL){
        //wtf? falling back to parent
        if (!is_null($key)) return parent::delete($key);
        
		//delete children
		$children_groups = Jelly::select('Client_ShareholderGroup')->where('parent', '=', $this->id())->execute();
		foreach($children_groups as $cg){
			$cg->delete();
		}
		$children_persons = Jelly::select('Client_Shareholder')->where('parent', '=', $this->id())->execute();
		foreach($children_persons as $cp){
			$cp->delete();
		}
		
		Jelly::delete('Client_ShareholderGroup')->where('_id', '=', $this->id())->execute();
    }
	
}