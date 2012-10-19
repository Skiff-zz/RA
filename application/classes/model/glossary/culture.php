<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Culture extends Model_Glossary_Abstract
{

	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_cultures', $group_model = 'glossary_culturegroup')
	{
		parent::initialize($meta,  $table_name, $group_model );

        $meta->table($table_name)
			->fields(array(
				'title'			=> Jelly::field('String', array('label' => 'Название')),
				'type'		=> Jelly::field('BelongsTo',array(
						'foreign'	=> 'glossary_culturetype',
						'column'	=> 'type_id',
						'label'		=> 'Тип',
					)),
				'crop_rotation_interest' =>  Jelly::field('String', array('label' => 'Процент в севообороте')),
			 ));

	}
	
	public function save($key = NULL, $stop_recursion=false)
	{
	    if((array_key_exists('name', $this->_changed) || array_key_exists('type', $this->_changed)) && !$stop_recursion)
        {
			$types = Jelly::select('glossary_culturetype')->execute()->as_array();
			if(count($types))
			{
				$id = (int)$this->_original['_id'];
				$old_name = $this->_original['name'];
				$new_name = array_key_exists('name', $this->_changed) ? $this->_changed['name'] : $old_name;
				$old_type = (int)$this->_original['type'];
				$new_type = (int)(array_key_exists('type', $this->_changed) ? $this->_changed['type'] : $this->_original['type']);

				$old_same_cultures = trim($old_name) ? Jelly::select('glossary_culture')->with('type')->where('deleted', '=', false)->and_where('name', 'LIKE', $old_name)->and_where('_id', '<>', $id)->execute() : array();
				$new_same_cultures = Jelly::select('glossary_culture')->with('type')->where('deleted', '=', false)->and_where('name', 'LIKE', $new_name)->and_where('_id', '<>', $id)->execute();


				//если переименовали и в группе раньше была ещё одна культура с таким именем и типом яр, то у неё нужно убрать тип из названия
				if(count($old_same_cultures)==1 && $old_same_cultures[0]->type->id()==$types[0]['_id']){
					$old_culture = $old_same_cultures[0];
					$old_culture->title = $old_culture->name;
					$old_culture->save(null, true);	
				}

				//если есть культуры с таким же названием
				if(count($new_same_cultures)){
					foreach($new_same_cultures as $new_same_culture){
						$new_same_culture->title = $new_same_culture->name.' '.$new_same_culture->type->name;
						$new_same_culture->save(null, true);
					}

				}
//				print_r($new_type.'-----'.$types[0]['_id']);
//				print_r($new_same_cultures[0]); exit;
				if(count($new_same_cultures) || $new_type!=$types[0]['_id']){
					$this->_changed['title'] = $new_name.' '.$this->getTypeName($new_type, $types);
				}else{
					$this->_changed['title'] = $new_name;
				}
			}
        }

        return parent::save($key);
    }



	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array(), $with_seeds = false){
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$groups = Jelly::select('glossary_culturegroup')->with('parent')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('glossary_culture')->with('group')->with('type')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		if($with_seeds) $seeds = Jelly::select('glossary_seed')->with('group')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$types = Jelly::select('glossary_culturetype')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':group:_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }

				$children_n = $with_seeds ? $this->get_children_seeds($seeds, $item['_id']) : array();
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['title'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'type'     => $item[':type:name'] ,
					'children_g' => array(),
					'children_n' => $children_n,
					'parent'   => $item[':group:_id'] ? 'g'.$item[':group:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':group:color'] ? $item[':group:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}

//		return $this->prepareResult($res, isset($types[0]) ? $types[0]['name'] : '');
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
	
		
	public function get_parent_path($group_id)
	{
		$culture = Jelly::select($this->meta()->model())->load($group_id);
		if($culture->group->id())
		{
			$culture->path = $culture->group->path.'/culture_'.$culture->id();
			return array_merge($culture->group->get_parent_path($culture->group->id()), array($culture));
		}
		else
		{
			$culture->path = '/culture_'.$culture->id().'/';
			return array($culture);
		}	
	}


	private function get_children_seeds($seeds, $culture_id){
		$res = array();
		foreach($seeds as $seed){
			if($seed[':group:_id']==$culture_id) $res[] = 's'.$seed['_id'];
		}
		return $res;
	}


	public function prepareResult($cultures, $def_type){
		
        //var_dump($cultures);
        //var_dump($def_type);
        
        $c = count($cultures) - 1;
        
        for($i = 0; $i <= $c; $i++)
        {
            $cultures[$i]['_original_title'] = $cultures[$i]['title'];

			if(substr($cultures[$i]['id'], 0, 1)=='g')continue;
            
            if($i + 1 <= $c and strtolower($cultures[$i]['title']) == strtolower($cultures[$i + 1]['title'])) 
            {
                $cultures[$i]['title'] = $cultures[$i]['_original_title'].' '.$cultures[$i]['type']; 
            }
            
            if($i - 1 >= 0 and strtolower($cultures[$i]['title']) == strtolower($cultures[$i - 1]['_original_title'])) 
            {
                $cultures[$i]['title'] = $cultures[$i]['_original_title'].' '.$cultures[$i]['type'];
            }
			
			if($cultures[$i]['type']!=$def_type)
            {
                $cultures[$i]['title'] = $cultures[$i]['_original_title'].' '.$cultures[$i]['type'];
            }
        }
        
		return $cultures;
	}

	//имя типа по айдишнику
	private function getTypeName($id, $types){
		foreach($types as $type) {
			if($type['_id']==$id){ return $type['name']; }
		}
		return '';
	}


	public function delete($key = NULL)
    {
        //wtf? falling back to parent
        if (!is_null($key)){
            return parent::delete($key);
        }

		$this->deleted = true;
        $this->save();


		$types = Jelly::select('glossary_culturetype')->execute()->as_array();

		if(!count($types)) return;

		$old_same_cultures = Jelly::select('glossary_culture')->with('type')->where('deleted', '=', false)->and_where('name', 'LIKE', $this->name)->and_where('type', '<>', $this->type->id())->execute();

		if(count($old_same_cultures)==1 && $old_same_cultures[0]->type->id()==$types[0]['_id']){
			$old_culture = $old_same_cultures[0];
			$old_culture->title = $old_culture->name;
			$old_culture->save();
		}
    }

}

