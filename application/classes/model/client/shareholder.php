<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Shareholder extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('shareholders')
			->fields(array(
				
				'_id' => new Field_Primary,
				
				'first_name'	=> Jelly::field('String', array('label' => 'Имя',
					'rules' => array(
						'not_empty' => NULL
					))),
				
				'last_name'	=> Jelly::field('String', array('label' => 'Фамилия',
					'rules' => array(
						'not_empty' => NULL
					))),

				'middle_name'	=> Jelly::field('String', array('label' => 'Отчество')),

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
				)),
				
				'address'	=> Jelly::field('String', array('label' => 'Адрес')),
				'passport'	=> Jelly::field('String', array('label' => 'Паспорт')),
				'code'	=> Jelly::field('String', array('label' => 'Код')),
				

		));
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
		$children_shares = Jelly::select('Client_Share')->where('shareholder', '=', $this->id())->execute();
		foreach($children_shares as $cs){
			$cs->delete();
		}

		
		Jelly::delete('Client_Shareholder')->where('_id', '=', $this->id())->execute();
    }
	
}

