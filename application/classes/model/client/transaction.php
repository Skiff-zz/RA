<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Transaction extends Jelly_Model
{
	const RECEIPT	 = 1; //приём
	const POSTING	 = 2; //оприходование
	const SHIPPING	 = 3; //отгрузка
	const RETIREMENT = 4; //списание
	const REMAINS	 = 5; //остатки

	static $type_titles = array(
		Model_Client_Transaction::RECEIPT      => 'Приём',
		Model_Client_Transaction::POSTING      => 'Оприходование',
		Model_Client_Transaction::SHIPPING     => 'Отгрузка',
		Model_Client_Transaction::RETIREMENT   => 'Списание',
		Model_Client_Transaction::REMAINS      => 'Остатки',
	);

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('transactions')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

				'number'			=> Jelly::field('String', array('label' => 'Номер транзакции',
					'rules' => array(
						'not_empty' => NULL
					))),

				'transaction_date'	=>  Jelly::field('Integer', array('label' => 'Дата',
					'rules' => array(
						'not_empty' => NULL
					))),

				'type'			=>  Jelly::field('Integer', array('label' => 'Тип',
					'rules' => array(
						'not_empty' => NULL
					))),

				'contragent_id'       => Jelly::field('Integer',array('label'	=> 'Контрагент',
                    'rules' => array(
                        'not_empty' => NULL
                    ))),

				'contract_number' => Jelly::field('String', array('label' => 'Номер договора')),
				'contract_date'	=>  Jelly::field('Integer', array('label' => 'Дата договора')),

				'total_without_nds'	 => Jelly::field('String', array('label' => 'Всего без НДС')),
				'total_with_nds'	  => Jelly::field('String', array('label' => 'Всего с НДС')),
				'nds'					  => Jelly::field('String', array('label' => 'НДС')),

				'shipper'				 => Jelly::field('String', array('label' => 'Отгрузил(а)')),
				'recipient'				 => Jelly::field('String', array('label' => 'Получил(а)')),


				'nomenclature'         => Jelly::field('HasMany',array(
					'foreign'  => 'client_transactionnomenclature',
					'label'		=> 'Список номенклатуры',
				)),


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
				)),

                'create_date'	=>  Jelly::field('Integer', array('label' => 'Дата создания',
					'rules' => array(
						'not_empty' => NULL
					))
				)
		));
	}


	public function getFilteredData($license_id, $mode, $mode_id, $farm_id){

        if($farm_id){
            $farms = array($farm_id);
        }else{
           $farms = Jelly::factory('farm')->get_session_farms();
           if(!count($farms))$farms = array(-1);
        }

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);


		$WHERE = ' AND transactions.farm_id IN ('.implode(', ', $farms).') ';
		$WHERE .= ' AND transactions.period_id IN ('.implode(', ', $periods).') ';

        if($mode_id){
            switch($mode){
                case 'nomenclature':
                    $mode_id = explode('|', $mode_id);
                    $WHERE .= ' AND transaction_nomenclatures.nomenclature_model = \''.$mode_id[0].'\' ';
                    $WHERE .= ' AND transaction_nomenclatures.nomenclature_id = '.$mode_id[1].' ';
                    break;
                case 'contragent':
                    $mode_id = explode('|', $mode_id);
                    $WHERE .= ' AND transactions.type IN '.($mode_id[1]=='customer' ? '(3, 4)':'(1, 2, 5)').' ';
                    $WHERE .= ' AND transactions.contragent_id = '.$mode_id[0].' ';
                    break;
                case 'date':
                    $WHERE .= ' AND transactions.transaction_date = '.strtotime($mode_id).' ';
                    break;
                default: break;
            }
        }

		$query = "SELECT transactions._id,
                         transactions.number,
                         transactions.transaction_date,
                         transactions.create_date,
                         transactions.type,
                         transactions.contragent_id,
                         transactions.total_without_nds,
                         transactions.total_with_nds,
                         transactions.nds AS total_nds,
                         transactions.farm_id,
                         farms.name AS farm_name,
                         client_contragent.name AS contragent_name,
                         transaction_nomenclatures.nomenclature_id,
                         transaction_nomenclatures.nomenclature_model,
                         transaction_nomenclatures.amount,
                         transaction_nomenclatures.amount_units,
                         transaction_nomenclatures.price_without_nds,
                         transaction_nomenclatures.price_without_nds_units,
                         transaction_nomenclatures.sum_without_nds,
                         transaction_nomenclatures.sum_without_nds_units,
                         transaction_nomenclatures.sum_with_nds,
                         transaction_nomenclatures.sum_with_nds_units,
                         transaction_nomenclatures.nds,
                         transaction_nomenclatures.nds_units
					FROM transactions
               LEFT JOIN client_contragent ON client_contragent._id = transactions.contragent_id
		       LEFT JOIN transaction_nomenclatures ON transaction_nomenclatures.client_transaction_id = transactions._id
		      INNER JOIN farms ON farms._id = transactions.farm_id
				   WHERE transactions.deleted = 0
					 AND transactions.license_id = $license_id
				  $WHERE";

		$db = Database::instance();
		$result = $db->query(DATABASE::SELECT, $query, true);
		$data = array();
		foreach($result as $row){
		   $data[] = (array)$row;
		}


		for($i=0; $i<count($data); $i++){
			if($data[$i]['nomenclature_model'] && $data[$i]['nomenclature_id']){
				$nomenclature = Jelly::select('glossary_'.$data[$i]['nomenclature_model'])->load((int)$data[$i]['nomenclature_id']);
				$n_name = $nomenclature->name;
				if($data[$i]['nomenclature_model']=='productionclass'){
					$production = Jelly::select('glossary_production')->load((int)$nomenclature->group->id());
					$n_name = $production->name.' '.$n_name;
				}
				$data[$i]['nomenclature'] = array('_id'=>$nomenclature->id(), 'name'=>$n_name, 'color' => $nomenclature->color);
				$data[$i]['id'] = $data[$i]['_id'].'|'.$data[$i]['nomenclature_model'].'|'.$data[$i]['nomenclature_id'];
				$data[$i]['amount_units'] = Model_Client_TransactionNomenclature::$amount_units[$data[$i]['amount_units']];
				$data[$i]['price_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$data[$i]['price_without_nds_units']];
				$data[$i]['sum_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$data[$i]['sum_without_nds_units']];
				$data[$i]['sum_with_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$data[$i]['sum_with_nds_units']];
				$data[$i]['nds_units'] = Model_Client_TransactionNomenclature::$money_units[$data[$i]['nds_units']];
			}else{
				$data[$i]['nomenclature'] = array('_id'=>'', 'name'=>'', 'color'=>'FFFFFF');
				$data[$i]['id'] = $data[$i]['_id'].'|nomodel|noid';
				$data[$i]['amount_units'] = Model_Client_TransactionNomenclature::$amount_units[1];
				$data[$i]['price_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[1];
				$data[$i]['sum_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[1];
				$data[$i]['sum_with_nds_units'] = Model_Client_TransactionNomenclature::$money_units[1];
				$data[$i]['nds_units'] = Model_Client_TransactionNomenclature::$money_units[1];
			}

			if($data[$i]['type']==2 || $data[$i]['type']==4){
				$contragent_farm = Jelly::select('farm', (int)$data[$i]['contragent_id']);
				if(!$contragent_farm instanceof Jelly_Model || !$contragent_farm->loaded()) $data[$i]['contragent'] = array('_id'=>0, 'name'=>'', 'type'=>($data[$i]['type']==4 ? 'customer' : 'supplier'), 'is_farm' => true);
				else																		$data[$i]['contragent'] = array('_id'=>$contragent_farm->id(), 'name'=>$contragent_farm->name, 'type'=>($data[$i]['type']==4 ? 'customer' : 'supplier'), 'is_farm' => true);
			}else{
				$data[$i]['contragent'] = array('_id'=>$data[$i]['contragent_id'], 'name'=>$data[$i]['contragent_name'], 'type'=>($data[$i]['type']==3 ? 'customer' : 'supplier'), 'is_farm' => false);
			}
			$data[$i]['type'] = array('_id'=>$data[$i]['type'], 'name'=>Model_Client_Transaction::$type_titles[$data[$i]['type']]);
            $data[$i]['transaction_date_timestamp'] = $data[$i]['transaction_date'];
			$data[$i]['transaction_date'] = date("d.m.Y", $data[$i]['transaction_date']);
		}

		return $data;
	}





    public function group_data($license_id, $data, $mode){
        $blocks = array();
        $used_records = array();
		
		$farms = Jelly::factory('farm')->get_session_farms();
        if(!count($farms))$farms = array(-1);

        switch($mode){
           case 'nomenclature':
               $group_by_items = Jelly::factory('client_handbook')->get_nomenclature_tree($license_id, $farms);
               break;
           case 'contragent':
               $group_by_items = Jelly::factory('client_handbook')->get_contragents_tree($license_id, $farms);
               break;
           case 'date':
               $group_by_items = $this->get_dates_tree($license_id, $farms);
               $group_by_items = $this->transactions_dates_tree($group_by_items);
               break;
           default:return $data;
        }


        foreach($data as $item){

            $fi = $item['farm_id'];

			//хозяйства
            if(!isset($blocks[$fi])){
                $farm = Jelly::select('farm', $fi);
                if($farm instanceof Jelly_Model && $farm->loaded()) $farm = $farm->as_array();
                $blocks[$fi] = array(
                    'farm_id' => $item['farm_id'],
                    'farm_name' => $item['farm_name'],
                    'farm_color' => arr::get($farm, 'color', 'FFFFFF'),
                    'amount' => array(),
                    'sum_without_nds' => array(),
                    'nds' => array(),
                    'sum_with_nds' => array(),
                    'items' => $group_by_items
                );
            }

            if(!isset($used_records[$fi]))$used_records[$fi] = array();

            //проходим по всем записям и ищем куда вставить транзакцию
            foreach($blocks[$fi]['items'] as &$group_by_item){

                //проверяем, сюда вставлять или нет
                $is_parent = false;
                if($mode=='nomenclature' && (int)str_replace($item['nomenclature_model'], '', substr($group_by_item['id'], 1))==(int)$item['nomenclature_id'])
                    if($item['nomenclature_model'] && (int)$item['nomenclature_id'])$is_parent = true;
                if($mode=='contragent'){
                    if((int)$item['contragent_id']){
                        $key = ($item['type']['_id']==3 || $item['type']['_id']==4) ? 'customer' : 'supplier';
						$key2 = ($item['type']['_id']==2 || $item['type']['_id']==4) ? 'f' : 'c';
						$key = $key.$key2;
                        if((int)str_replace($key, '', substr($group_by_item['id'], 1))==(int)$item['contragent_id']) $is_parent = true;
                    }
                }
                if($mode=='date' && $group_by_item['filter']==$item['transaction_date']) $is_parent = true;


                //если вставлять нужно сюда
                if($is_parent){
                    //если у данной записи ещё нет транзакций, создаём массив для них
                    if(!isset($group_by_item['transactions'])) $group_by_item['transactions'] = array();
					//если у данной записи ещё нет транзакции с таким ид, создаём её
                    if(!isset($group_by_item['transactions'][$item['_id']])) $group_by_item['transactions'][$item['_id']] = array(
                        'id' => $item['_id'].($mode=='nomenclature' ? '|'.$item['nomenclature_model'].'|'.$item['nomenclature_id']:''),
                        'number' => $item['number'],
                        'transaction_date' => $item['transaction_date'],
                        'type' => $item['type'],
                        'contragent' => $item['contragent'],
                        'farm' => array('_id' => $item['farm_id'], 'name' => $item['farm_name']),
                        'nomenclature' => array()
                    );

                    //добавляем данной транзакции номенклатуру из $item
                    $nomenclature_status = Jelly::factory('client_handbookversion')->get_nomenclature_status($license_id, $item);
                    $group_by_item['transactions'][$item['_id']]['nomenclature'][]=array(
                        'nomenclature_id' => $item['nomenclature']['_id'],
                        'nomenclature_name' => $item['nomenclature']['name'],
                        'nomenclature_color' => $item['nomenclature']['color'],
                        'nomenclature_model' => $item['nomenclature_model'],
                        'nomenclature_status' => $nomenclature_status,
                        'amount' => $item['nomenclature_id'] ? array('value' => $item['amount'], 'units_name' => $item['amount_units']['name']) : array('value'=>0, 'units_name'=>'т'),
                        'price_without_nds' => $item['nomenclature_id'] ? array('value'=>$item['price_without_nds'], 'units_name' => $item['price_without_nds_units']['name'].'/'.$item['amount_units']['name']) : array('value'=>0, 'units_name'=>'грн'),
                        'sum_without_nds' => $item['nomenclature_id'] ? array('value'=>$item['sum_without_nds'], 'units_name' => $item['sum_without_nds_units']['name']) : array('value'=>0, 'units_name'=>'грн'),
                        'nds' => $item['nomenclature_id'] ? array('value'=>$item['nds'], 'units_name' => $item['nds_units']['name']) : array('value'=>0, 'units_name'=>'грн'),
                        'sum_with_nds' => $item['nomenclature_id'] ? array('value'=>$item['sum_with_nds'], 'units_name' => $item['sum_with_nds_units']['name']) : array('value'=>0, 'units_name'=>'грн')
                    );


                    $au_converted = ($item['amount_units']['_id']==2 || $item['amount_units']['_id']==3 || $item['amount_units']['_id']==4) ? 1 : $item['amount_units']['_id']; //всё что не литры будем переводить в тонны

                    //список ид записей, у которых есть хотябы 1 транзакция
                    if(!isset($used_records[$fi][$group_by_item['id']]))$used_records[$fi][$group_by_item['id']] = array(
                        'amount' => array(),
                        'sum_without_nds' => array(),
                        'nds' => array(),
                        'sum_with_nds' => array()
                    );

                    if($nomenclature_status!='not_relevant'){
                        if(!isset($used_records[$fi][$group_by_item['id']]['amount'][$au_converted])) $used_records[$fi][$group_by_item['id']]['amount'][$au_converted] = array('value'=>0, 'units_id'=> $au_converted, 'units_name'=>Model_Client_TransactionNomenclature::$amount_units[$au_converted]['name']);
                        if(!isset($used_records[$fi][$group_by_item['id']]['sum_without_nds'][$item['sum_without_nds_units']['_id']])) $used_records[$fi][$group_by_item['id']]['sum_without_nds'][$item['sum_without_nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['sum_without_nds_units']['_id'], 'units_name'=>$item['sum_without_nds_units']['name']);
                        if(!isset($used_records[$fi][$group_by_item['id']]['nds'][$item['nds_units']['_id']])) $used_records[$fi][$group_by_item['id']]['nds'][$item['nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['nds_units']['_id'], 'units_name'=>$item['nds_units']['name']);
                        if(!isset($used_records[$fi][$group_by_item['id']]['sum_with_nds'][$item['sum_with_nds_units']['_id']])) $used_records[$fi][$group_by_item['id']]['sum_with_nds'][$item['sum_with_nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['sum_with_nds_units']['_id'], 'units_name'=>$item['sum_with_nds_units']['name']);
						
						if($mode=='nomenclature' && $item['type']['_id']!=3 && $item['type']['_id']!=4){
							$price = $this->getNomenclatureHandbookData($license_id, $fi, $item['nomenclature_model'], $item['nomenclature_id'], $item['amount_units']['_id'], $item['price_without_nds_units']['_id']);
							if($price){
								if(!isset($group_by_item['price'])) $group_by_item['price'] = array();
								if(!isset($group_by_item['price'][$price['amount_units']['_id']])) $group_by_item['price'][$price['amount_units']['_id']] = $price;
							}
						}
						

                        $converted = $this->convert_to_tons($item['amount'], $item['amount_units']['_id']);
                        $koef = $nomenclature_status=='minus' ? -1:1;
                        $used_records[$fi][$group_by_item['id']]['amount'][$au_converted]['value'] += (float)$converted*$koef;
                        $used_records[$fi][$group_by_item['id']]['sum_without_nds'][$item['sum_without_nds_units']['_id']]['value'] += round($item['sum_without_nds']*$koef, 2);
                        $used_records[$fi][$group_by_item['id']]['nds'][$item['nds_units']['_id']]['value'] += round($item['nds']*$koef, 2);
                        $used_records[$fi][$group_by_item['id']]['sum_with_nds'][$item['sum_with_nds_units']['_id']]['value'] += round($item['sum_with_nds']*$koef, 2);


                        if(!isset($blocks[$fi]['amount'][$au_converted])) $blocks[$fi]['amount'][$au_converted] = array('value'=>0, 'units_id'=> $au_converted, 'units_name'=>Model_Client_TransactionNomenclature::$amount_units[$au_converted]['name']);
                        if(!isset($blocks[$fi]['sum_without_nds'][$item['sum_without_nds_units']['_id']])) $blocks[$fi]['sum_without_nds'][$item['sum_without_nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['sum_without_nds_units']['_id'], 'units_name'=>$item['sum_without_nds_units']['name']);
                        if(!isset($blocks[$fi]['nds'][$item['nds_units']['_id']])) $blocks[$fi]['nds'][$item['nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['nds_units']['_id'], 'units_name'=>$item['nds_units']['name']);
                        if(!isset($blocks[$fi]['sum_with_nds'][$item['sum_with_nds_units']['_id']])) $blocks[$fi]['sum_with_nds'][$item['sum_with_nds_units']['_id']] = array('value'=>0, 'units_id'=> $item['sum_with_nds_units']['_id'], 'units_name'=>$item['sum_with_nds_units']['name']);


                        $blocks[$fi]['amount'][$au_converted]['value'] += (float)$converted*$koef;
                        $blocks[$fi]['sum_without_nds'][$item['sum_without_nds_units']['_id']]['value'] += round($item['sum_without_nds']*$koef, 2);
                        $blocks[$fi]['nds'][$item['nds_units']['_id']]['value'] += round($item['nds']*$koef, 2);
                        $blocks[$fi]['sum_with_nds'][$item['sum_with_nds_units']['_id']]['value'] += round($item['sum_with_nds']*$koef, 2);
                    }

                }

            }

        }

        //чистим дерево
        foreach($blocks as $farm_id => &$block){
            $this->clean_tree($block['items'], $used_records[$farm_id]);
        }

        //print_r($blocks); exit;

        return $blocks;
    }



    private function clean_tree(&$records, $used_records){
        //print_r($used_records); exit;
        for($i=count($records)-1; $i>=0; $i--){
            if(array_key_exists($records[$i]['id'], $used_records)){
                $current = $used_records[$records[$i]['id']];

                $records[$i]['amount'] = $current['amount'];
                $records[$i]['sum_without_nds'] = $current['sum_without_nds'];
                $records[$i]['nds'] = $current['nds'];
                $records[$i]['sum_with_nds'] = $current['sum_with_nds'];


                if(trim($records[$i]['parent'])){
                    $rp = $records[$i]['parent'];

                    if(!isset($used_records[$rp])) $used_records[$rp] = array(
                        'amount' => array(),
                        'sum_without_nds' => array(),
                        'nds' => array(),
                        'sum_with_nds' => array()
                    );

                    foreach($current['amount'] as $amount) if(!isset($used_records[$rp]['amount'][(int)$amount['units_id']])) $used_records[$rp]['amount'][(int)$amount['units_id']] = array('value'=>0, 'units_id'=> $amount['units_id'], 'units_name'=> $amount['units_name']);
                    foreach($current['sum_without_nds'] as $sum_without_nds) if(!isset($used_records[$rp]['sum_without_nds'][(int)$sum_without_nds['units_id']])) $used_records[$rp]['sum_without_nds'][(int)$sum_without_nds['units_id']] = array('value'=>0, 'units_id'=> $sum_without_nds['units_id'], 'units_name'=> $sum_without_nds['units_name']);
                    foreach($current['nds'] as $nds) if(!isset($used_records[$rp]['nds'][(int)$nds['units_id']])) $used_records[$rp]['nds'][(int)$nds['units_id']] = array('value'=>0, 'units_id'=> $nds['units_id'], 'units_name'=> $nds['units_name']);
                    foreach($current['sum_with_nds'] as $sum_with_nds) if(!isset($used_records[$rp]['sum_with_nds'][(int)$sum_with_nds['units_id']])) $used_records[$rp]['sum_with_nds'][(int)$sum_with_nds['units_id']] = array('value'=>0, 'units_id'=> $sum_with_nds['units_id'], 'units_name'=> $sum_with_nds['units_name']);

                    foreach($current['amount'] as $amount) $used_records[$rp]['amount'][(int)$amount['units_id']]['value'] += round($amount['value'], 2);
                    foreach($current['sum_without_nds'] as $sum_without_nds) $used_records[$rp]['sum_without_nds'][(int)$sum_without_nds['units_id']]['value'] += round($sum_without_nds['value'], 2);
                    foreach($current['nds'] as $nds) $used_records[$rp]['nds'][(int)$nds['units_id']]['value'] += round($nds['value'], 2);
                    foreach($current['sum_with_nds'] as $sum_with_nds) $used_records[$rp]['sum_with_nds'][(int)$sum_with_nds['units_id']]['value'] += round($sum_with_nds['value'], 2);

                    ksort($used_records[$rp]['amount']);
                }
            }else{
                array_splice($records, $i, 1);
            }
        }
    }
	
	
	
	
	
	public function getNomenclatureHandbookData($license, $farm, $nomenclature_model, $nomenclature_id, $amount_units, $price_units){
		
		$amount_units = ($amount_units==2 || $amount_units==3 || $amount_units==4) ? 1 : $amount_units;
		
		$period = Session::instance()->get('periods');
		if(!count($period)) $period = -1;
		else $period = $period[0];
		
		$handbook_version = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                                   ->and_where('license', '=', $license)
                                                                   ->and_where('farm', '=', $farm)
                                                                   ->and_where('period', '=', $period)
                                                                   ->and_where('nomenclature_model', '=', $nomenclature_model)
                                                                   ->and_where('nomenclature_id', '=', $nomenclature_id)
                                                                   ->and_where('amount_units', '=', $amount_units)
                                                                   ->and_where('version_date', '=', 0)
                                                                   ->and_where('discount_price_units', '=', $price_units)->limit(1)->execute();
		
		if(!$handbook_version instanceof Jelly_Model || !$handbook_version->loaded()) return false;
		
		return array('value' => $handbook_version->discount_price, 'price_units' => Model_Client_TransactionNomenclature::$money_units[$handbook_version->discount_price_units], 'amount_units' => Model_Client_TransactionNomenclature::$amount_units[$handbook_version->amount_units]);
	}





	public function get_dates_tree($license_id, $farms = array(-1), $date = false){
		if(!count($farms)) $farms = array(-1);

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$transactions = Jelly::select('client_transaction')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->and_where('license', '=', $license_id)->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods);
        if($date) $transactions = $transactions->and_where ('transaction_date', '=', strtotime($date));
        $transactions = $transactions->order_by('transaction_date')->execute()->as_array();

		$result = array();

		$years = $this->get_transaction_years($transactions);
		foreach($years as $year) {
				$months = $this->get_transaction_months($transactions, $year);
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
					'parent_color' => 'FFFFFF',
					'filter' => 'y'.$year
				);
				foreach($months as $month) {
					$days = $this->get_transaction_days($transactions, $year, $month);
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
							'id'	   => 'g'.$year.'-'.$month.'-'.$day,
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
//print_r($result); exit;
		return $result;
	}


	private function get_transaction_years($transactions){
		$years = array();
		foreach($transactions as $transaction){
			$y = date('Y', $transaction['transaction_date']);
			if(array_search($y, $years)===false)$years[] = $y;
		}
		return $years;
	}


	private function get_transaction_months($transactions, $year){
		$months = array();
		foreach($transactions as $transaction){
			$y = (int)date('Y', $transaction['transaction_date']);
			if($y>$year)break;
			if($y<$year)continue;
			$m = date('m', $transaction['transaction_date']);
			if(array_search($m, $months)===false)$months[] = $m;
		}
		return $months;
	}


	private function get_transaction_days($transactions, $year, $month){
		$days= array();
		foreach($transactions as $transaction){
			$y = (int)date('Y', $transaction['transaction_date']);
			if($y>$year)break;
			if($y<$year)continue;
			$m = date('m', $transaction['transaction_date']);
			if($m!=$month)continue;
			$d = date('d', $transaction['transaction_date']);
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
		for($i=0; $i<count($days); $i++)$res[] = 'g'.$year.'-'.$month.'-'.$days[$i];
		return $res;
	}

    static $year_colors = array('fffb00', 'fffee0', 'fffdbb', 'fcfc90', 'fff471', 'f1ef01', 'ff3ff5', 'ffd6fd', 'fda4f1', 'ff79eb', 'ed2ec3');
    static $month_colors = array('00fbff', 'd6ffff', 'b3fdff', '88f8ff', '46e9ff', '00daf9', '1c2eed', 'dee9ff', 'c5daff', '6792f1', '3944ff', '0227cd');
    static $day_colors = array('b45b00', 'efdfc2', 'e7b87e', 'cc7e10', 'a15000', '834000', 'ff2600', 'ffdbd5', 'ffa8a6', 'ff5b56', 'd92000', 'a91600', '00c700', 'd5fdd2', 'b9fca0',
                               '7bfa39', '2edf00', '00ae00', 'a62ecc', 'e7dafd', 'd8b9ff', 'c385f3', 'b639ff', '9626b0', '00b792', 'cefef0', 'a4fdea', '46fcd7', '00ddbd', '00977e', 'ff8200');
	private function get_color($mode, $id){
        switch($mode){
            case 'day':
                $color = Model_Client_Transaction::$day_colors[(int)$id-1];
                break;
            case 'month':
                $color = Model_Client_Transaction::$month_colors[(int)$id-1];
                break;
            case 'year':
                $color = Model_Client_Transaction::$year_colors[((int)$id) % 10];
                break;
            default:
                $color = 'BB2244';
                break;
        }

		return $color;
	}


    private function transactions_dates_tree($dates_tree){
        $result = array();
        foreach($dates_tree as &$item){
            $is_day = count(explode('-', $item['id']))==3;
            if($is_day){
                $item['level'] = 0;
                $item['parent'] = '';
                $item['title'] = $item['filter'];
                $result[] = $item;
            }
        }
        return $result;
    }









    private $nomenclature_titles = array('seed'=>'Семена', 'szr'=>'СЗР', 'fertilizer'=>'Удобрения', 'gsm'=>'ГСМ', 'productionclass'=>'Продукция');
	private $nomenclature_colors = array('seed'=>'973aae', 'szr'=>'b655fa', 'fertilizer'=>'c38ef1', 'gsm'=>'d8befc', 'productionclass'=>'5f99ef');
    public function get_summary_data($license_id){
        $farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms))$farms = array(-1);

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$summary = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                          ->and_where('license', '=', $license_id)
                                                          ->and_where('farm', 'IN', $farms)
                                                          ->and_where('period', 'IN', $periods)
                                                          //->and_where('amount', '>', 0)
                                                          ->and_where('version_date', '=', 0)->execute()->as_array();

		$blocks = array();
		$farms_data = array();

		foreach($summary as $item){

			$nm = $item['nomenclature_model'];
            $ni = $item['nomenclature_id'];
            //print_r($item); exit;
            $au = $item['amount_units'];
            $au_converted = ($item['amount_units']==2 || $item['amount_units']==3 || $item['amount_units']==4) ? 1 : $item['amount_units']; //всё что не литры будем переводить в тонны
			$pu = $item['discount_price_units'];

			//виды номенклатуры
            if(!isset($blocks[$nm]))$blocks[$nm] = array(
                'nomenclature_model' => $item['nomenclature_model'],
                'nomenclature_title' => $this->nomenclature_titles[$item['nomenclature_model']],
				'nomenclature_color' => $this->nomenclature_colors[$item['nomenclature_model']],
                'amount' => array(),
                'price' => array(),
                'items' => array()
            );

			if(!isset($blocks[$nm]['amount'][$au_converted]))$blocks[$nm]['amount'][$au_converted] = array('value' => 0, 'units'=>$au_converted, 'units_title' => Model_Client_TransactionNomenclature::$amount_units[$au_converted]['name']);
			if(!isset($blocks[$nm]['price'][$pu]))$blocks[$nm]['price'][$pu] = array('value' => 0, 'units'=>$pu, 'units_title' => Model_Client_TransactionNomenclature::$money_units[$pu]['name']);

			//конкретные номенклатуры
            if(!isset($blocks[$nm]['items'][$ni.'|'.$au.'|'.$pu.'|'.$nm])){
				$nomenclature = $this->get_nomenclature($item['nomenclature_model'], $item['nomenclature_id']);
				$blocks[$nm]['items'][$ni.'|'.$au.'|'.$pu.'|'.$nm] = array(
					'id' => $ni.'|'.$au.'|'.$pu.'|'.$nm,
					'nomenclature_id' => $item['nomenclature_id'],
					'nomenclature_title' => $nomenclature['name'],
					'nomenclature_color' => $nomenclature['color'],
					'price' => 0,
					'price_units' => $item['discount_price_units'],
					'price_units_title' => Model_Client_TransactionNomenclature::$money_units[$pu]['name'],
					'amount' => 0,
					'amount_units' => $item['amount_units'],
					'amount_units_title' => Model_Client_TransactionNomenclature::$amount_units[$au]['name']
				);
			}

            $converted = $this->convert_to_tons($item['amount'], $item['amount_units']);
			$blocks[$nm]['amount'][$au_converted]['value'] += (float)$converted;
			$blocks[$nm]['price'][$pu]['value'] += round($item['amount']*$item['discount_price'], 2);

			$blocks[$nm]['items'][$ni.'|'.$au.'|'.$pu.'|'.$nm]['amount'] += round($item['amount'], 2);
			$blocks[$nm]['items'][$ni.'|'.$au.'|'.$pu.'|'.$nm]['price'] += round($item['amount']*$item['discount_price'], 2);
		}

		//print_r($blocks);exit;

		foreach($farms as $farm){
			$frm = Jelly::select('farm', (int)$farm);
			if(!$frm instanceof Jelly_Model || !$frm->loaded()) continue;

			//столбец хозяйства
			$farms_data[$farm] = array(
				'farm_id' => $farm,
				'farm_name' => $frm->name,
				'nomenclature' => array()
			);

			foreach($blocks as $nomenclature_type){
				$nm = $nomenclature_type['nomenclature_model']; //nomenclature model

				//блок типа номенклатуры в столбце хозяйства
				$farms_data[$farm]['nomenclature'][$nm]=array(
					'amount' => array(),
					'price' => array(),
					'items' => array()
				);

				foreach($nomenclature_type['items'] as $nomenclature){

					//конкретная строка номенклатуры в блоке
					$farms_data[$farm]['nomenclature'][$nm]['items'][$nomenclature['id']] = array(
						'amount' => 'none',
						'price'  => 'none',
						'sum'    => 'none'
					);

					foreach($summary as $item){
						if($item['farm']==$farm
						   && $item['nomenclature_model']==$nm
						   && $item['nomenclature_id']==$nomenclature['nomenclature_id']
						   && $item['amount_units']==$nomenclature['amount_units']
						   && $item['discount_price_units']==$nomenclature['price_units']){
							$farms_data[$farm]['nomenclature'][$nm]['items'][$nomenclature['id']]['amount'] = number_format($item['amount'], 2, '.', '').' '.Model_Client_TransactionNomenclature::$amount_units[$item['amount_units']]['name'];
							$farms_data[$farm]['nomenclature'][$nm]['items'][$nomenclature['id']]['price'] = number_format($item['discount_price'], 2, '.', '').' '.Model_Client_TransactionNomenclature::$money_units[$item['discount_price_units']]['name'].'/'.Model_Client_TransactionNomenclature::$amount_units[$item['amount_units']]['name'];
							$farms_data[$farm]['nomenclature'][$nm]['items'][$nomenclature['id']]['sum'] = number_format($item['amount']*$item['discount_price'], 2, '.', '').' '.Model_Client_TransactionNomenclature::$money_units[$item['discount_price_units']]['name'];

                            $au_converted = ($item['amount_units']==2 || $item['amount_units']==3 || $item['amount_units']==4) ? 1 : $item['amount_units']; //всё что не литры будем переводить в тонны

							if(!isset($farms_data[$farm]['nomenclature'][$nm]['amount'][$au_converted])) $farms_data[$farm]['nomenclature'][$nm]['amount'][$au_converted] = array('value' => 0, 'units'=>$au_converted, 'units_title' => Model_Client_TransactionNomenclature::$amount_units[$au_converted]['name']);
							if(!isset($farms_data[$farm]['nomenclature'][$nm]['price'][$item['discount_price_units']])) $farms_data[$farm]['nomenclature'][$nm]['price'][$item['discount_price_units']] = array('value' => 0, 'units'=>$item['discount_price_units'], 'units_title' => Model_Client_TransactionNomenclature::$money_units[$item['discount_price_units']]['name']);

                            $converted = $this->convert_to_tons($item['amount'], $item['amount_units']);
							$farms_data[$farm]['nomenclature'][$nm]['amount'][$au_converted]['value'] += (float)$converted;
							$farms_data[$farm]['nomenclature'][$nm]['price'][$item['discount_price_units']]['value'] += round($item['amount']*$item['discount_price'], 2);
						}
					}
				}
			}
		}
		//print_r($farms_data); exit;
		$result = array('blocks'=>$blocks, 'farms' => $farms_data);
		return $result;
    }


	public function get_nomenclature($model, $id){
		$model = 'glossary_'.$model.($model=='production' ? 'class' : '');
		$item = Jelly::select($model, (int)$id);
		if($item instanceof Jelly_Model && $item->loaded()){
			$item = $item->as_array();
			if($model=='glossary_productionclass'){
				$production = jelly::select('glossary_production', (int)$item['group']->id());
				if($production instanceof Jelly_Model && $production->loaded()){
					$item['name'] = $production->name." ".$item['name'];
				}
			}
			return $item;
		}
		return array('name'=>'', 'color'=>'FFFFFF');
	}


    public function convert_to_tons($amount, $units){
        switch((int)$units){
            case 2: $koef = 0.1;break;
            case 3: $koef = 0.001;break;
            case 4: $koef = 0.000001;break;
            default: $koef = 1;break;
        }

        return $amount*$koef;
    }


    public function get_units($model, $id){

        $units = Model_Client_TransactionNomenclature::$amount_units;
        $result = $units[1];

        if(!$model || !$id) return $result;


        $record = Jelly::select($model, (int)$id);
        if(!$record instanceof Jelly_Model || !$record->loaded()) return $result;

        switch($model){
            case 'glossary_seed': break;
            case 'glossary_productionclass':
                foreach($units as $unit) if($unit['name']==$record->group->units->name) $result = $unit;
                break;
            default:
                foreach($units as $unit) if($unit['name']==$record->units->name) $result = $unit;
                break;
        }

        return $result;
    }
	
	
	public function get_amount($handbook_versions, $n_model, $n_id){
		$result = array();
		foreach($handbook_versions as $version){
			if($version['nomenclature_model']!=$n_model || $version['nomenclature_id']!=$n_id) continue;
			$result[$version['amount_units']] = array('value'=>$version['amount'], 'units'=>Model_Client_TransactionNomenclature::$amount_units[$version['amount_units']]);
		}
		return $result;
	}
	
	
	public function get_handbook_price($handbook_versions, $n_model, $n_id, $amount_units){
		$result = 0;
		$koef = $amount_units==2 ? 0.1 :($amount_units==3 ? 0.001 : ($amount_units==4 ? 0.000001 : 1));
		$amount_units = ($amount_units==2 || $amount_units==3 || $amount_units==4) ? 1 : $amount_units;
		
		foreach($handbook_versions as $version){
			if($version['nomenclature_model']!=$n_model || $version['nomenclature_id']!=$n_id || $version['amount_units']!=$amount_units) continue;
			$result = $version['discount_price']*$koef;
		}
		return $result;
	}
	
	
	public function get_nomenclature_tooltip($n_model, $n_id){
		$path = Jelly::factory('client_transaction')->get_nomenclature_path($n_model, $n_id);
		$path = array_reverse($path);
		switch($n_model){
			case 'seed':			$firstInPath = 'Семена'; break;
			case 'szr':				$firstInPath = 'СЗР'; break;
			case 'fertilizer':		$firstInPath = 'Удобрения'; break;
			case 'gsm':				$firstInPath = 'ГСМ'; break;
			case 'productionclass': $firstInPath = 'Продукция'; break;
		}
		$str = implode(' -> ', $path);
		$str = $firstInPath.' -> '.$str;
		
		return $str;
	}
	
	
	public function get_nomenclature_path($n_model, $n_id){
		$result = array();
		$model = 'glossary_'.$n_model;
		
		$item = Jelly::select($model, (int)$n_id);
		if($item instanceof Jelly_Model && $item->loaded()){
			$result[] = $item->name;
			$parent_id = ($n_model=='seed' || $n_model=='culture' || $n_model=='gsm' || $n_model=='szr' || $n_model=='fertilizer' || $n_model=='productionclass') ? $item->group->id() : $item->parent->id();
			
			if($parent_id){
				switch($n_model) {
					case 'seed': $parent_model = 'culture'; break;
					case 'culture': $parent_model = 'culturegroup'; break;
					case 'culturegroup': $parent_model = 'culturegroup'; break;
					case 'gsm': $parent_model = 'gsmgroup'; break;
					case 'gsmgroup': $parent_model = 'gsmgroup'; break;
					case 'szr': $parent_model = 'szrgroup'; break;
					case 'szrgroup': $parent_model = 'szrgroup'; break;
					case 'fertilizer': $parent_model = 'fertilizergroup'; break;
					case 'fertilizergroup': $parent_model = 'fertilizergroup'; break;
					case 'productionclass': $parent_model = 'production'; break;
					case 'production': $parent_model = 'production'; break;
				}
				$result = array_merge($result, Jelly::factory('client_transaction')->get_nomenclature_path($parent_model, $parent_id));
			}
		}
		
		return $result;
	}


    public function get_extras($model, $id){
        $result = array();
        if(!$model || !$id) return false;


        $record = Jelly::select($model, (int)$id);
        if(!$record instanceof Jelly_Model || !$record->loaded()) return false;

        switch($model){
            case 'glossary_seed':
                $result = array('crop_norm' => $record->crop_norm_mid, 'crop_norm_units' => $record->crop_norm_units->id(), 'units' => $record->units->id());
                break;
            case 'glossary_szr':
                $result = array('crop_norm' => $record->expend, 'crop_norm_units' => $record->expend_units->id(), 'units' => $record->units->id());
                break;
            case 'glossary_fertilizer':
                $result = array('crop_norm' => $record->expend, 'crop_norm_units' => $record->expend_units->id(), 'units' => $record->units->id());
                break;
            case 'client_handbook_techniquemobile':
                $gsm = $record->gsm->current();
                $result = array('gsm_norm' => $record->fuel_work, 'gsm_norm_units' => $record->fuel_work_units->id(), 'gsm_id'=>$gsm->id(), 'gsm_name'=>$gsm->name, 'price'=>$record->cost);
                break;
            case 'client_handbook_techniquetrailer':
                $gsm = $record->gsm->current();
                $result = array('gsm_norm' => $record->fuel_work, 'gsm_norm_units' => $record->fuel_work_units->id(), 'gsm_id'=>$gsm->id(), 'gsm_name'=>$gsm->name);
                break;


            case 'glossary_techmobile':
                $gsm = $record->gsm->current();
                $result = array('gsm_norm' => $record->fuel_work, 'gsm_norm_units' => $record->fuel_work_units->id(), 'gsm_id'=>$gsm->id(), 'gsm_name'=>$gsm->name);
                break;
            case 'glossary_techtrailer':
                $gsm = $record->gsm->current();
                $result = array('gsm_norm' => $record->fuel_work, 'gsm_norm_units' => $record->fuel_work_units->id(), 'gsm_id'=>$gsm->id(), 'gsm_name'=>$gsm->name);
                break;


            case 'client_handbook_personalgroup':
                $result = array('salary' => $record->average_salary, 'salary_units' => $record->average_salary_units->id());
                break;
            default:
                return false;
                break;
        }

        return $result;
    }


    public function get_handbook_version_values($license_id, $model, $id, $handbook_version){
        $result = array();
        $date = 0;

        if($handbook_version>0){
            $handbook_version_name = Jelly::select('client_handbookversionname', (int)$handbook_version);
            if(!$handbook_version_name instanceof Jelly_Model || !$handbook_version_name->loaded()) return $result;
            $date = $handbook_version_name->datetime;
        }

        $period = Session::instance()->get('periods');
        $period = (int)$period[0];

		$farm = Jelly::factory('farm')->get_session_farms();
        $farm = (int)$farm[0];

        $record = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                       ->and_where('license', '=', $license_id)
                                                       ->and_where('farm', '=', $farm)
                                                       ->and_where('period', '=', $period)
                                                       ->and_where('nomenclature_model', '=', $model)
                                                       ->and_where('nomenclature_id', '=', $id)
                                                       ->and_where('version_date', '=', $date)->limit(1)->execute();
        //print_r($handbook_version); exit;
        if(!$record instanceof Jelly_Model || !$record->loaded()) return $result;

        $result['price'] = $record->discount_price;
        return $result;
    }

}

