<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_OperationStageGroup extends Model_Glossary_AbstractGroup
{

    
    public static function initialize(Jelly_Meta $meta, $table_name = '', $items_model 	= '')
	{
		$meta->table('client_operationstagegroup')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
					))),

				'color'	=> Jelly::field('String', array('label' => 'Цвет')),

                'path'			=> Jelly::field('String', array('label' => 'Путь')),
                'parent'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operationstagegroup',
                        'column'	=> 'parent_id',
                        'label'		=> 'Родительская группа'
                )),	
                
                
                //stuff
                
				'license' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'license',
                        'column'	=> 'license_id',
                        'label'		=> 'Лицензия'
                ))
		));
	}
	
	
	public function get_tree($license_id, $with_names = false, $exclude = array(), $items_field = ''){
		$this->result = array();
		$this->counter = 0;

		$groups = Jelly::select('client_operationstagegroup')->with('parent')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('client_operationstage')->with('group')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, $names, 0, 0, $exclude, $with_names, 'group');

		if($with_names){
			
			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
                    if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':group:_id']==$group_id) {
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['name'],
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
    
    
    public function get_properties(){
		$properties = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->execute();
		
		$t = array();
		
		foreach($properties as $property){
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
			if(($v instanceof Jelly_Model) and $v->loaded()){
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}else
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
		}
		
		return $t;
	}
	
	public function set_property($id, $property_name, $property_value = '', $order = ''){
		$property = null;
        
        if($id){
            $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();
            
            if(!($property instanceof Jelly_Model) or !$property->loaded()){
                return;
            }
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

}

