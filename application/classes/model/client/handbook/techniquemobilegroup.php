<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Handbook_TechniqueMobileGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name = 'client_handbook_techniquemobilegroup', $items_model = 'client_handbook_techniquemobile')
	{
		$meta->table($table_name)
			->fields(array(
				'license'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',

                                                        'rules' => array(
                                        						'not_empty' => NULL
                                        				)

													)),
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm',
					'label'		=> 'Хозяйство',
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
				'id_in_glossary'			=> Jelly::field('Integer', array('label' => 'Номер в таблице глоссария'))
		));

		$p = parent::initialize($meta, $table_name,  $items_model);

		return $p;
	}

	public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items'){
		$this->result = array();
		$this->counter = 0;

		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];


		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				where('period', 'IN', $periods)->
				where('farm', 'IN', $farms)->
				order_by('name', 'asc')->
				execute()->
				as_array();
        
		$names_groups = array();
		foreach($names as $name){
			$names_groups[] = $name['group'];
			$names_groups = array_merge($names_groups, explode('/',$name[':group:path']));
		}
        $new_ngr = array();
        foreach($names_groups as $ngr){
            if((int)$ngr>0){
                $new_ngr[] = $ngr;
            }
        }
        $names_groups = $new_ngr;
		$names_groups = array_unique($names_groups);
		if(count($names_groups)==0){
			$groups = array();
		} else {
			$groups = Jelly::select($this->meta()->model())->with('parent')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				where('period', 'IN', $periods)->
				where('_id', 'IN', $names_groups)->
				order_by('name', 'asc')->
				execute()->
				as_array();
		}
        
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
							'id_in_glossary' => $name['id_in_glossary'],
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

	protected function get_groups($groups, $names, $parent, $level, $exclude, $with_cultures, $relation){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}


		foreach($items as $item)
		{
			if(in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)){ continue; }
			$children_g = $this->get_children_ids($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude);
			$children_n = $this->get_children_ids($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array());
            
            $children_n_all = $this->get_all_children_ids($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array(), TRUE);
            
			$this->result[$this->counter] = array(
				'id'	   => 'g'.$item['_id'],
				'clear_title'    => $item['name'],
                'title'    => $item['name'].'</div> <div style=\"color: #666666; height: 28px; padding-top:3px; padding-right:4px;\">'.count($children_n_all).'</div><div>',
				'id_in_glossary' => $item['id_in_glossary'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $level,
				'children_g' => $with_cultures ? array_merge($children_g, $children_n) : $children_g,
				'children_n' => $children_n,
				'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
				'color'    => $item['color'],
				'parent_color' => $item[':parent:color']
			);
			$this->counter++;
			$this->get_groups($groups, $names, $item['_id'], $level+1, $exclude, $with_cultures, $relation);
		}
	}


	protected function get_children_ids($children, $item_id, $is_group, $relation, $exclude){
		$res = array();
		foreach($children as $child){
			if(in_array($child['_id'], $exclude)){ continue; }
			if($is_group && $child[':parent:_id']==$item_id){ $res[] = 'g'.$child['_id']; }
			if(!$is_group && $child[':'.$relation.':_id']==$item_id){ $res[] = 'n'.$child['_id']; }
		}
		return $res;
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


	public function get_branch() {


	}
    
    public function add_nomenclature($model, $model_ids, $license_id, $farm_id){

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$_POST['period']           = (int)$periods[0];
		$period_id           = (int)$periods[0];

		if($farm_id)
		{
			$selected_farm_obj = Jelly::select('farm',$farm_id);
			if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded())
			{
				Session::instance()->set('last_create_farm', (int)$farm_id);
			}
			else throw new Kohana_Exception('Хозяйства не существует');
		}


		$root_elements = array();
		foreach($model_ids as $id){
			$record = Jelly::select($model)->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();
			$p = explode("/",$record->path);
            
            
			$parent = $p[count($p)-3];

			if (!(  in_array($parent,$model_ids)  )) {
				array_push($root_elements,$record->id());
			}
		}
		$model_ids = array_diff($model_ids, $root_elements);

		$new_root_elements = array();
		foreach($root_elements as $id){
			$record = Jelly::select($model)->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();
			$p = explode("/",$record->path);
            
            
			$parent = $p[count($p)-3];

			while ($parent) {
				array_push($model_ids,$record->id());
				$record = Jelly::select($model)->with($this->group_field)->where(':primary_key', '=', (int)$parent)->load();
				$p = explode("/",$record->path);
                
               
				$parent = $p[count($p)-3];
			}
			array_push($new_root_elements,$record->id());
		}

		$model_ids = array_merge($model_ids, $root_elements);
		$model_ids = array_unique($model_ids);
		$model_ids = array_diff($model_ids, $new_root_elements);

		foreach($new_root_elements as $id){

			$record = Jelly::select($model)->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();
			$p = explode("/",$record->path);
            
            
            
			$parent = $p[count($p)-3];
			$buff = $record->as_array();
			$buff['license'] = $license_id;
			$buff['update_date'] = time();
			$buff['farm'] = $selected_farm_obj;
			$buff['period'] = $_POST['period'];
			$buff['id_in_glossary'] = $buff['_id'];
			unset($buff['parent']);
			unset($buff['group']);
			unset($buff['id']);
			unset($buff['_id']);
            unset($buff['items']);
            unset($buff['path']);

			$record_to = Jelly::select('client_handbook_techniquemobilegroup')
                    ->with($this->group_field)
                    ->where('id_in_glossary', '=', $record->id())
                    ->where('license', '=', $license_id)
//					->where('farm', '=', $farm_id)
                    ->where('period', '=', $period_id)
                    ->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
                    ->limit(1)
                    ->execute();
            
			if ($record_to && $record_to->id()) {
				$record_to->set($buff);
				$record_to->save();
			} else {
				$parent_group = false;
				if ($parent){
					$parent_glossary = Jelly::select($model,$parent);
					$parent_group = Jelly::select('client_handbook_techniquemobilegroup')->
							where('id_in_glossary', '=', $parent_glossary->id())->
							and_where('license','=',$license_id)->
							where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
//							where('farm', '=', $farm_id)->
							where('period', '=', $period_id)->
                            limit(1)->
							execute();
				}

				if ($parent_group && $parent_group->id()) {
					$buff['parent'] = $parent_group;
                    
                    $buff['path'] = $parent_group->path;
				}else{
                    $buff['path'] = '/';
                }
				$host_record = Jelly::factory('client_handbook_techniquemobilegroup')->set($buff)->save();
			}
		}
        
		for($i=0;$i<count($model_ids);$i++){
			$id = $model_ids[$i];

			$record = Jelly::select($model)->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();
			$p = explode("/",$record->path);
            
            
			$parent = $p[count($p)-3];


			$buff = $record->as_array();

			$buff['license'] = $license_id;
			$buff['farm'] = $selected_farm_obj;
			$buff['period'] = $_POST['period'];
			$buff['update_date'] = time();
			$buff['id_in_glossary'] = $buff['_id'];
			unset($buff['id']);
			unset($buff['_id']);
            unset($buff['path']);
            unset($buff['parent']);
            unset($buff['items']);

			$record_to = Jelly::select('client_handbook_techniquemobilegroup')
                    ->with($this->group_field)
                    ->where('id_in_glossary', '=', $record->id())
                    ->where('license', '=', $license_id)
//					->where('farm', '=', $farm_id)
                    ->where('period', '=', $period_id)
                    ->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
                    ->limit(1)
                    ->execute();
            
			if ($record_to && $record_to->id()) {
				$record_to->set($buff);
				$record_to->save();
			} else {
				$parent_group = false;
				if ($parent){
					$parent_glossary = Jelly::select($model,$parent);
					$parent_group = Jelly::select('client_handbook_techniquemobilegroup')->
							where('id_in_glossary', '=', $parent_glossary->id())->
							and_where('license','=',$license_id)->
							where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
//							where('farm', '=', $farm_id)->
							where('period', '=', $period_id)->
                            limit(1)->
							execute();
				}

				if ($parent_group && $parent_group->id()) {
                        $buff['parent'] = $parent_group;
                        
                        
                        
                        $buff['path'] = $parent_group->path;
				} else {
                        $buff['path'] = '/';
						if (in_array($parent,$model_ids)) {
                                array_push($model_ids,$record->id() );
                                continue;
                        }
				}
				$host_record = Jelly::factory('client_handbook_techniquemobilegroup')->set($buff)->save();
			}
		}
	}


}
