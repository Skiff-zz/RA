<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Share extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('shares')
			->fields(array(
				
				'_id' => new Field_Primary,
				
				'share_number'	=> Jelly::field('String', array(
					'label' => 'Номер пая',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				'coordinates'   => Jelly::field('Serialized', array('label' => 'Координаты точек')),
				
				'kadastr_area'	=>  Jelly::field('String', array('label' => 'Кадастровая площадь')),
				'area'	=>  Jelly::field('String', array('label' => 'Расчётная площадь')),
				
				'gov_act_number'	=>  Jelly::field('String', array('label' => 'Номер гос. акта')),
				'tech_doc'	=>  Jelly::field('String', array('label' => 'Технический документ')),
				'kadastr_number'	=>  Jelly::field('String', array('label' => 'Кадастровый номер')),
				
				'field'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'field',
					'column'	=> 'field_id',
					'label'	=> 'Поле',
					'rules' => array(
						//'not_empty' => NULL
					)
				)),
				
				'shareholder'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_shareholder',
					'column'	=> 'client_shareholder_id',
					'label'	=> 'Пайщик',
					'rules' => array(
						//'not_empty' => NULL
					)
				)),
				
				'status'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_sharestatus',
					'column'	=> 'client_sharestatus_id',
					'label'	    => 'Статус'
				)),
				
				'announce_date'	=>  Jelly::field('Integer', array('label' => 'Дата заявления')),
				
				
				
				'order_number'	=>  Jelly::field('String', array('label' => 'Номер договора')),
				'order_date'	=>  Jelly::field('Integer', array('label' => 'Дата договора')),
				'order_act_date'	=>  Jelly::field('Integer', array('label' => 'Акт дата договора')),
				
				
				
				'registration_number'	=>  Jelly::field('String', array('label' => 'Номер регистрации')),
				'registration_date'	=>  Jelly::field('Integer', array('label' => 'Дата регистрации')),
				
				
				
				'rent_start_date'	=>  Jelly::field('Integer', array('label' => 'Дата начала аренды')),
				'rent_end_date'	=>  Jelly::field('Integer', array('label' => 'Дата окончания аренды')),
				'recision_right'	=>  Jelly::field('Integer', array('label' => 'Право расторжения')),
				
				
				'payments' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_sharepayment',
                    'label'	=> 'Арендная плата'
                )),
				
				
				'license' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'license',
                        'column'	=> 'license_id',
                        'label'		=> 'Лицензия',
						'rules' => array(
							'not_empty' => NULL
						)
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
				)),
				
				'last_status_update'	=>  Jelly::field('Integer', array('label' => 'Последнее обновление статуса')),
				'last_alert_show'	=>  Jelly::field('Integer', array('label' => 'Последний показ предупреждения'))

		));
	}
	
	

	public function update_share_status($share_alert_period, &$record){
		$todays_midnight = (int)strtotime(date('d F Y'));
		if(((int)$record['last_status_update'])>$todays_midnight)return;
		
		$now_time = time();
		$record['last_status_update'] = $now_time;
		if($record['rent_end_date']>($now_time+$share_alert_period['seconds'])){
			$color = '98cc6b';//green
			$id = 1;
			$name = 'Активный';
		}else if($record['rent_end_date']>$now_time){
			$color = 'f3bf6d';//yellow
			$id = 2;
			$name = 'Срок аренды подходит к окончанию';
		}else{
			$color = 'e64744';//red
			$id = 3;
			$name = 'Срок аренды истёк';
		}
		
		$record[':status:_id'] = $id;
		$record[':status:name'] = $name;
		$record[':status:color'] = $color;
		
		$item = Jelly::select('client_share', $record['_id']);
		$item->status = $id;
		$item->last_status_update = $now_time;
		$item->save();
	}


	
	
	
	public function get_share_tree($license_id, $sort){
		$filter = true;
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$share_alert_period = Jelly::factory('Client_ShareAlertPeriod')->get_period($license_id);
		
		$records = Jelly::select('client_share')->with('status')->with('shareholder')->where('license', '=', $license_id)->where('period', '=', $periods[0])->where('farm', 'IN', $farms)->order_by('share_number', 'desc')->execute()->as_array();
		$farms = Jelly::factory('farm')->get_full_tree($license_id, 0, false, $filter);
		
		$this->total_area = 0.0;
		if($sort=='shareholder'){
			for($i=count($farms)-1; $i>=0; $i--){
				$farms[$i]['is_group_realy'] = true;
				$farms[$i]['field_list'] = Jelly::factory('field')->get_farm_field_list(substr($farms[$i]['id'], 1));
				$farms[$i]['field_list'] = json_encode($farms[$i]['field_list']);

				foreach($records as $record){
					if($record['farm'] == substr($farms[$i]['id'], 1)){
						$farms[$i]['children_g'][] = 's'.$record['_id'];
						$this->total_area += (float)$record['area'];
						$this->update_share_status($share_alert_period, $record);
						array_splice($farms, $i+1, 0, array(array(
							'id'	   => 's'.$record['_id'],
							'title'    => '<div class="title-x">'.$record['share_number'].'</div><div class="sub-title-x">'.($this->get_subtitle($record)).'</div>',
							'number' => $record['share_number'],
							'coordinates' => unserialize($record['coordinates']),
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $farms[$i]['level']+1,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => 'n'.$record[':shareholder:_id'],
							'color'    => $record[':status:color'] ? $record[':status:color'] : 'fff',
							'parent_color' => $record[':shareholder:color'],
							'alt_parent' => 'n'.date('Y-m-d', $record['rent_end_date']),
							'farm' => $farms[$i]['id'],
							'area' => (float)$record['area'],
							'shares_count' => 1,
							'rent_end_date_txt' => date('d.m.Y', $record['rent_end_date']),
							'popup_title' => '<div class="popup-title-1" style="color:#000 !important; background-color:#8da5c4 !important;">Дата окончания договора <span>'.date('d.m.Y', $record['rent_end_date']).'</span></div><div class="popup-title-2" style="color:#000 !important; background-color:#a3d4a4 !important;">До окончания аренды <span>'.($this->get_rent_end_remains($record['rent_end_date'])).'</span></div>'
						)));
					}
				}

			}
		}else{
			$shareholders = Jelly::factory('client_shareholdergroup')->get_shareholder_tree($license_id);
			
			for($i=count($farms)-1; $i>=0; $i--){
				$farms[$i]['is_group_realy'] = true;
				$farms[$i]['field_list'] = Jelly::factory('field')->get_farm_field_list(substr($farms[$i]['id'], 1));
				$farms[$i]['field_list'] = json_encode($farms[$i]['field_list']);

				$subrecords = $this->get_farm_subrecords($shareholders, $farms[$i]);
				if(count($subrecords))array_splice($farms, $i+1, 0, $subrecords);
			}
			
			for($i=count($farms)-1; $i>=0; $i--){
				if(substr($farms[$i]['id'],0,2)=='nn'){
					$subrecords = $this->get_shareholder_subrecords($records, $farms[$i], $share_alert_period);
					if(count($subrecords))array_splice($farms, $i+1, 0, $subrecords);
				}
			}
		}
		
		return $this->construct_extras($farms, $sort);
	}
	
	
	private $total_area = 0.0;
	
	private function construct_extras($records, $sort){
		$areas = array();
		$shares_count = array();
		for($i=count($records)-1; $i>=0; $i--){
			if(!isset($records[$i]['area'])){
				$records[$i]['area'] = isset($areas[$records[$i]['id']]) ? $areas[$records[$i]['id']] : 0.00;
				$records[$i]['shares_count'] = isset($shares_count[$records[$i]['id']]) ? $shares_count[$records[$i]['id']] : 0;
			}
			
			$records[$i]['area_percent'] = $this->total_area>0 ? ($records[$i]['area']/$this->total_area)*100 : 0;
			$records[$i]['clear_title'] = $records[$i]['title'];
			$records[$i]['title'] .= '</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.$records[$i]['area'].' га</div><div>';

			if($sort=='shareholder' && substr($records[$i]['id'], 0, 1)=='s'){
				if($records[$i]['farm']){
					$areas[$records[$i]['farm']] = isset($areas[$records[$i]['farm']]) ? $areas[$records[$i]['farm']]+$records[$i]['area'] : $records[$i]['area'];
					$shares_count[$records[$i]['farm']] = isset($shares_count[$records[$i]['farm']]) ? $shares_count[$records[$i]['farm']]+$records[$i]['shares_count'] : $records[$i]['shares_count'];
				}
			}else{
				if($records[$i]['parent']){
					$areas[$records[$i]['parent']] = isset($areas[$records[$i]['parent']]) ? $areas[$records[$i]['parent']]+$records[$i]['area'] : $records[$i]['area'];
					$shares_count[$records[$i]['parent']] = isset($shares_count[$records[$i]['parent']]) ? $shares_count[$records[$i]['parent']]+$records[$i]['shares_count'] : $records[$i]['shares_count'];
				}
			}
		}
		
		return $records;
	}

	
	private function get_shareholder_subrecords($shares, &$shareholder, $share_alert_period){
		$data = array();
		$shareholder_id = substr($shareholder['id'], 2);
		
		foreach($shares as $share){
			if($share['shareholder']==$shareholder_id){
				$this->update_share_status($share_alert_period, $share);
				$data[] = array(
					'id'	   => 's'.$share['_id'],
					'title'    => '<div class="title-x">'.$share['share_number'].'</div><div class="sub-title-x">'.($this->get_subtitle($share)).'</div>',
					'number' => $share['share_number'],
					'coordinates' => unserialize($share['coordinates']),
					'is_group' => true,
					'is_group_realy' => false,
					'level'	   => $shareholder['level']+1,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $shareholder['id'],
					'color'    => $share[':status:color'] ? $share[':status:color'] : 'fff',
					'parent_color' => $shareholder['color'],
					'alt_parent' => 'n'.date('Y-m-d', $share['rent_end_date']),
					'area' => $share['area'],
					'shares_count' => 1,
					'rent_end_date_txt' => date('d.m.Y', $share['rent_end_date']),
					'popup_title' => '<div class="popup-title-1" style="color:#000 !important; background-color:#8da5c4 !important;">Дата окончания договора <span>'.date('d.m.Y', $share['rent_end_date']).'</span></div><div class="popup-title-2" style="color:#000 !important; background-color:#a3d4a4 !important;">До окончания аренды <span>'.($this->get_rent_end_remains($share['rent_end_date'])).'</span></div>'
				);
				
				$this->total_area += (float)$share['area'];
				$shareholder['children_g'][] = 's'.$share['_id'];
			}
		}
		
		return $data;
	}
	
	
	
	
	
	private function get_farm_subrecords($shareholders, &$farm){
		$data = array();
		$farm_id = substr($farm['id'], 1);
		
		foreach($shareholders as $shareholder){
			if($shareholder['farm']==$farm_id){
				if($shareholder['parent']=='g-2'){
					$shareholder['level'] = $farm['level']+1;
				}else{
					$shareholder['level'] += $farm['level']+1;
				}
				if($shareholder['parent']=='' || $shareholder['parent']=='g-2'){
					$shareholder['parent'] = $farm['id'];
					$shareholder['parent_color'] = $farm['color'];
				}else{
					$shareholder['parent'] = substr($shareholder['parent'], 0, 1).$shareholder['parent'];
				}
				
				$shareholder['id'] = substr($shareholder['id'], 0, 1).$shareholder['id'];
				//$shareholder['children_g'] = array_merge($shareholder['children_g'], $shareholder['children_n']);
				if(substr($shareholder['id'], 0, 1)=='n') $shareholder['children_g'] = array_merge($shareholder['children_n'], array());
				$shareholder['children_n'] = array();
				for($i=0; $i<count($shareholder['children_g']); $i++){
					$shareholder['children_g'][$i] = substr($shareholder['children_g'][$i], 0, 1).$shareholder['children_g'][$i];
				}
				
				if($shareholder['parent']==$farm['id']) $farm['children_g'][] = $shareholder['id'];
				$shareholder['is_group_realy'] = true;
				$farm['children_n'] = array();
				$data[] = $shareholder;
			}
		}
		
		return $data;
	}
	
	
	
	
	
	public function get_date_tree($license_id){
		$result = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$records = Jelly::select('client_share')->where('license', '=', $license_id)->where('period', '=', $periods[0])->where('farm', 'IN', $farms)->order_by('rent_end_date')->execute()->as_array();
		
		$years = $this->get_shares_years($records);
		foreach($years as $year) {
				$months = $this->get_share_months($records, $year);
				$year_color = $this->get_color('year', $year);
				$result[] = array(
					'id'	   => 'g'.$year,
					'title'    => $year,
					'is_group' => true,
					'is_group_realy' => true,
					'level'	   => 0,
					'children_g' => $this->get_children_month_ids($months, $year),
					'children_n' => array(),
					'parent'   => '',
					'color'    => $year_color,
					'parent_color' => 'FFFFFF'
				);
				foreach($months as $month) {
					$days = $this->get_share_days($records, $year, $month);
					$month_color = $this->get_color('month', $month);
					$result[] = array(
						'id'	   => 'g'.$year.'-'.$month,
						'title'    => mb_convert_case(ACDate::$m_names[(int)$month], MB_CASE_TITLE),
						'is_group' => true,
						'is_group_realy' => true,
						'level'	   => 1,
						'children_g' => $this->get_children_days_ids($days, $month, $year),
						'children_n' => array(),
						'parent'   => 'g'.$year,
						'color'    => $month_color,
						'parent_color' => $year_color,
						'filter' => 'm'.$month.'.'.$year
					);
					foreach($days as $day){
						$day_color = $this->get_color('day', $day);
						$result[] = array(
							'id'	   => 'n'.$year.'-'.$month.'-'.$day,
							'title'    => $day,
							'is_group' => true,
							'is_group_realy' => true,
							'level'	   => 2,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => 'g'.$year.'-'.$month,
							'color'    => $day_color,
							'parent_color' => $month_color,
							'filter' => $day.'.'.$month.'.'.$year
						);
					}
				}
		}
		return $this->construct_dates_extras($records, $result);
	}
	
	
	private function construct_dates_extras($shares, $dates){
		$total_area = 0.0;
		$areas = array();
		$shares_count = array();
		foreach($shares as $record){
			$total_area += $record['area'];
			$p_id = 'n'.date('Y-m-d', $record['rent_end_date']);
			$areas[$p_id] = isset($areas[$p_id]) ? $areas[$p_id]+$record['area'] : $record['area'];
			$shares_count[$p_id] = isset($shares_count[$p_id]) ? $shares_count[$p_id]+1 : 1;
		}
		
		for($i=count($dates)-1; $i>=0; $i--){
			if(!isset($dates[$i]['area'])){
				$dates[$i]['area'] = isset($areas[$dates[$i]['id']]) ? $areas[$dates[$i]['id']] : 0.00;
				$dates[$i]['shares_count'] = isset($shares_count[$dates[$i]['id']]) ? $shares_count[$dates[$i]['id']] : 0;
			}
			
			$dates[$i]['area_percent'] = $total_area>0 ? ($dates[$i]['area']/$total_area)*100 : 0;
			$dates[$i]['clear_title'] = $dates[$i]['title'];
			$dates[$i]['title'] .= '</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.$dates[$i]['area'].' га</div><div>';

			if($dates[$i]['parent']){
				$areas[$dates[$i]['parent']] = isset($areas[$dates[$i]['parent']]) ? $areas[$dates[$i]['parent']]+$dates[$i]['area'] : $dates[$i]['area'];
				$shares_count[$dates[$i]['parent']] = isset($shares_count[$dates[$i]['parent']]) ? $shares_count[$dates[$i]['parent']]+$dates[$i]['shares_count'] : $dates[$i]['shares_count'];
			}
		}
		return $dates;
	}
	
	
	
	public function get_field_list($license_id, $farm_id){
		$periods = Session::instance()->get('periods');
		if(!is_array($periods) || !count($periods)) $periods = array(-1);

        $fields = Jelly::select('field')->with('culture')->where('deleted', '=', false)->and_where('farm', '=', $farm_id)->and_where('period', 'IN', $periods)->and_where('license', '=', $license_id)->execute()->as_array();
		$data = array();
		
		foreach($fields as $field){
			$data[] = array(
				'id'	   => 'f'.$field['_id'],
				'title'    => $field['title'],
				'is_group' => false,
				'is_group_realy' => false,
				'level'	   => 0,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => '',
				'color'    => $field[':culture:_id'] ? $field[':culture:color'] : 'transparent',
				'parent_color' => $field[':culture:_id'] ? $field[':culture:color'] : 'transparent'
			);
		}

		return $data;
	}
	
	
	
	private function get_shares_years($shares){
		$years = array();
		foreach($shares as $share){
			$y = date('Y', $share['rent_end_date']);
			if(array_search($y, $years)===false)$years[] = $y;
		}
		return $years;
	}
	
	
	
	private function get_share_months($shares, $year){
		$months = array();
		foreach($shares as $share){
			$y = (int)date('Y', $share['rent_end_date']);
			if($y>$year)break;
			if($y<$year)continue;
			$m = date('m', $share['rent_end_date']);
			if(array_search($m, $months)===false)$months[] = $m;
		}
		return $months;
	}
	
	
	
	private function get_share_days($shares, $year, $month){
		$days= array();
		foreach($shares as $share){
			$y = (int)date('Y', $share['rent_end_date']);
			if($y>$year)break;
			if($y<$year)continue;
			$m = date('m', $share['rent_end_date']);
			if($m!=$month)continue;
			$d = date('d', $share['rent_end_date']);
			if(array_search($d, $days)===false)$days[] = $d;
		}
		return $days;
	}
	
	
	
	private function get_children_month_ids($months, $year){
		$res = array();
		for($i=0; $i<count($months); $i++)$res[] = 'g'.$year.'-'.$months[$i];
		return $res;
	}
	
	
	
	private function get_children_days_ids($days, $month, $year){
		$res = array();
		for($i=0; $i<count($days); $i++)$res[] = 'n'.$year.'-'.$month.'-'.$days[$i];
		return $res;
	}
	
	
	
	public function get_share_grid_data($license_id, $sort = 'shareholder'){
		$data = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$shares = Jelly::select('client_share')->with('status')->with('shareholder')->with('farm')->with('field')
											   ->where('license', '=', $license_id)
											   ->where('period', '=', $periods[0])
											   ->where('farm', 'IN', $farms)
											   ->order_by('share_number', 'desc')->execute()->as_array();

		$shareholder_groups = Jelly::factory('Client_ShareholderGroup')->get_shareholder_tree($license_id, false);
		
		foreach($shares as $share){
			
			$fi = (int)$share[':farm:_id'];
			$hi = (int)$share[':shareholder:parent'];
			$si = (int)$share['_id'];
			
			if(!isset($data[$fi])){
				$data[$fi] = array(
					'farm_id' => $fi,
					'farm_name' => $share[':farm:name'],
					'farm_color' => $share[':farm:color'],
					'children' => $this->get_farm_shareholder_groups($shareholder_groups, $fi)
				);
			}
			
			if(!$hi || $hi=='-2'){
				if(!isset($data[$fi]['children']['0_'.$fi])){
					$data[$fi]['children']['0_'.$fi] = array(
						'id'=>'0_'.$fi, 
						'title'    => 'Без группы',
						'level'=>1, 
						'color'=>'BBBBBB',
						'parent'   => '',
						'parent_color' => 'BBBBBB',
						'share_area' => 0,
						'land_price' => 0,
						'year_price' => 0,
						'rent_price' => 0,
						'land_tax' => 0,
						'income_tax' => 0,
						'payments' => array()
					);
				}
				$hi = '0_'.$fi;
			}
			
			if(array_key_exists($hi, $data[$fi]['children'])){
				$data[$fi]['children'][$hi]['children'][$share['_id']] = array(
					'share_id' => $share['_id'],
					'share_number' => $share['share_number'],
					'field_name' => $share[':field:title'],
					'color' => $share[':status:color'],
					'share_area' => $share['area'],
					'order_number' => $share['order_number'],
					'order_date' => date("d.m.Y", $share['order_date']),
					'shareholder_name' => trim($share[':shareholder:last_name'].' '.$share[':shareholder:first_name'].' '.$share[':shareholder:middle_name']),
					'shareholder_address' => $share[':shareholder:address'],
					'shareholder_passport' => $share[':shareholder:passport'],
					'shareholder_code' => $share[':shareholder:code'],
					'rent_start_date' => date("d.m.Y", $share['rent_start_date']),
					'rent_end_date' => date("d.m.Y", $share['rent_end_date']),
					'years' => (int)floor(((int)$share['rent_end_date'] - (int)$share['rent_start_date'])/(3600*24*365)),
					'land_price' => 0,
					'year_price' => 0,
					'rent_price' => 0,
					'land_tax' => 0,
					'income_tax' => 0,
					'payments' => array()
				);
				
				$data[$fi]['children'][$hi]['share_area'] += $share['area'];
				
				$payments = Jelly::select('client_sharepayment')->where('share', '=', $share['_id'])->execute()->as_array();
				
				foreach($payments as $payment){
					$data[$fi]['children'][$hi]['children'][$share['_id']]['payments'][] = array(
						'payment_id' => $payment['_id'],
						'payment_start_date' => date("d.m.Y", $payment['payment_start_date']),
						'payment_end_date' => date("d.m.Y", $payment['payment_end_date']),
						'years' => $payment['years'],
						'land_price' => $payment['land_price'],
						'percents' => $payment['percents'],
						'year_price' => $payment['year_price'],
						'rent_price' => $payment['rent_price'],
						'land_tax' => $payment['land_tax'],
						'income_tax' => $payment['income_tax']
					);
					
					if($data[$fi]['children'][$hi]['children'][$share['_id']]['land_price'] < $payment['land_price']) 
						$data[$fi]['children'][$hi]['children'][$share['_id']]['land_price'] = $payment['land_price'];
					
					$data[$fi]['children'][$hi]['children'][$share['_id']]['year_price'] += $payment['year_price'];
					$data[$fi]['children'][$hi]['year_price'] += $payment['year_price'];

					$data[$fi]['children'][$hi]['children'][$share['_id']]['rent_price'] += $payment['rent_price'];
					$data[$fi]['children'][$hi]['rent_price'] += $payment['rent_price'];
					
					$data[$fi]['children'][$hi]['children'][$share['_id']]['land_tax'] += $payment['land_tax'];
					$data[$fi]['children'][$hi]['land_tax'] += $payment['land_tax'];
					
					$data[$fi]['children'][$hi]['children'][$share['_id']]['income_tax'] += $payment['income_tax'];
					$data[$fi]['children'][$hi]['income_tax'] += $payment['income_tax'];
				}
			}
			
		}
		
		
		return $this->remove_empty_shareholdergroups($data);
	}
	
	
	
	private function remove_empty_shareholdergroups($data){
		foreach($data as &$farm){
			$farm['children'] = array_merge($farm['children'], array());
			$do_not_delete = array();
			for($i=count($farm['children'])-1; $i>=0; $i--){
				$key = $i;
				if(!isset($farm['children'][$key])) $key = '0_'.$farm['farm_id'];

				if(!count($farm['children'][$key]['children']) && array_search($farm['children'][$key]['id'], $do_not_delete)===false){
					array_splice($farm['children'], $i, 1);
				}else{
					if($farm['children'][$key]['parent']) $do_not_delete[] = $farm['children'][$key]['parent'];
				}
			}
		}
		return $data;
	}
	
	
	
	private function get_farm_shareholder_groups($shareholder_groups, $farm_id){
		$data = array();
		
		foreach($shareholder_groups as $shareholder){
			if($shareholder['farm']==$farm_id){
				$shareholder['level'] += 1;
				$shareholder['id'] = substr($shareholder['id'], 1);
				$shareholder['children'] = array();
				$shareholder['year_price'] = 0;
				$shareholder['rent_price'] = 0;
				$shareholder['land_tax'] = 0;
				$shareholder['income_tax'] = 0;
				$shareholder['share_area'] = 0;
				if($shareholder['parent']) $shareholder['parent'] = substr($shareholder['parent'], 1);
				$data[$shareholder['id']] = $shareholder;
			}
		}
		
		return $data;
	}
	
	
	
	static $year_colors = array('fffb00', 'fffee0', 'fffdbb', 'fcfc90', 'fff471', 'f1ef01', 'ff3ff5', 'ffd6fd', 'fda4f1', 'ff79eb', 'ed2ec3');
    static $month_colors = array('00fbff', 'd6ffff', 'b3fdff', '88f8ff', '46e9ff', '00daf9', '1c2eed', 'dee9ff', 'c5daff', '6792f1', '3944ff', '0227cd');
    static $day_colors = array('b45b00', 'efdfc2', 'e7b87e', 'cc7e10', 'a15000', '834000', 'ff2600', 'ffdbd5', 'ffa8a6', 'ff5b56', 'd92000', 'a91600', '00c700', 'd5fdd2', 'b9fca0',
                               '7bfa39', '2edf00', '00ae00', 'a62ecc', 'e7dafd', 'd8b9ff', 'c385f3', 'b639ff', '9626b0', '00b792', 'cefef0', 'a4fdea', '46fcd7', '00ddbd', '00977e', 'ff8200');
	private function get_color($mode, $id){
        switch($mode){
            case 'day':
                $color = Model_Client_Share::$day_colors[(int)$id-1];
                break;
            case 'month':
                $color = Model_Client_Share::$month_colors[(int)$id-1];
                break;
            case 'year':
                $color = Model_Client_Share::$year_colors[((int)$id) % 10];
                break;
            default:
                $color = 'BB2244';
                break;
        }

		return $color;
	}
	
	
	
	public function get_properties($model){
		$properties = Jelly::select('client_model_properties')->where('model', '=', $model)->execute();
		$t = array();
		foreach($properties as $property){
			$v = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
			if(($v instanceof Jelly_Model) and $v->loaded()) {
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}else{
				$t[$property->id()] = array('name' => $property->name, 'value' =>  $v->value, '_id' => $property->id());
			}
		}
		return $t;
	}


	
	public function set_property($model, $id, $property_name, $property_value = ''){
		$property = null;
        if($id){
            $property = Jelly::select('client_model_properties')->where('model', '=', $model)->where('_id', '=', (int)$id)->load();
            if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		}
		if(!$id){
			$property = Jelly::factory('client_model_properties');
			$property->model 	= $model;
//			$property->license 	= $this->license;
			$property->name 	= $property_name;
			$property->save();
		}else{
            $property->name 	= $property_name;
			$property->save();
        }

		$value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
		if(!($value instanceof Jelly_Model) or !$value->loaded()){
			$value = Jelly::factory('client_model_values');
			$value->property 	= $property;
			$value->item_id 	= $this->id();
		}

		$value->value	 	= $property_value;
		$value->save();
	}



	public function delete_property($model, $id){
		$property = Jelly::select('client_model_properties')->where('model', '=', $model)->where('_id', '=', (int)$id)->load();
		if(!($property instanceof Jelly_Model) or !$property->loaded()) return;
		Jelly::delete('client_model_values')->where('property', '=', $property->id())->execute();
		Jelly::delete('client_model_properties')->where('model', '=', $model)->where('_id', '=', (int)$id)->execute();
	}

	
	
	public function delete($key = NULL){
        //wtf? falling back to parent
        if (!is_null($key)) return parent::delete($key);
		
		$children_payments = Jelly::select('Client_SharePayment')->where('share', '=', $this->id())->execute();
		foreach($children_payments as $cp){
			$cp->delete();
		}
		
		Jelly::delete('Client_Share')->where('_id', '=', $this->id())->execute();
    }
	
	
	
	
	public function get_alert_shares($license_id){
		$result = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		$todays_midnight = (int)strtotime(date('d F Y'));
		
		$records = Jelly::select('client_share')->with('shareholder')->where('license', '=', $license_id)->where('period', '=', $periods[0])->where('farm', 'IN', $farms)->execute()->as_array();
		
		foreach($records as $record){
			if($record['status']==2){
				if(((int)$record['last_alert_show'])>$todays_midnight)continue;
				
				$result[] = array(
					'share_number' => $record['share_number'],
					'rent_end_date' => date("d.m.Y", $record['rent_end_date']),
					'shareholder_name' => Jelly::factory('Client_ShareholderGroup')->compose_full_name(array('last_name'=>$record[':shareholder:last_name'], 'first_name'=>$record[':shareholder:first_name'], 'middle_name'=>$record[':shareholder:middle_name']))
				);
				
				$item = Jelly::select('client_share', $record['_id']);
				$item->last_alert_show = time();
				$item->save();
			}
		}
		
		return $result;
	}
	
	
	
	
	public function get_subtitle($record){
		$msg = $record[':shareholder:last_name']." ".mb_substr($record[':shareholder:first_name'], 0, 1).".".mb_substr($record[':shareholder:middle_name'], 0, 1).". ".date("d.m.Y", $record['rent_end_date']);
		return UTF8::clean($msg);
	}
	
	
	
	public function get_shareholder_name($record){
		$msg = $record[':shareholder:last_name']." ".mb_substr($record[':shareholder:first_name'], 0, 1).".".mb_substr($record[':shareholder:middle_name'], 0, 1).".";
		return UTF8::clean($msg);
	}
	
	
	
	
	public function get_shares_for_field($field_id){
		$result = array();
		$records = Jelly::select('client_share')->with('shareholder')->where('field', '=', $field_id)->execute()->as_array();
		
		foreach($records as $record){
			$payments_data = Jelly::factory('Client_SharePayment')->get_share_payments_data($record['_id']);
			$result[] = array(
				'_id' => $record['_id'],
				'number' => $record['share_number'],
				'shareholder_name' => $this->get_shareholder_name($record),
				'gov_act_number' => $record['gov_act_number'],
				'square' => $record['area'],
				'order_date_start' => date('d.m.Y', $record['rent_start_date']),
				'order_date_end' => date('d.m.Y', $record['rent_end_date']),
				'land_price' => $payments_data['land_price'],
				'year_price' => $payments_data['year_price'],
				'rent_price' => $payments_data['rent_price'],
				'land_tax' => $payments_data['land_tax'],
				'income_tax' => $payments_data['income_tax']
			);
		}
		
		return $result;
	}
	
	
	
	private function get_rent_end_remains($timestamp){
		$remains = $timestamp - time();
		if($remains<=86400) return '0 дней';
		
		$years = $remains/31536000;
		if($years>=1) return round($years).' года';
		
		$months = $remains/2592000;
		if($months>=1) return round($months).' месяцев';
		
		$days = $remains/86400;
		if($days>=1) return round($days).' дней';
	}
	
}

