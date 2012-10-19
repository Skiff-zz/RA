<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Model_Properties extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta )
	{
		$meta->table('client_model_properties')
			->fields(array(
				// Первичный ключ
				'_id'			=> Jelly::field('Primary'),
				'name'			=> Jelly::field('String', array('label' => 'Имя допсвойства')),
				'model'			=> Jelly::field('String', array('label' => 'Модель')),
                'license'	    => Jelly::field('BelongsTo',array(
										'foreign'	=> 'license',
										'column'	=> 'license_id',
										'label'		=> 'Лицензия'
								)),
				'period'	    => Jelly::field('BelongsTo',array(
										'foreign'	=> 'period',
										'column'	=> 'period_id',
										'label'		=> 'Период'
								))				
			 ));
	}
	
	public function get_properties($model_name, $id, $period = null)
	{
		$properties = Jelly::select('client_model_properties')->where('model', '=', $model_name)->where('license', '=', Auth::instance()->get_user()->license->id());
		
		if($period)
			$properties = $properties->where('period', '=', (int)$period);
		
		$properties = $properties->execute();

		$t = array();

		foreach($properties as $property)
		{
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $id)->load();
			
			if(($v instanceof Jelly_Model) and $v->loaded()) {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}
			else {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  '', '_id' => $property->id());
			}
		}

		return $t;
	}
	
	public function update_properties($model_name, $post, $field_prefix, $item_id, $add_prefix = null, $period = null)
	{
			$add = array();

			/*
			name_insert
			insert_property_
			
			
			name_chief_insert_1:чиф1
			insert_chief_property_1:чиф1_значение
			
			*/

			// Удаляем старые
			$properties = Jelly::factory($this->meta()->model())->get_properties($model_name, $item_id);

			foreach($properties as $property_id => $property)
			{
				if(!array_key_exists($field_prefix.'_'.$property_id, $post))
				{
					Jelly::factory($this->meta()->model())->delete_property($model_name, $property_id);
				}
			}

			//Новые допполя
			foreach($post as $key => $value)
			{
				if(UTF8::strpos($key, 'insert_'.$field_prefix.'_') !== false and UTF8::strpos($key, 'insert_'.$field_prefix.'_') == 0)
				{
					$property_id = (int)UTF8::str_ireplace('insert_'.$field_prefix.'_', '', $key);

					$add[$post['name_insert_'.($add_prefix ? $add_prefix.'_' : '').$property_id]] = $post['insert_'.$field_prefix.'_'.$property_id];
				}
			}

			foreach($add as $key => $value)
			{
				Jelly::factory($this->meta()->model())->set_property($model_name, 0, $key, $value, $item_id, $period);
			}

			// Старые допполя

			foreach($post as $key => $value)
			{
				if(UTF8::strpos($key, $field_prefix.'_') !== false)
				{
					$id = (int)UTF8::str_ireplace($field_prefix.'_', '', $key);

					if(array_key_exists($field_prefix.'_'.$id.'_label', $post))
					{
						  Jelly::factory($this->meta()->model())->set_property($model_name, $id, $post[$field_prefix.'_'.$id.'_label'], $post[$field_prefix.'_'.$id], $item_id, $period);
					}
				}
			}
	}
	
	public function set_property($model_name, $id, $property_name, $property_value = '', $item_id, $period = null)
	{
		$property = null;

        if($id)
        {
            $property = Jelly::select('client_model_properties')->where('model', '=', $model_name)->where('license', '=', Auth::instance()->get_user()->license->id())->where('_id', '=', (int)$id);
			
			if($period)
			{
				$property = $property->where('period', '=', $period);
			}
			
			$property = $property->load();

            if(!($property instanceof Jelly_Model) or !$property->loaded())
		    {
                return;
            }
		}

		if(!$id)
		{
			$property = Jelly::factory('client_model_properties');
			$property->model 	= $model_name;
			$property->license 	= Auth::instance()->get_user()->license->id();
			$property->name 	= $property_name;
			
			if($period)
			{
				$property->period = $period;
			}
			
			$property->save();
		}
        else
        {
            $property->name 	= $property_name;
			$property->save();
        }

		$value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $item_id)->load();
		if(!($value instanceof Jelly_Model) or !$value->loaded())
		{
			$value = Jelly::factory('client_model_values');
			$value->property 	= $property;
			$value->item_id 	= $item_id;
		}

		$value->value	 	= $property_value;
		$value->save();
	}

	public function delete_property($model_name, $id)
	{
		$property = Jelly::select('client_model_properties')->where('model', '=', $model_name)->where('license', '=', Auth::instance()->get_user()->license->id())->where('_id', '=', (int)$id)->load();

		if(!($property instanceof Jelly_Model) or !$property->loaded())
			return;

		Jelly::delete('client_model_values')->where('property', '=', $property->id())->execute();

		Jelly::delete('client_model_properties')->where('model', '=', $model_name)->where('_id', '=', (int)$id)->execute();

	}

}

