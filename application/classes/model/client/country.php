<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Country extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_country', $group_model = 'client_countrygroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
				'capital'	=> Jelly::field('String', array('label' => 'Столица')),
				'currency'	=> Jelly::field('String', array('label' => 'Валюта')),
                'countrycode'	=> Jelly::field('String', array('label' => 'Код страны'))
			 ));
	}
    
    protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];
		
		$groups = Jelly::select($group_model_name)->with('parent')->where('deleted', '=', false)->order_by('name', 'asc');
		$names 	= Jelly::select($model_name)->with($group_field)->where('deleted', '=', false)->order_by('name', 'asc');
		
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
				$groups = $groups->where('license', '=', $user->license->id());
				$names  = $names->where('license', '=', $user->license->id());
			}
			
			$groups = $groups->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
			$names  = $names->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
		}

		$groups = $groups->execute()->as_array();
		$names = $names->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) 
			{
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
                    'countrycode'    => $item['countrycode'],
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
}

