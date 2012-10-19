<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkType extends Jelly_Model{

	public static function initialize(Jelly_Meta $meta){
		
		$meta->table('planning_atk_types')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'name' => Jelly::field('String', array('label' => 'Название')),
				'color'	=> Jelly::field('String', array('label' => 'Цвет')),
				'order' => Jelly::field('Integer', array('label' => 'Порядок'))
			 ));
	}
	
	public function saveAtkTypes($atypes = array()){
		$ids = array();
		foreach($atypes as $atype){

			if(UTF8::strpos($atype['id'], 'new_') !== false){
				$atype_model = Jelly::factory('client_planning_atktype');
				$atype_model->color = $this->colors[rand(0, count($this->colors)-1)];
			}else{
				$atype_model = Jelly::select('client_planning_atktype', (int)$atype['id']);
			}

			$atype_model->name = $atype['name'];
			$atype_model->save();
			$ids[] = $atype_model->id();
		}

		if($ids){
			Jelly::delete('client_planning_atktype')->where('_id', 'NOT IN', $ids)->execute();
		}else{
			Jelly::delete('client_planning_atktype')->execute();
		}
	}
	
	private $colors = array('b45b00', 'efdfc2', 'e7b87e', 'cc7e10', 'a15000', '834000', 'ff2600', 'ffdbd5', 'ffa8a6', 'ff5b56', 'd92000', 'a91600', '00c700', 'd5fdd2', 'b9fca0',
                               '7bfa39', '2edf00', '00ae00', 'a62ecc', 'e7dafd', 'd8b9ff', 'c385f3', 'b639ff', '9626b0', '00b792', 'cefef0', 'a4fdea', '46fcd7', '00ddbd', '00977e', 'ff8200');
    
    
    
    public function get_tree(){
        $items = Jelly::select('client_planning_atktype')->order_by('order', 'asc')->execute()->as_array();
        $res = array();
        foreach($items as $item){
            $res[] = array(
                'id'	   => 'n'.$item['_id'],
                'title'    => $item['name'],
                'is_group' => true,
                'is_group_realy' => false,
                'level'	   => 0,
                'children_g' => array(),
                'children_n' => array(),
                'parent'   => '',
                'color'    => $item['color'],
                'parent_color' => $item['color']
            );
        }
        
        return $res;
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
        if (!is_null($key)){
            return parent::delete($key);
        }

		$this->deleted = true;
        $this->save();
    }
}