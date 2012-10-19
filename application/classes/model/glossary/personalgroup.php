<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_PersonalGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'glossary_personal_group', $items_model 	= 'glossary_personal')
	{
		return parent::initialize($meta, $table_name,  $items_model);
	}
	
	public function delete($key = null)
	{
		
		if($key)
		{
			// Получим все подгруппы
			$subgroups 		= Jelly::select('glossary_personalgroup')->where('path', 'LIKE', rtrim($this->path, '/').'/'.$key.'/%');
			$subgroup_ids 	= array();
			
			$subgroup_ids[] = $key;
			
			foreach($subgroups as $s)
			{
				$subgroup_ids[] = $s->id();
			}
			
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

