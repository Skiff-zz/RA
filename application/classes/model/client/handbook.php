<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Handbook extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('handbook')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),


				'model'			=> Jelly::field('String', array('label' => 'Модель',
					'rules' => array(
						'not_empty' => NULL
					))),
				'item'			=> Jelly::field('Integer', array('label' => 'ИД записи',
					'rules' => array(
						'not_empty' => NULL
					))),

				'license'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'license',
					'column'	=> 'license_id',
					'label'	=> 'Лицензия'
				)),

				'farm'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm_id',
					'label'	=> 'Хозяйство'
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


	public function get_excludes($model, $license_id){
		if(UTF8::strpos($model, 'contragent') !== false){
			$clear_model = UTF8::str_ireplace('_customer', '', UTF8::str_ireplace('_supplier', '', $model));
		}else{
			$clear_model = $model;
		}

		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$model_items = Jelly::select($clear_model)->where('deleted', '=', false)->execute()->as_array();
		$handbook_items = Jelly::select('client_handbook')->where('deleted', '=', false)->and_where('model', '=', $model)->and_where('license', '=', $license_id)->and_where('period', 'IN', $periods)->and_where('farm', 'IN', $farms)->execute()->as_array();

		$excludes = array();
		foreach($model_items as $model_item) {
			$handbook_include = false;
			foreach ($handbook_items as $handbook_item) {
				if($handbook_item['item']==$model_item['_id']) $handbook_include = true;
			}

			if(!$handbook_include) $excludes[] = $model_item['_id'];
		}

		return $excludes;
	}


	public function add_nomenclature($model, $model_ids, $license_id, $sel_farm = 0){
		if($sel_farm){
			$farms = array($sel_farm);
		}else{
			$farms = Jelly::factory('farm')->get_session_farms();
		}
		
		$periods = Session::instance()->get('periods');

		if(!count($farms) || !count($periods)) return;

		$this->clear_nomenclature($model, $model_ids, $license_id, $farms);

		if(UTF8::strpos($model, 'contragent') !== false){
			$clear_model = UTF8::str_ireplace('_customer', '', UTF8::str_ireplace('_supplier', '', $model));
			$postfix = UTF8::strpos($model, '_customer') !== false ? '_customer' : '_supplier';
		}else{
			$clear_model = $model;
		}

		foreach($model_ids as $id){
			$branch = $this->get_branch($clear_model, $id);

			foreach($branch as $item){
				if(UTF8::strpos($item['model'], 'contragent') !== false){
					$model_name = $item['model']=='client_contragent' ? 'client_contragent'.$postfix : 'client_contragent'.$postfix.'group';
				}else{
					$model_name = $item['model'];
				}

				foreach($farms as $farm){
					$record = Jelly::select('client_handbook')->where('model', '=', $model_name)->and_where('item', '=', $item['id'])->and_where('license', '=', $license_id)->and_where('farm', '=', $farm)->and_where('period', '=', $periods[0])->load();
					if(!($record instanceof Jelly_Model) or !$record->loaded()){
						$record = Jelly::factory('client_handbook');
					}
					$record->deleted = false;
					$record->update_date = time();
					$record->model = $model_name;
					$record->item = $item['id'];
					$record->license = $license_id;
					$record->farm = $farm;
					$record->period = $periods[0];
					$record->save();
				}
			}
		}
	}


	public function get_branch($model, $id){
		switch($model){
			case 'glossary_seed': $parent_model = 'glossary_culture'; break;
			case 'glossary_productionclass': $parent_model = 'glossary_production'; break;
			case 'glossary_production': $parent_model = 'glossary_production'; break;
			default: $parent_model = UTF8::strpos($model, 'group')===false ? $model.'group' : $model;
		}

		$record = Jelly::select($model)->where('deleted', '=', false)->load($id)->as_array();
		$parent = (UTF8::strpos($model, 'group') !== false || $model=='glossary_production') ? $record['parent'] : $record['group'];
		if(!($parent instanceof Jelly_Model) or !$parent->loaded()){
			return array(array('model' => $model, 'id' => $id));
		}
		
		return array_merge(array(array('model' => $model, 'id' => $id)), $this->get_branch($parent_model, $parent->id()));
	}


	public function clear_nomenclature($model, $model_ids, $license_id, $farms){
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$names_model = UTF8::strpos($model, 'production')!==false ? 'glossary_productionclass' : $model;
		$groups_model = UTF8::strpos($model, 'production')!==false ? 'glossary_production' : $model.'group';

		Jelly::delete('client_handbook')->where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->and_where_open()->and_where('model', '=', $names_model)->or_where('model', '=', $groups_model)->and_where_close()->execute();
	}


	public function set_checked_records($license_id, &$data, $model, $is_group, $both_trees){
		$handbook_records = $this->get_handbook_records($license_id, $model, $is_group, $both_trees);

		foreach($data as &$record){
			foreach($handbook_records as $handbook_record){
                if(UTF8::strpos($model, 'personal')===false){
                    if(substr($record['id'], 1)==$handbook_record['item'] && $record['is_group_realy']==UTF8::strpos($handbook_record['model'], 'group')  && !$record['is_group_realy']){
                        $record['checked'] = true;
                        $record['disabled'] = $handbook_record['in_use'];
                        if($record['disabled']) $record['disabled_txt'] = "Удалить данную культуру нельзя, так как она уже используется. Для удаления необходимо удалить все связанные с ней записи.";
                    }
                }else{
                    if((substr($record['id'], 1)==$handbook_record['id_in_glossary'] && $handbook_record['is_position']) && !$record['is_group_realy']){
                        $record['checked'] = true;
                        $record['disabled'] = $handbook_record['in_use'];
                        if($record['disabled']) $record['disabled_txt'] = "Удалить данную культуру нельзя, так как она уже используется. Для удаления необходимо удалить все связанные с ней записи.";
                    }
                }
			}
		}
	}


	public function get_handbook_records($license_id, $model, $is_group, $both_trees){
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
        
        if(UTF8::strpos($model, 'personal')!==false){
            $records = Jelly::select('client_handbook_personalgroup')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->execute()->as_array();
            foreach($records as &$record){
                $record['in_use'] = false;
            }
        }else{
            $records = Jelly::select('client_handbook')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->and_where('model', '=', $model)->execute()->as_array();
            if($is_group/* && $both_trees*/){
                $records_n = Jelly::select('client_handbook')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->and_where('model', '=', UTF8::str_ireplace('group', '', $model))->execute()->as_array();
                $records = array_merge($records, $records_n);
            }
            
            foreach($records as &$record){
                $record['in_use'] = $this->_is_record_in_use($record, $license_id, $farms);
            }
        }
        
		return $records;
	}


	private function _is_record_in_use($record, $license_id, $farms){
		$in_use = false; $m = $record['model'];
		if(!count($farms)) $farms = array(-1);
		
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		if($m=='glossary_culture'){
			$fields_count = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->and_where_open()->and_where('culture', '=', $record['item'])->or_where('culture_before', '=', $record['item'])->and_where_close()->execute()->count();
			if($fields_count>0) $in_use = true;
            
            $atk_count = Jelly::select('client_planning_atk')->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->and_where('culture', '=', $record['item'])->execute()->count();
            if($atk_count>0) $in_use = true;
        }

		if($m=='glossary_seed' || $m=='glossary_szr' || $m=='glossary_fertilizer' || $m=='glossary_gsm' || $m=='glossary_productionclass'){
			$clear_model = UTF8::str_ireplace('glossary_', '', $m);
			$transactio_nomenclature = Jelly::select('client_transactionnomenclature')->from('client_transaction')->where('deleted', '=', false)
																												->and_where('client_transaction.deleted', '=', false)
																												->and_where('client_transaction.license', '=', $license_id)
																												->and_where('client_transaction.period', 'IN', $periods)
																												->and_where('client_transaction.farm', 'IN', $farms)
																												->and_where('nomenclature_model', '=', $clear_model)
																												->and_where('nomenclature_id', '=', $record['item'])
																												->execute()->count();
			if($transactio_nomenclature>0) $in_use = true;
		}

		return $in_use;
	}
    
    
    
    
    
    
    
    
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////ДЛЯ НАВИГАЦИОННОЙ ПАНЕЛИ В СКЛАДСКОМ//////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    

    private $ids = array(
        'gculturegroup' => array(),
        'gszrgroup' => array(),
        'gfertilizergroup' => array(),
        'ggsmgroup' => array(),
        'gproduction' => array(),
        'gsuppliergroup' => array(),
        'gcustomergroup' => array()
    );
    
    
    public function get_nomenclature_tree($license_id, $farms){
        
        $seeds = $this->get_seeds($license_id, $farms);
        $szrs = $this->get_tree($license_id, 'glossary_szrgroup', 'glossary_szr', array('g'=>'gszrgroup', 'n'=>'nszr'), $farms);
        $fertilisers = $this->get_tree($license_id, 'glossary_fertilizergroup', 'glossary_fertilizer', array('g'=>'gfertilizergroup', 'n'=>'nfertilizer'), $farms);
        $gsms = $this->get_tree($license_id, 'glossary_gsmgroup', 'glossary_gsm', array('g'=>'ggsmgroup', 'n'=>'ngsm'), $farms);
        $productions = $this->get_tree($license_id, 'glossary_production', 'glossary_productionclass', array('g'=>'gproduction', 'n'=>'nproductionclass'), $farms);
        
        $data = $this->compose_all_nomenclature($seeds, $szrs, $fertilisers, $gsms, $productions);
        
        return $data;
    }
    
    private function get_seeds($license_id, $farms = array(-1)){  
        $exclude_subnames = Jelly::factory('client_handbook')->get_excludes('glossary_seed', $license_id);
        $seeds = Jelly::factory('glossary_seed')->get_tree($license_id, 'group', $exclude_subnames);
        
        $do_not_delete = array();
        $in_use_items = $this->get_in_use_nomenclature($license_id, $farms, 'glossary_seed');
        $children_to_remove = array();
        for($i=count($seeds)-1; $i>=0; $i--){
            if(array_search($seeds[$i]['id'], $in_use_items)===false){$children_to_remove[]=$seeds[$i]['id']; array_splice($seeds, $i, 1); }
            else $do_not_delete[] = $seeds[$i]['parent'];
        }
        

		$exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $license_id);
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $license_id);
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
		$cultures =	Jelly::factory('glossary_culturegroup')->get_tree($license_id, true, $exclude, 'items', true);


		for($i=count($cultures)-1; $i>=0; $i--){
			if(array_search($cultures[$i]['id'], $do_not_delete)===false){
				$children_to_remove[] = $cultures[$i]['id'];
				array_splice($cultures, $i, 1);
				continue;
			}else{
				for($j=count($cultures[$i]['children_g'])-1; $j>=0; $j--){
					if(array_search($cultures[$i]['children_g'][$j], $children_to_remove)!==false){
						array_splice($cultures[$i]['children_g'], $j, 1);
					}
				}
				if($cultures[$i]['parent'])$do_not_delete[] = $cultures[$i]['parent'];
			}

			$cultures[$i]['is_group'] = $cultures[$i]['is_group_realy'] = true;
			if(substr($cultures[$i]['id'], 0, 1)=='g') continue;
			if(!count($cultures[$i]['children_n'])) continue;
			$seeds_ids = array();

			for($j=count($seeds)-1; $j>=0; $j--){
				if($seeds[$j]['parent']==$cultures[$i]['id']){
					$seeds[$j]['is_group'] = true;
					$seeds[$j]['level'] = $cultures[$i]['level']+1;
					array_splice($cultures, $i+1, 0, array($seeds[$j]));
					$seeds_ids[] = $seeds[$j]['id'];
				}
			}

			$cultures[$i]['children_g'] = $cultures[$i]['children_n'] = $seeds_ids;
		}

		return $this->update_ids($cultures, array('g'=>'gculturegroup', 'n'=>'nculture', 's'=>'sseed'), 2, false);

    }
    
    private function get_tree($license_id, $groups_model, $names_model, $replacements, $farms = array(-1)){
        
		$exclude_groups = Jelly::factory('client_handbook')->get_excludes($groups_model, $license_id);
		$exclude_names = Jelly::factory('client_handbook')->get_excludes($names_model, $license_id);
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);

		$data =	Jelly::factory($groups_model)->get_tree($license_id, true, $exclude, 'items', true);
        

        $in_use_items = $this->get_in_use_nomenclature($license_id, $farms, $names_model);
        $children_to_remove = array();
        for($i=count($data)-1; $i>=0; $i--){
            if(array_search($data[$i]['id'], $in_use_items)===false){
                $children_to_remove[] = $data[$i]['id'];
                array_splice($data, $i, 1);
            }else{
                for($j=count($data[$i]['children_g'])-1; $j>=0; $j--){
                    if(array_search($data[$i]['children_g'][$j], $children_to_remove)!==false){
                        array_splice($data[$i]['children_g'], $j, 1);
                    }
                }
                if($data[$i]['parent'])$in_use_items[] = $data[$i]['parent'];
            }
        }
        
        
        return $this->update_ids($data, $replacements);
    }
    
    private function update_ids($data, $replacements, $level_incr = 2, $convert_to_group = true){
        foreach($data as &$record){
            if(isset($record['clear_title']))$record['title'] = $record['clear_title'];
            $record['level'] = $record['level']+$level_incr;
            $record['id'] = strtr($record['id'], $replacements);
            $record['parent'] = strtr($record['parent'], $replacements);
            if($convert_to_group){ $record['is_group'] = $record['is_group_realy'] = true; }
            
            for($i=0; $i<count($record['children_n']); $i++) $record['children_n'][$i] = strtr($record['children_n'][$i], $replacements);
            for($i=0; $i<count($record['children_g']); $i++) $record['children_g'][$i] = strtr($record['children_g'][$i], $replacements);
            
            if(!trim($record['parent'])){
                $record['parent'] = $replacements['g'].'00';
                $this->ids[$replacements['g']][] = $record['id'];
            }
        }
        return $data;
    }
    
    private function compose_all_nomenclature($seeds, $szrs, $fertilisers, $gsms, $productions){
        $data = array();
        $chilren = array();
        if(count($seeds)) $chilren[] = 'gculturegroup00';
        if(count($szrs)) $chilren[] = 'gszrgroup00';
        if(count($fertilisers)) $chilren[] = 'gfertilizergroup00';
        if(count($gsms)) $chilren[] = 'ggsmgroup00';
        //if(count($material_others)) $chilren[] = 'gmaterialsother';
        
        if(count($chilren)){
            $data[] = array('id' => 'gmaterialsroot', 'title' => 'Материалы', 'level' => 0, 'children_g' => $chilren, 'parent' => '', 'color' => '6d277f', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
        }
        
        if(count($seeds)){
            $data[] = array('id' => 'gculturegroup00', 'title' => 'Семена', 'level' => 1, 'children_g' => $this->ids['gculturegroup'], 'parent' => 'gmaterialsroot', 'color' => '973aae', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $seeds); 
        }
        
        if(count($szrs)){
            $data[] = array('id' => 'gszrgroup00', 'title' => 'СЗР', 'level' => 1, 'children_g' => $this->ids['gszrgroup'], 'parent' => 'gmaterialsroot', 'color' => 'b655fa', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $szrs);
        }
        
        if(count($fertilisers)){
            $data[] = array('id' => 'gfertilizergroup00', 'title' => 'Удобрения', 'level' => 1, 'children_g' => $this->ids['gfertilizergroup'], 'parent' => 'gmaterialsroot', 'color' => 'c38ef1', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $fertilisers);
        }
        
        if(count($gsms)){
            $data[] = array('id' => 'ggsmgroup00', 'title' => 'ГСМ', 'level' => 1, 'children_g' => $this->ids['ggsmgroup'], 'parent' => 'gmaterialsroot', 'color' => 'd8befc', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $gsms);
        }
        //$data[] = array('id' => 'gmaterialsother', 'title' => 'Прочее', 'level' => 1, 'children_g' => array(), 'parent' => 'gmaterialsroot', 'color' => 'e7dcfc', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
        
        
        $chilren = array();
        if(count($productions)) $chilren[] = 'gproduction00';
        //if(count($production_others)) $chilren[] = 'gproductionsother';
        
        if(count($chilren)){
            $data[] = array('id' => 'gproductionsroot', 'title' => 'Продукция', 'level' => 0, 'children_g' => $chilren, 'parent' => '', 'color' => '265bf9', 'parent_color' => '265bf9', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
        }
        
        if(count($productions)){
            $data[] = array('id' => 'gproduction00', 'title' => 'Продукция', 'level' => 1, 'children_g' => $this->ids['gproduction'], 'parent' => 'gproductionsroot', 'color' => '5f99ef', 'parent_color' => '265bf9', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $productions);
        }
        //$data[] = array('id' => 'gproductionsother', 'title' => 'Прочее', 'level' => 1, 'children_g' => array(), 'parent' => 'gproductionsroot', 'color' => 'c3dcfd', 'parent_color' => '265bf9', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
        
        //$data[] = array('id' => 'gservicesroot', 'title' => 'Услуги', 'level' => 0, 'children_g' => array(), 'parent' => '', 'color' => 'de2414', 'parent_color' => 'de2414', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
        
        return $data;
    }
    
    
    
    
    
    
    
    public function get_contragents_tree($license_id, $farms){
        
		$suppliers = $this->get_contragents($license_id, 'supplier', array('g'=>'gsuppliergroup', 'n'=>'nsupplier', 'cf' => 'nf', 'pf' => 'gf'), $farms);
		$customers = $this->get_contragents($license_id, 'customer', array('g'=>'gcustomergroup', 'n'=>'ncustomer', 'cf' => 'nf', 'pf' => 'gf'), $farms); 
        
		$data = $this->compose_all_contragents($suppliers, $customers);
		return $data;
    }
    
    private function get_contragents($license_id, $mode, $replacements, $farms = array(-1)){ 
        $in_use_farm = $this->get_in_use_contragents($license_id, $mode, 'farm', $farms);
        $in_use_cont = $this->get_in_use_contragents($license_id, $mode, 'cont', $farms);

		$farms = count($in_use_farm) ? Jelly::select('farm')->where('deleted', '=', false)->and_where('_id', 'IN', $in_use_farm)->order_by('name')->execute()->as_array() : array();
		$contragents = count($in_use_cont) ? Jelly::select('client_contragent')->where('deleted', '=', false)->and_where('_id', 'IN', $in_use_cont)->order_by('name')->execute()->as_array() : array();
		
        $frecords = array();
		foreach($farms as $farm){
			$frecords[] = array(
				'id'	   => 'g'.$mode.'f'.$farm['_id'],
				'title'    => $farm['name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => 1,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => 'g'.$mode.'group00',
				'color'    => $farm['color'],
				'parent_color' => $farm['color']
			);
            $this->ids['g'.$mode.'group'][] = 'g'.$mode.'f'.$farm['_id'];
		}
		
		$crecords = array();
		foreach($contragents as $contragent){
			$frecords[] = array(
				'id'	   => 'g'.$mode.'c'.$contragent['_id'],
				'title'    => $contragent['name'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => 1,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => 'g'.$mode.'group00',
				'color'    => $contragent['color'],
				'parent_color' => $contragent['color']
			);
			$this->ids['g'.$mode.'group'][] = 'g'.$mode.'c'.$contragent['_id'];
		}

        return array_merge($frecords, $crecords);
    }
    
	
    private function compose_all_contragents($suppliers, $customers){
        $data = array();
        if(count($suppliers)){
            $data[] = array('id' => 'gsuppliergroup00', 'title' => 'Поставщики', 'level' => 0, 'children_g' => $this->ids['gsuppliergroup'], 'parent' => '', 'color' => '6d277f', 'parent_color' => '6d277f', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $suppliers);
        }
        if(count($customers)){
            $data[] = array('id' => 'gcustomergroup00', 'title' => 'Покупатели', 'level' => 0, 'children_g' => $this->ids['gcustomergroup'], 'parent' => '', 'color' => '973aae', 'parent_color' => '973aae', 'is_group' => true, 'is_group_realy' => true, 'children_n' => array());
            $data = array_merge($data, $customers); 
        }

        return $data;
    }
    
    
    public function get_in_use_nomenclature($license_id, $farms,  $model){
        $result = array();
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
        
        $handbook_version = Jelly::select('client_handbookversion')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
                                                                   ->and_where('license', '=', $license_id)
                                                                   ->and_where('farm', 'IN', $farms)
                                                                   ->and_where('period', 'IN', $periods)
                                                                   ->and_where('nomenclature_model', '=', UTF8::str_ireplace('glossary_', '', $model))
                                                                   ->and_where('version_date', '=', 0)->execute()->as_array();
        
        foreach($handbook_version as $item){
            $result[] = ($model=='glossary_seed' ? 's' : 'n').$item['nomenclature_id'];
        }
        
        return $result;
    }
    
    
    public function get_in_use_contragents($license_id, $mode, $ctype, $farms){
        $result = array();

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
        
		if($ctype=='farm') $type = $mode=='supplier' ? 2 : 4;
		if($ctype=='cont') $type = $mode=='supplier' ? 1 : 3;
        
        $transactions = Jelly::select('client_transaction')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
                                                           ->and_where('license', '=', $license_id)
                                                           ->and_where('farm', 'IN', $farms)
                                                           ->and_where('period', 'IN', $periods)
                                                           ->and_where('type', '=', $type)->execute()->as_array();
		
        foreach($transactions as $item){
            $result[] = $item['contragent_id'];
        }
        
        return $result;
    }
	
    
}

