<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_CountryGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'client_countrygroup', $items_model 	= 'client_country')
	{
		return parent::initialize($meta, $table_name,  $items_model);
	}
    
    protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items'){
		$this->result = array();
		$this->counter = 0;
		
		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];
		
		$groups = Jelly::select($this->meta()->model())->with('parent')->where('deleted', '=', false)->order_by('name', 'asc');
		$names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->where('deleted', '=', false)->order_by('name', 'asc');
		
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

		$this->get_groups($groups, $names, 0, 0, $exclude, $with_cultures, $items_model->foreign['column']);

		if($with_cultures){
			
			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':'.$items_model->foreign['column'].':_id']==$group_id) {
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['name'],
                            'countrycode'    => $name['countrycode'],
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
}
