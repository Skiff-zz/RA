<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Handbook_TechniqueMobile extends Model_Glossary_Abstract
{
    protected $model_name 		= 'client_handbook_techniquemobile';
	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_handbook_techniquemobile', $group_model = 'client_handbook_techniquemobilegroup')
	{
		parent::initialize($meta, $table_name, $group_model);

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
				'id_in_glossary'			=> Jelly::field('Integer', array('label' => 'Номер в таблице глоссария')),
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
				'gsm'	    => Jelly::field('ManyToMany',array(
							'foreign'	=> 'glossary_gsm',
							'label'		=> 'ГСМ',
							'through'   => 'gsm2tm'
				)),
				'grasp_width' => Jelly::field('String', array('label' => 'Ширина захвата')),
				'grasp_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'grasp_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'productivity' => Jelly::field('String', array('label' => 'Производительность')),
				'productivity_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'productivity_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'fuel_work' => Jelly::field('String', array('label' => 'Расход топлива (работа)')),
				'fuel_work_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fuel_work_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'fuel_idle' => Jelly::field('String', array('label' => 'Расход топлива (холостой ход)')),
				'fuel_idle_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fuel_idle_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'state_number' => Jelly::field('String', array('label' => 'Гос. номер')),
				'year_manufactured' => Jelly::field('String', array('label' => 'Год. выпуска')),
				'cost' => Jelly::field('String', array('label' => 'Стоимость')),
				'cost_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'cost_units_id',
							'label'		=> 'Единицы измерения'
					)),






				'max_power' => Jelly::field('String', array('label' => 'Макс. мощность')),
				'max_power_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'max_power_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'work_volume' => Jelly::field('String', array('label' => 'Рабочий объем двигателя')),
				'work_volume_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'work_volume_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'fuel_cosumption' => Jelly::field('String', array('label' => 'Удел. расход топлива')),
				'fuel_cosumption_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'fuel_cosumption_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'tank_capacity' => Jelly::field('String', array('label' => 'Емкость топливного бака')),
				'tank_capacity_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'tank_capacity_units_id',
							'label'		=> 'Единицы измерения'
					)),
				'oil_change_period' => Jelly::field('String', array('label' => 'Период замены масла')),
				'oil_change_period_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'oil_change_period_units_id',
							'label'		=> 'Единицы измерения'
					))


			 ));

	}

	public function add_nomenclature($model, $model_ids, $license_id, $farm_id){
		$ids_added = array();

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$_POST['period']           = (int)$periods[0];
		$period_id = (int)$periods[0];
		if($farm_id)
		{
			$selected_farm_obj = Jelly::select('farm',$farm_id);
			if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded())
			{
				Session::instance()->set('last_create_farm', (int)$farm_id);
			}
			else throw new Kohana_Exception('Хозяйства не существует');
		}


		foreach($model_ids as $id){

			$record = Jelly::select('glossary_techmobile',(int)$id);
			$parent = Jelly::select('client_handbook_techniquemobilegroup')->
					where('id_in_glossary','=',(int)($record->group->id()))->
					where('license','=',(int)$license_id)->
					where_open()->where('deleted','=',0)->or_where('deleted','IS',null)->where_close()->
					where('period','=',(int)$period_id)->
					execute();

			$buff = $record->as_array();
			$buff['license'] = $license_id;
			$buff['update_date'] = time();
			$buff['deleted'] = 0;
			$buff['farm'] = $selected_farm_obj;
			$buff['period'] = $period_id;
			$buff['id_in_glossary'] = $buff['_id'];
			unset($buff['id']);
			unset($buff['_id']);
			unset($buff['parent']);
			unset($buff['group']);
			unset($buff['group_id']);

			if ($parent) {
				$buff['group_id'] = $parent[0]->_id;
				$buff['group'] = $parent[0]->_id;
			} else {
				$buff['group'] = 0;
				$buff['group_id'] = 0;
			}
            
            $record_to = Jelly::factory('client_handbook_techniquemobile')->set($buff)->save();
            array_push($ids_added,$record_to->id());
            
            //  IMAGE TRANSFER
            // glossary
            $model_name = $record->meta()->model();
            $glo_subdir = floor( $id / 2000);
            $glo_images = array();
            $hbook_id = $record_to->id();
            if(is_dir(DOCROOT.Kohana::config('upload.path').'/'.$model_name.'/'.$glo_subdir))
            {
                $files = scandir(DOCROOT.Kohana::config('upload.path').'/'.$model_name.'/'.$glo_subdir);
                foreach($files as $file){
                    if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$model_name.'/'.$glo_subdir.'/'.$file) && ( !(strpos($file, 'item_'.$id.'_')===FALSE) || !(strpos($file, 'item_'.$id.'.')===FALSE)       )  ){
                        $glo_images[] = Kohana::config('upload.path').'/'.$model_name.'/'.$glo_subdir.'/'.$file;
                        
                        $subdir = floor($record_to->id() / 2000);

                        if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'))
                        {
                            @mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/');
                        }

                        if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
                        {
                            @mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir, 0777);
                        }
                        if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$hbook_id.'.jpg')){
                            rename(	DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$hbook_id.'.jpg',
                                    DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$hbook_id.'_changed_'.time().'_'.Text::random('alnum', 15).'.jpg');
                        }

                        
                        copy(DOCROOT.Kohana::config('upload.path').'/'.$model_name.'/'.$glo_subdir.'/'.$file, 
                                DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$hbook_id.'.jpg');
                        chmod(  DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$hbook_id.'.jpg', 0777);
                                
                    }
                }
            }
            
            
            
            
            
		}
		return $ids_added;
	}

	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];

                 // -------
		$names_unsorted = Jelly::select($model_name)->
				with($group_field)->with('farm')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				order_by('name', 'desc')->
				execute()->
				as_array();


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
                // -------

		$names_groups = array();
		foreach($names_unsorted as $name){
			$names_groups[] = $name['group'];
			$names_groups = array_merge($names_groups, explode('/',$name[':group:path']));
		}
		$names_groups = array_unique($names_groups);


		if(count($names_groups)==0){
			$groups = array();
		} else {
			$groups = Jelly::select($group_model_name)->
					with('parent')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('license', '=', $license_id)->
					and_where('_id', 'IN', $names_groups)->
					and_where('period', 'IN', $periods)->
					order_by('name', 'asc')->
					execute()->
					as_array();
		}

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'clear_title'    => $item['name'],
					'title'    => $item['name'].'</div>  <div style="color: #666666; height: 28px;">'.$item['state_number'].'</div><div>',
					'id_in_glossary' => $item['id_in_glossary'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'farm_path'	=> $item['str_farm_path'],
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}
		// -------
               $final = array();
                foreach($id_order as $i){
					$found = NULL;
                    foreach($res as $item){
                        if($item['id']=='n'.$i){
                           $found = $item;
                           break;
                        }
                    }
                    if ($found) array_push($final, $found);
                }
		return $final;
                // -------
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