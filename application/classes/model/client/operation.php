<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Operation extends Model_Glossary_Abstract
{
    public static function initialize(Jelly_Meta $meta, $table_name  = '', $group_model = '')
	{
		$meta->table('client_operation')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
					))),

				'color'	=> Jelly::field('String', array('label' => 'Цвет')),

                'group'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operationgroup',
                        'column'	=> 'group_id',
                        'label'		=> 'Группа'
                )),


                //основные поля
                'from_date' =>  Jelly::field('Integer', array('label' => 'Дата начала выполнения')),
                'to_date' =>  Jelly::field('Integer', array('label' => 'Дата конца выполнения')),

                'stages'      => Jelly::field('ManyToMany',array(
                    'label'	=> 'Стадии',
                    'foreign'	=> 'client_operationstage',
                    'through' => array(
                        'model'   => 'operations2stages',
                        'columns' => array('operation_id', 'stage_id'),
                    ),
                )),

                'cultures'      => Jelly::field('ManyToMany',array(
                    'label'	=> 'Культуры',
                    'foreign'	=> 'glossary_culture',
                    'through' => array(
                        'model'   => 'operations2cultures',
                        'columns' => array('operation_id', 'culture_id'),
                    ),
                )),

                'materials' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operations2materials',
                    'label'	=> 'Материалы',
                )),

                'technics' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operations2technics',
                    'label'	=> 'Техника',
                )),

                'personal' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_operations2personal',
                    'label'	=> 'Персонал',
                )),


                //stuff

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
				))
		));
	}

	public function get_tree($license_id, $group_field = '', $exclude = array(), $extras = false, $farms = array())
	{
		if(!count($farms)){
			$farms = Jelly::factory('farm')->get_session_farms();
			if(!count($farms)) $farms = array(-1);
		}

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$this->result = array();
		$this->counter = 0;
		$res = array();
        $it = 0;

		$names_unsorted = Jelly::select('client_operation')->with('group')->with('farm')->
															 where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
															 where('license', '=', $license_id)->
															 where('farm', 'IN', $farms)->
															 where('period', 'IN', $periods)->
															 order_by('name', 'asc')->execute()->as_array();

		$n_paths = array();
		for ($i=0;$i<count($names_unsorted);$i++){
			$n_paths[(string)($i)] = $names_unsorted[$i][':farm:path'].$names_unsorted[$i][':farm:_id'].'/';
		}
		asort($n_paths);

		$names = array();
		$id_order = array();
		$str_farms_path = array();
		foreach($n_paths as $i => $path){
			$path_farms = explode('/', $path);
			$path_farms_names = array();
			for($j=1;$j<count($path_farms)-1;$j++){
				$farm = Jelly::select('farm',(int)$path_farms[$j]);
				array_push($path_farms_names, $farm->name);
			}
			$str_farms_path[(string)($i)] = implode(', ', $path_farms_names);
			$names_unsorted[(int)($i)]['str_farm_path'] = $str_farms_path[(string)($i)];
			array_push($names, $names_unsorted[(int)($i)]);
			array_push($id_order, $names_unsorted[(int)($i)]['_id']);
		}

		$names_groups = array();
		foreach($names_unsorted as $name){
			$names_groups[] = $name['group'];
			$names_groups = array_merge($names_groups, explode('/',$name[':group:path']));
		}
		$names_groups = array_unique($names_groups);


		if(count($names_groups)==0){
			$groups = array();
		} else {
			$groups = Jelly::select('client_operationgroup')->
					with('parent')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('license', '=', $license_id)->
					where('_id', 'IN', $names_groups)->
					where('period', 'IN', $periods)->
					order_by('name', 'asc')->
					execute()->
					as_array();
		}

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':group:_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
                $from_date = (int)$item['from_date']>0 ? date('d.m.Y', $item['from_date']) : '';
                $to_date = (int)$item['to_date']>0 ? date('d.m.Y', $item['to_date']) : '';
				$res[$it] = array(
					'id'	   => 'n'.$item['_id'],
//                    'title'    => '<div style="float: left;">'.$item['name'].'</div></div>  '.
//			'<div style="font-size:14px; color: #444; width: 80px; height: 28px; text-align: right; overflow: hidden; display:-webkit-box; -webkit-box-flex: 1; -webkit-box-pack: end; margin-top:6px;">'.
//						$from_date.($from_date && $to_date ? ' - ' : '').$to_date.'</div><div>',
					'title'    => $item['name'].'</div>  '.
                                  '<div style="color: #666666; width:auto; height: 28px; padding-top:3px; padding-right:4px; text-align:right; font-size:12px;">'.
						$from_date.($from_date && $to_date ? '<br>- ' : '').$to_date.'</div>'.
                              '<div>',
					'clear_title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'farm_path'	=> $item['str_farm_path'],
					'parent'   => $item[':group:_id'] ? 'g'.$item[':group:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':group:color'] ? $item[':group:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
                if($extras) $res[$it]['extras'] = $this->get_operation_extras($item['_id']);
                $it++;
			}
		}

		// -------
//               $final = array();
//                foreach($id_order as $i){
//					$found = NULL;
//                    foreach($res as $item){
//                        if($item['id']=='n'.$i){
//                           $found = $item;
//                           break;
//                        }
//                    }
//                    if ($found) array_push($final, $found);
//                }
		return $res;
                // -------


	}

    public function get_operation_extras($operation_id){
        $result = array('materials'=>array(), 'technics'=>array(), 'personal'=>array());

        $operation = Jelly::select('client_operation', (int)$operation_id);
        if(!$operation instanceof Jelly_Model || !$operation->loaded()) return $result;

        $result['dates'] = array('from_date'=>$operation->from_date, 'to_date'=>$operation->to_date);

        foreach($operation->materials as $material){
			$m = Jelly::select('glossary_'.$material->material_model, (int)$material->material_id);
			if($m instanceof Jelly_Model && $m->loaded()){
				$mm = $material->material_model;
				$extras = Jelly::factory('client_transaction')->get_extras('glossary_'.$mm, (int)$material->material_id);

				if(!isset($result['materials'][$mm])) $result['materials'][$mm] = array();
				$result['materials'][$mm][] = array(
					'data' => array('id' => 'n'.$m->id(), 'title' => $m->name, 'color' => $m->color),
					'raw' => array('extras'=>array(
//						'crop_norm' => $extras['crop_norm'],
//						'crop_norm_units' => $extras['crop_norm_units'],
						'crop_norm' => $material->crop_norm,
						'crop_norm_units' => $material->crop_norm_units->id(),
						'units' => $extras['units']
					))
				);
			}
        }

		$result['technics'] = Jelly::factory('client_operations2technics')->prepare_technics($operation->technics->as_array());

        foreach($operation->personal as $personal){
            $result['personal'][] = array(
				'data'=>array('id'=>'n'.$personal->personal->id(), 'title'=>$personal->personal->name, 'color'=>$personal->personal->color),
				'raw'=>array('extras'=>array(
					'salary'=>$personal->salary,
					'salary_units'  => $personal->salary_units->id()
				))
			);$personal->personal->as_array();
        }

        return $result;
    }

	public function copyOperation($operation, $farm, $period){
        $operation = Jelly::select('client_operation', (int)$operation);
        if(!$operation instanceof Jelly_Model || !$operation->loaded()) return;

        $new_operation = Jelly::factory('client_operation');
        $arr = $operation->as_array();
        unset($arr['_id']);
		unset($arr['materials']);
		unset($arr['technics']);
		unset($arr['personal']);
        $new_operation->set($arr);
        $new_operation->name = UTF8::str_ireplace(' копия', '', $operation->name).' копия';
        $new_operation->farm = $farm;
        $new_operation->period = $period;
        $new_operation->save();

		for($i=0;$i<count($operation->materials);$i++){
			$material = $operation->materials[$i];
			$new_material = Jelly::factory('Client_Operations2Materials')->set(
					array(
						'operation' => $new_operation,
						'material_model' => $material->material_model,
						'material_id' => $material->material_id,
						'crop_norm' => $material->crop_norm,
						'crop_norm_units' => $material->crop_norm_units->id()
					)
			)->save();
		}

		for($i=0;$i<count($operation->technics);$i++){
			$technic = $operation->technics[$i];
			$new_technic = Jelly::factory('Client_Operations2Technics')->set(
					array(
						'operation' => $new_operation,
						'title' => $technic->title,
						'gsm' => $technic->gsm->id(),
						'cons_norm' => $technic->cons_norm,
						'cons_norm_units' => $technic->cons_norm_units->id()
					)
			)->save();

			// -----------

			for($j=0;$j<count($technic->mobile_block);$j++){
				$mobile_block = $technic->mobile_block[$j];
				$new_mobile_block = Jelly::factory('Client_OperationTechnicMobileBlock')->set(
						array(
							'operation' => $new_operation,
							'operation_technic'	=> $new_technic,
							'technic_mobile'	=> $mobile_block->technic_mobile,
							'in_main' => $mobile_block->in_main
						)
				)->save();
			}

			for($j=0;$j<count($technic->trailer_block);$j++){
				$trailer_block = $technic->trailer_block[$j];
				$new_trailer_block = Jelly::factory('Client_OperationTechnicTrailerBlock')->set(
						array(
							'operation' => $new_operation,
							'operation_technic'	=> $new_technic,
							'technic_trailer'	=> $mobile_block->technic_trailer,
							'in_main' => $mobile_block->in_main
						)
				)->save();
			}

			for($j=0;$j<count($technic->aggregates_block);$j++){
				$aggregates_block = $technic->aggregates_block[$j];
				$new_aggregates_block = Jelly::factory('Client_OperationTechnicAggregateBlock')->set(
						array(
							'operation' => $new_operation,
							'operation_technic'	=> $new_technic,

							'technic_mobile'	=> $aggregates_block->technic_mobile,
							'technic_trailer'	=> $aggregates_block->technic_trailer,

							'title' => $aggregates_block->title,
							'color' => $aggregates_block->color,


							'fuel_work' =>  $aggregates_block->color,
							'fuel_work_units'  => $aggregates_block->fuel_work_units->id(),

							'gsm' => $aggregates_block->gsm->id(),

							'fuel_work_secondary' =>  $aggregates_block->fuel_work_secondary,
							'fuel_work_units_secondary'  => $aggregates_block->fuel_work_units_secondary->id(),
							'in_main' => $aggregates_block->in_main,
							'checked' => $aggregates_block->checked
						)
				)->save();
			}

			// -----------
		}

		for($i=0;$i<count($operation->personal);$i++){
			$personal = $operation->personal[$i];
			$new_personal = Jelly::factory('Client_Operations2Personal')->set(
					array(
						'operation' => $new_operation,
						'personal'	=> $personal->personal,
						'salary' =>  $personal->salary,
						'salary_units'  => $personal->salary_units->id()
					)
			)->save();
		}
	}
}