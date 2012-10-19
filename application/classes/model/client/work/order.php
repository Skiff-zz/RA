<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Work_Order extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_work_orders')
	{
		/**
         *
         *
        if($table_name == '__abstract')
		{
			throw new Kohana_Exception('Abstract Client Model Should NOT be initialized directly!');
		}*/

		$meta->table($table_name)
			->fields(array(
				// Первичный ключ
				'_id'			=> Jelly::field('Primary'),
				'name'			=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'client_work_undevelopmentorder',
														'column'	=> 'undevelopmentorder_id',
														'label'		=> 'Название'
													)),
				'license'	    => Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия'
													)),
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
				'license'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия'
													)),
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm',
					'label'		=> 'Хозяйство'
				)),

				'period'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'period_id',
					'label'		=> 'Период'
				)),
				'color'			=> Jelly::field('String', array('label' => 'Цвет')),
			 ));
	}

	public function get_properties()
	{
		$properties = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->execute();

		$t = array();

		foreach($properties as $property)
		{
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
			if(($v instanceof Jelly_Model) and $v->loaded()) {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}
			else {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}


		}

		return $t;
	}

	public function set_property($id, $property_name, $property_value = '')
	{
		$property = null;

        if($id)
        {
            $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();

            if(!($property instanceof Jelly_Model) or !$property->loaded())
		    {
                return;
            }
		}

		if(!$id)
		{
			$property = Jelly::factory('client_model_properties');
			$property->model 	= $this->_meta->model();
//			$property->license 	= $this->license;
			$property->name 	= $property_name;
			$property->save();
		}
        else
        {
            $property->name 	= $property_name;
			$property->save();
        }

		$value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
		if(!($value instanceof Jelly_Model) or !$value->loaded())
		{
			$value = Jelly::factory('client_model_values');
			$value->property 	= $property;
			$value->item_id 	= $this->id();
		}

		$value->value	 	= $property_value;
		$value->save();
	}

	public function delete_property($id)
	{
		$property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->load();

		if(!($property instanceof Jelly_Model) or !$property->loaded())
			return;

		Jelly::delete('client_model_values')->where('property', '=', $property->id())->execute();

		Jelly::delete('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int)$id)->execute();

	}

	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		
		$names 	= Jelly::select($model_name)->where('deleted', '=', false)->order_by('name', 'asc');
		
		// Дополнительная фильтрация
		if($this->meta()->fields('license') and $this->meta()->fields('farm') and $this->meta()->fields('period'))
		{
			$farms = Jelly::factory('farm')->get_session_farms();
			if(!count($farms)) $farms = array(-1);
			$periods = Session::instance()->get('periods');
			if(!count($periods)) $periods = array(-1);
			
			$user = Auth::instance()->get_user();
			
			if($user)
			{
				$names  = $names->where('license', '=', $user->license->id());
			}
			
			$names  = $names->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
		}

		$names = $names->execute()->as_array();

		$this->get_groups(array(), 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				 $items[] = $name; 
			}

			foreach($items as $item) 
			{
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
					'clear_title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}
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

	public function delete($key = NULL)
    {
        //wtf? falling back to parent
        if (!is_null($key)){
            return parent::delete($key);
        }

		$this->deleted = true;
        $this->save();
    }

}

