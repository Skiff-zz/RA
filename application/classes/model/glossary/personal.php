<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Personal extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_personal', $group_model = 'glossary_personalgroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
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
	}


	public function get_personal_subtree($personal_id, $personal_is_group){
		$res = array();
		$children_g = array();
		$children_n = array();
		if($personal_is_group){
			$children_g = Jelly::select('glossary_personalgroup')->where('deleted', '=', false)->and_where('parent', '=', $personal_id)->execute()->as_array();
			$children_n = Jelly::select('glossary_personal')->where('deleted', '=', false)->and_where('group', '=', $personal_id)->execute()->as_array();
		}

		foreach($children_g as $child){
			$res[] = array('_id'=>$child['_id'], 'is_group' => true);
			$res = array_merge($res, $this->get_personal_subtree($child['_id'], true));
		}

		foreach($children_n as $child){
			$res[] = array('_id'=>$child['_id'], 'is_group' => false);
		}

		return $res;
	}
	
	public function delete($key = null)
	{
		
		if($key)
		{
			$items = Jelly::select('glossary_personal')->where('group', 'IN', $subgroup_ids)->execute();
			
			$item_ids = array();
			
			foreach($items as $item)
			{
				$item_ids[] = $item->id();
			}
			
			$dictionary		 = Jelly::select('client_handbook_personalgroup')
									->where_open()
									->where('id_in_glossary', 'IN', $subgroup_ids)
									->where('is_position', '=', 0)
									->where_close()
									->or_where_open()
									->where('id_in_glossary', 'IN', $item_ids)
									->where('is_position', '=', 1)
									->or_where_close()
									->execute();
			
			$dictionary_ids = array();
			
			foreach($dictionary as $d)
			{
				$dictionary_ids[] = $d->id();
			}
			
			if(count($dictionary_ids))
			{						
				Jelly::update('client_handbook_personalgroup')->set(array('deleted' => 1))->where('_id', 'IN', $dictionary_ids)->execute();
				Jelly::update('client_handbook_personal')->set(array('group' => null))->where('group', 'IN', $dictionary_ids)->execute();
			}
			
			if(count($subgroup_ids))
			{
				Jelly::update('glossary_personal')->set(array('deleted' => 1))->where('group', 'IN', $subgroup_ids)->execute();
				Jelly::update('glossary_personalgroup')->set(array('deleted' => 1))->where('_id', 'IN', $subgroup_ids)->execute();
			}
		}
		
		return parent::delete($key);
	}

}

