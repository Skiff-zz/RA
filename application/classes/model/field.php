<?php
class Model_Field extends Jelly_Model
{
	
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('fields')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
							'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения')),
							'license'		=> Jelly::field('BelongsTo',array(
								'foreign'	=> 'license',
								'column'	=> 'license',
								'label'		=> 'Лицензия'
							)),

							'name'						 => Jelly::field('String', array('label' => 'Название')),
							'title'						 => Jelly::field('String', array('label' => 'Тайтл')),
							'crop_rotation_number' => Jelly::field('String', array('label' => '№ в севообороте')),
							'number'					 => Jelly::field('String', array('label' => '№ поля')),
							'sector_number'			  => Jelly::field('String', array('label' => '№ участка')),
				
							'kadastr_area'	=>  Jelly::field('String', array('label' => 'Кадастровая площадь')),
							'area'	=>  Jelly::field('Float', array('label' => 'Посевная площадь')),
					
							'culture'		=> Jelly::field('BelongsTo',array(
								'foreign'	=> 'glossary_culture',
								'column'	=> 'culture',
								'label'		=> 'Культура'
							)),
							'culture_before'		=> Jelly::field('BelongsTo',array(
								'foreign'	=> 'glossary_culture',
								'column'	=> 'culture_before',
								'label'		=> 'Предшественник',
							)),
							'seed'	=> Jelly::field('BelongsTo',array(
									'foreign'	=> 'glossary_seed',
									'column'	=> 'glossary_seed_id',
									'label'		=> 'Семена'
							)),
							'productionclass'	=> Jelly::field('BelongsTo',array(
									'foreign'	=> 'glossary_productionclass',
									'column'	=> 'glossary_productionclass_id',
									'label'		=> 'Класс продукции'
							)),
							'production'	=> Jelly::field('BelongsTo',array(
									'foreign'	=> 'glossary_production',
									'column'	=> 'glossary_production_id',
									'label'		=> 'Продукция'
							)),
							'prolificness_plan' => Jelly::field('String', array('label' => 'Урожайность план.')),
							'prolificness_fact' => Jelly::field('String', array('label' => 'Урожайность факт.')),
				
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
							
                            'coordinates'                      => Jelly::field('Serialized', array('label' => 'Координаты точек')),
							'arrow_coordinates'				   => Jelly::field('Serialized', array('label' => 'Координаты стрелки')),
				
							'notes' => Jelly::field('HasMany',array(
								'foreign'	=> 'fieldnote',
								'label'	=> 'Заметки',
							)),
				

							//ПОЧВА
							'nitrogen'	=> Jelly::field('Float', array('label' => 'N (азот), %')),
							'phosphor'	=> Jelly::field('Float', array('label' => 'P (фосфор), %')),
							'potassium'	=> Jelly::field('Float', array('label' => 'K (калий), %')),
							'acidity'		=> Jelly::field('BelongsTo',array(
								'foreign'	=> 'glossary_acidity',
								'column'	=> 'acidity_id',
								'label'		=> 'pH (кислотность)'
							)),
							'acidity_ratio'	 => Jelly::field('String', array('label' => 'Уровень кислотности')),
							'ground_type'		=> Jelly::field('BelongsTo',array(
								'foreign'	=> 'glossary_groundtype',
								'column'	=> 'ground_type_id',
								'label'		=> 'Тип почвы'
							)),
				
							'works' => Jelly::field('HasMany',array(
								'foreign'	=> 'fieldwork',
								'label'	=> 'Наряды',
							))
			));
	}
	
	
	public function save($key = NULL){
		
		$numbers = array();
		
		$rn = arr::get($this->_changed, 'crop_rotation_number', false);
		if($rn===false) $rn = arr::get($this->_original, 'crop_rotation_number', '');
		
		$n = arr::get($this->_changed, 'number', false);
		if($n===false) $n = arr::get($this->_original, 'number', '');
		
		$sn = arr::get($this->_changed, 'sector_number', false);
		if($sn===false) $sn = arr::get($this->_original, 'sector_number', '');
		
		$name = arr::get($this->_changed, 'name', false);
		if($name===false) $name = arr::get($this->_original, 'name', '');

		
		if($rn) $numbers[] = $rn;
		if($n)  $numbers[] = $n;
		if($sn) $numbers[] = $sn;
		
		$this->_changed['title'] = implode('.', $numbers).($name ? ' '.$name : '');

        return parent::save($key);
    }


	public function get_tree($license_id, $filter = false, $sort = 'culture')
    {
		$session = Session::instance();
		$periods = $session->get('periods');
		if(!is_array($periods) || !count($periods)) $periods = array(-1);

        $elements = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('period', 'IN', $periods);


        $exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $license_id);
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $license_id);
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
		$cultures_tree = Jelly::factory('glossary_culturegroup')->get_tree($license_id, true, $exclude, 'items');
		$cultures_tree[] = array('id' => 'n0');
		$cultures_tree = array_reverse($cultures_tree);

		$farms = Jelly::factory('farm')->get_full_tree($license_id, 0, false, $filter);
		$formats = Jelly::factory('client_format')->get_formats($license_id);
        
        $elements =  $elements->execute()->as_array();
		for($i=0; $i<count($elements); $i++){
			$elements[$i]['title'] = $this->get_field_title($elements[$i], $formats);
		}
		foreach ($elements as $key => $row) {
			$title[$key]  = $row['title'];
		}
		if(count($elements))array_multisort($title, SORT_DESC, $elements);

		$squares = array();
		for($i=count($farms)-1; $i>=0; $i--){
			$farms[$i]['is_group_realy'] = true;
			$total_area = isset($squares[$farms[$i]['id']]) ? $squares[$farms[$i]['id']] : 0;
			
			if(!isset($squares[$farms[$i]['parent']])){
				$squares[$farms[$i]['parent']] = 0;
			}
				
				

			foreach($cultures_tree as $clt){

				

				for($j=0; $j<count($elements); $j++){
					if($elements[$j]['farm']==substr($farms[$i]['id'], 1) && $elements[$j][$sort]==substr($clt['id'], 1) && substr($clt['id'], 0, 1)=='n'){
						$farms[$i]['is_group'] = true;
						$farms[$i]['children_g'][] = 'f'.$elements[$j]['_id'];
						$culture = Jelly::select('glossary_culture', $elements[$j]['culture']);
						$predecessor = Jelly::select('glossary_culture', $elements[$j]['culture_before']);
						$seed = Jelly::select('glossary_seed', (int)$elements[$j]['seed']);
						$production = Jelly::select('glossary_production', (int)$elements[$j]['production']);
						$productionclass = Jelly::select('glossary_productionclass', (int)$elements[$j]['productionclass']);
						$total_area += $elements[$j]['area'];
						
						
//						if($elements[$j]['arrow_coordinates']){
//							print_r($elements[$j]['arrow_coordinates']); exit();
//						}
						
						array_splice($farms, $i+1, 0, array(array(
							'id'	   => 'f'.$elements[$j]['_id'],
							'title'    => $elements[$j]['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.$elements[$j]['area'].' га</div><div>',
							'is_group' => false,
							'is_group_realy' => false,
							'level'	   => $farms[$i]['level']+3,
							'children_g' => array(),
							'children_n' => unserialize($elements[$j]['coordinates']), //Костыль!
							'parent'   => 'n'.$elements[$j]['culture'],
							'color'    => $culture->color ? $culture->color : 'transparent',
							'parent_color' => $culture->color,
							'predecessor_color' => $predecessor->color ? $predecessor->color : 'EDEDED',
							'farm_color' => $farms[$i]['color'],
							'farm_id' => $farms[$i]['id'],
							'square' => $elements[$j]['area'],
							'kadastr_square' => $elements[$j]['kadastr_area'],
							'number' => $elements[$j]['number'],
							'complex_number' => $elements[$j]['title'],
							'culture_name' => $culture->title,
							'predecessor_name' => $predecessor->title,
							'seed_name' => $seed->name,
							'seed_color' => $seed->color,
							'production_color' => $productionclass->color,
							//'production_name' => trim($production->name.' '.$productionclass->name),
							'production_name' => trim($productionclass->name),
							'alt_parent' => 'n'.$elements[$j]['culture_before'],
							'arrow_coordinates' => unserialize($elements[$j]['arrow_coordinates'])
						)));
					}
				}

			}

			if($total_area>0)$farms[$i]['title'] = $farms[$i]['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.str_replace (',', '.', $total_area).' га</div><div>';
			$farms[$i]['square'] = str_replace (',', '.', $total_area);
			$squares[$farms[$i]['parent']] += $total_area;
		}
		return $farms;
	}

	public function get_field_title($field, $formats, $use_name=true){
		$arr = array();
		if(trim($field['crop_rotation_number']) && $formats['crop_rotation_n']) $arr[] = trim($field['crop_rotation_number']);
		if(trim($field['number']) && $formats['field_n']) $arr[] = trim($field['number']);
		if(trim($field['sector_number']) && $formats['sector_n']) $arr[] = trim($field['sector_number']);	
		$title = implode('.', $arr);
		
		if(trim($field['name']) && $formats['field_name']) $title .= ' '.trim($field['name']);
		return $title;
	}
	
	public function get_farm_field_list($farm_id){
		$periods = Session::instance()->get('periods');
		if(!is_array($periods) || !count($periods)) $periods = array(-1);

        $fields = Jelly::select('field')->where('deleted', '=', false)->and_where('farm', '=', $farm_id)->and_where('period', 'IN', $periods)->execute()->as_array();
		for($i=0; $i<count($fields); $i++){
			$fields[$i]['coordinates'] = unserialize($fields[$i]['coordinates']);
		}
		//print_r($fields); exit;
		return $fields;
	}

	public function delete($key = NULL)
    {
        //wtf? falling back to parent
        if (!is_null($key))
        {
            return parent::delete($key);
        }
        
		$this->deleted = 1;
    }
	
	
	
	
	
	
	public function get_work_grid_data($license_id){
		$result = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		//$farms_tree    = Jelly::factory('farm')->get_full_tree($license_id, 0, false, false);
		$formats = Jelly::factory('client_format')->get_formats($license_id);
		$fields_list   = Jelly::select('field')->with('culture')->with('farm')->with('works')
											   ->where('deleted', '=', false)
											   ->where('license', '=', $license_id)
											   ->where('farm', 'IN', $farms)
											   ->where('period', 'IN', $periods)
											   ->execute()->as_array();
		
		foreach($fields_list as &$fld){
			$fld['works'] = Jelly::select('fieldwork')->with('operation')->where('field', '=', $fld['_id'])->execute()->as_array();
		}
		
		foreach($fields_list as $field){ 
			foreach($field['works'] as $work){
				$fi = (int)$field[':farm:_id'];
				$ci = $fi.'|'.((int)$field[':culture:_id']);
				$pi = $ci.'|'.((int)$field['_id']);
				$oi = $pi.'|'.((int)$work[':operation:_id']);
				$wi = $oi.'|'.((int)$work['_id']);
				
				$pa = (float)$field['area'];

				if(!isset($result[$fi])){
					$result[$fi] = array(
						'farm_id' => $fi,
						'farm_name' => $field[':farm:name'],
						'farm_color' => $field[':farm:color'],
						'children' => array(),
						'processed' => 0,
						'processed_percents' => 0,
						'total_materials' => 0,
						'total_technics' => 0,
						'total_personals' => 0,
						'total' => 0
					);
				}

				if(!isset($result[$fi]['children'][$ci])){
					$result[$fi]['children'][$ci] = array(
						'culture_id' => $ci,
						'culture_name' => $field[':culture:title'],
						'culture_color' => $field[':culture:color'],
						'children' => array(),
						'processed' => 0,
						'processed_percents' => 0,
						'total_materials' => 0,
						'total_technics' => 0,
						'total_personals' => 0,
						'total' => 0
					);
				}

				if(!isset($result[$fi]['children'][$ci]['children'][$pi])){
					$result[$fi]['children'][$ci]['children'][$pi] = array(
						'field_id' => $pi,
						'field_name' => $field['title'],
						'field_color' => $field[':culture:color'],
						'field_area' => $field['area'],
						'children' => array(),
						'processed' => 0,
						'processed_percents' => 0,
						'total_materials' => 0,
						'total_technics' => 0,
						'total_personals' => 0,
						'total' => 0
					);
				}
				
				if(!isset($result[$fi]['children'][$ci]['children'][$pi]['children'][$oi])){
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi] = array(
						'operation_id' => $oi,
						'operation_name' => trim($work[':operation:name'])=='' ? 'Без операции' : $work[':operation:name'],
						'operation_color' => $work[':operation:color'],
						'children' => array(),
						'processed' => 0,
						'processed_percents' => 0,
						'total_materials' => 0,
						'total_technics' => 0,
						'total_personals' => 0,
						'total' => 0
					);
				}
				
				if(!isset($result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['children'][$wi])){
					$inputs_data = json_decode($work['inputs_data'], true);
					$inputs_data = $this->construct_inputs_data($inputs_data);
					setlocale(LC_ALL, "ru_RU");
					
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['children'][$wi] = array(
						'work_id' => $wi,
						'work_name' => $work['work_number'],
						'work_color' => $work['work_color'],
						//'date' => strftime("%d %B %Y", $work['work_date']),
						'date' => date("d.m.Y", $work['work_date']),
						'processed' => $work['processed'],
						'processed_percents' => $pa!=0 ? round(((float)$work['processed']/$pa)*100, 0) : 0,
						'total_materials' => $inputs_data['materials'],
						'total_technics' => $inputs_data['technics'],
						'total_personals' => $inputs_data['personals'],
						'total' => (float)$work['inputs']
					);
					
					$result[$fi]['total_materials'] += $inputs_data['materials'];
					$result[$fi]['children'][$ci]['total_materials'] += $inputs_data['materials'];
					$result[$fi]['children'][$ci]['children'][$pi]['total_materials'] += $inputs_data['materials'];
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['total_materials'] += $inputs_data['materials'];
					
					$result[$fi]['total_technics'] += $inputs_data['technics'];
					$result[$fi]['children'][$ci]['total_technics'] += $inputs_data['technics'];
					$result[$fi]['children'][$ci]['children'][$pi]['total_technics'] += $inputs_data['technics'];
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['total_technics'] += $inputs_data['technics'];
					
					$result[$fi]['total_personals'] += $inputs_data['personals'];
					$result[$fi]['children'][$ci]['total_personals'] += $inputs_data['personals'];
					$result[$fi]['children'][$ci]['children'][$pi]['total_personals'] += $inputs_data['personals'];
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['total_personals'] += $inputs_data['personals'];
					
					$result[$fi]['total'] += (float)$work['inputs'];
					$result[$fi]['children'][$ci]['total'] += (float)$work['inputs'];
					$result[$fi]['children'][$ci]['children'][$pi]['total'] += (float)$work['inputs'];
					$result[$fi]['children'][$ci]['children'][$pi]['children'][$oi]['total'] += (float)$work['inputs'];

				}
				
			}
		}

		//print_r($result); exit;
		return $result;
	}
	
	
	private function construct_inputs_data($inputs_data){
		$result = array('materials'=>0, 'technics'=>0, 'personals'=>0);

		if($inputs_data)
		foreach($inputs_data as $item){
			if($item['section']['id']=='s_technics'){
				$result['technics'] += (float)$item['total'];
			}else if($item['section']['id']=='s_personals'){
				$result['personals'] += (float)$item['total'];
			}else{
				$result['materials'] += (float)$item['total'];
			}
		}
		
		return $result;
	}
	
	
}