<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Handbook_Personal extends Model_Glossary_Abstract
{

	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_handbook_personal', $group_model = 'client_handbook_personalgroup')
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
				'farm'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm',
					'label'		=> 'Хозяйство',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'user'		    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'user',
							'column'	=> 'user_id',
							'label'		=> 'Связанный пользователь',
						)),


				'period'		=> Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_periodgroup',
					'column'	=> 'period_id',
					'label'		=> 'Период',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'card_number'	=> Jelly::field('String', array('label' => 'Личная карточка №')),
				'date'			=> Jelly::field('Integer', array('label' => 'Дата')),
				'surname'		=> Jelly::field('String', array('label' => 'Фамилия')),
				'name'			=> Jelly::field('String', array(
					'label' => 'Ф.И.О.'
				)),

				'name_fake'			=> Jelly::field('String', array('label' => 'Имя')),
				'patronymic'	=> Jelly::field('String', array('label' => 'Отчество')),

				'birth_date'			=> Jelly::field('Integer', array('label' => 'Дата рождения')),
				'birth_place'			=> Jelly::field('String', array('label' => 'Место рождения')),

				'position'	      	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'client_handbook_personalgroup',
														'column'	=> 'position_id',
														'label'		=> 'Должность'
													)),
				'department'		=> Jelly::field('String', array('label' => 'Отдел')),

				'email' => new Field_Email(array(
					'label' => 'E-mail',
					'rules' => array(
						'max_length' => array(1000),
						'min_length' => array(3)
					)
				)),
				'login'				=> Jelly::field('String', array('label' => 'Логин')),
				'password'			=> Jelly::field('String', array('label' => 'Пароль')),

				'phone'				=> Jelly::field('String', array('label' => 'Телефон')),
				'address'			=> Jelly::field('String', array('label' => 'Адрес')),

				'work_days_a_week'				=> Jelly::field('String', array('label' => 'Количество рабочих дней(в неделю)')),
				'work_hours_a_day'				=> Jelly::field('String', array('label' => 'Количество рабочих часов в сутки')),

				'work_hour_cost'				=> Jelly::field('String', array('label' => 'Стоимость за 1 час работы')),
				'work_hour_cost_units'	       		  => Jelly::field('BelongsTo',array(
											'foreign'	=> 'glossary_units',
											'column'	=> 'work_hour_cost_units_id',
											'label'		=> 'Единицы измерения'
									)),
				'salary'						=> Jelly::field('String', array('label' => 'Заработная плата')),
				'salary_units'	       		  => Jelly::field('BelongsTo',array(
											'foreign'	=> 'glossary_units',
											'column'	=> 'personal_payment_units_id',
											'label'		=> 'Единицы измерения'
									))
			 ));

	}

	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array(), $linked_with_names = true, $farm_id = null)
	{
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		
		if($farm_id)
		{
			$farms = array($farm_id);
		}

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];
		
		
		$names_unsorted = Jelly::select($model_name)->
				with($group_field)->with('farm')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				order_by('name', 'asc')->
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



        $names_groups = array();
		foreach($names_unsorted as $name){
			$names_groups[] = $name['group'];
			$names_groups = array_merge($names_groups, explode('/',$name[':group:path']));
		}
		$names_groups = array_unique($names_groups);

		if($linked_with_names){
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
		}else{
			$groups = Jelly::select($group_model_name)->
                with('parent')->
                where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                where('license', '=', $license_id)->
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
					'title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'farm_path'	=> $item['str_farm_path'],
					'parent'   => $item[':'.$group_field.':id_in_glossary'] ? 'gn'.$item[':'.$group_field.':id_in_glossary'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}

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

}