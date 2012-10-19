<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_HandbookVersion extends Jelly_Model
{


	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('handbook_versions')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

                'nomenclature_model'			=>  Jelly::field('String', array('label' => 'Модель номенклатуры',
					'rules' => array(
						'not_empty' => NULL
					))),
				'nomenclature_id'			=>  Jelly::field('Integer', array('label' => 'Номенклатура',
					'rules' => array(
						'not_empty' => NULL
					))),

                'amount'		 =>  Jelly::field('Float', array('label' => 'Кол-во/Объём')),
				'amount_units' =>  Jelly::field('Integer', array('label' => 'Единицы измерения')),

                'discount_price'			  => Jelly::field('Float', array(
					'label' => 'Учётная цена',
					'default' => 0
					)),
				'discount_price_units'	=> Jelly::field('String', array('label' => 'Единицы измерения')),

				'planned_price'			  => Jelly::field('Float', array('label' => 'Плановая цена')),
				'planned_price_manual'			  => Jelly::field('Integer', array('label' => 'Была ли Плановая цена введена вручную')), // или является просто копией учетной
				'planned_price_units'	=> Jelly::field('String', array(
					'label' => 'Единицы измерения',
					'default' => '1'
					)),

                'version_date'	=>  Jelly::field('Integer', array('label' => 'Дата версии')),


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


    public function updateCurrentHandbook($license_id, $period, $farm, $nomenclature_model, $nomenclature_id, $amount_units, $discount_price_units, $delete = false){

		$amount_units = ($amount_units==2 || $amount_units==3 || $amount_units==4) ? 1 : $amount_units;
		$amount_units_arr = $amount_units==1 ? array(1, 2, 3, 4) : array($amount_units);
        $last_remains = $this->get_last_remains($license_id, $period, $farm, $nomenclature_model, $nomenclature_id, $amount_units, $discount_price_units);

        $where_arr = array();
        $where_arr[] = 'transactions.deleted = 0';
        $where_arr[] = 'transactions.license_id = '.$license_id;
        $where_arr[] = 'transactions.farm_id = '.$farm;
        $where_arr[] = 'transactions.period_id = '.$period;
        $where_arr[] = 'transaction_nomenclatures.deleted = 0';
        $where_arr[] = 'transaction_nomenclatures.nomenclature_model = \''.$nomenclature_model.'\'';
        $where_arr[] = 'transaction_nomenclatures.nomenclature_id = '.$nomenclature_id;
        $where_arr[] = 'transaction_nomenclatures.amount_units IN ('.implode(', ', $amount_units_arr).')';
        $where_arr[] = 'transaction_nomenclatures.price_without_nds_units = '.$discount_price_units;
        $where_arr[] = 'transaction_nomenclatures.client_transaction_id = transactions._id';
        if(isset($last_remains['transaction_date'])) $where_arr[] = 'transactions.transaction_date >= '.$last_remains['transaction_date'];
        $WHERE = 'WHERE '.implode(' AND ', $where_arr);

        $query = "SELECT transactions.type,
                         transactions.transaction_date,
                         transactions.create_date,
                         transaction_nomenclatures.amount,
						 transaction_nomenclatures.amount_units,
                         transaction_nomenclatures.price_without_nds
					FROM transactions, transaction_nomenclatures
				  $WHERE";

		$db = Database::instance();
		$result = $db->query(DATABASE::SELECT, $query, true);
		$data = array();
		foreach($result as $row){
		   $data[] = (array)$row;
		}

        //убираем транзакции, которые были в тот же день с останками, но созданы раньше их
        if(isset($last_remains['transaction_date'])){
            for($i=count($data)-1; $i>=0; $i--){
                if(($data[$i]['transaction_date']==$last_remains['transaction_date'] && $data[$i]['create_date']<$last_remains['create_date']) || $data[$i]['type']=='5'){
                    array_splice($data, $i, 1);
                }
            }
            $data[] = $last_remains;
        }



        $amount = 0;
        $discount_price = 0;
        $chislitel = 0;
        $znamenatiel = 0;

        foreach($data as $item){
			if($item['amount_units']==2) $item['amount'] *= 0.1;
			if($item['amount_units']==3) $item['amount'] *= 0.001;
			if($item['amount_units']==4) $item['amount'] *= 0.000001;

			if($item['amount_units']==2) $item['price_without_nds'] *= 10;
			if($item['amount_units']==3) $item['price_without_nds'] *= 1000;
			if($item['amount_units']==4) $item['price_without_nds'] *= 1000000;

            $amount += $item['amount'] * (($item['type']==3 || $item['type']==4) ? -1:1);
            if(!($item['type']==3 || $item['type']==4)){
                $chislitel += $item['amount']*$item['price_without_nds'];
                $znamenatiel += $item['amount'];
            }
        }
        $discount_price = $znamenatiel>0 ? $chislitel/$znamenatiel : 0;




        $handbook_version = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                                   ->and_where('license', '=', $license_id)
                                                                   ->and_where('farm', '=', $farm)
                                                                   ->and_where('period', '=', $period)
                                                                   ->and_where('nomenclature_model', '=', $nomenclature_model)
                                                                   ->and_where('nomenclature_id', '=', $nomenclature_id)
                                                                   ->and_where('amount_units', '=', $amount_units)
                                                                   ->and_where('version_date', '=', 0)
                                                                   ->and_where('discount_price_units', '=', $discount_price_units)->limit(1)->execute();
        if(!$handbook_version instanceof Jelly_Model || !$handbook_version->loaded()){
            $handbook_version = Jelly::factory('client_handbookversion');
            $handbook_version->nomenclature_model = $nomenclature_model;
            $handbook_version->nomenclature_id = $nomenclature_id;
            $handbook_version->amount_units = $amount_units;
            $handbook_version->discount_price_units = $discount_price_units;
            $handbook_version->version_date = 0;
            $handbook_version->license = $license_id;
            $handbook_version->farm = $farm;
            $handbook_version->period = $period;

        }

        $handbook_version->amount = $amount;
        $handbook_version->update_date = time();
        $handbook_version->discount_price = $discount_price;
		$handbook_version->planned_price  = $discount_price;
		$handbook_version->planned_price_units = $amount_units;
		$handbook_version->planned_price_manual = false;

		if(!count($data)){//если по этой версии все транзакции удалены
			$handbook_version->delete();
			return 0;
		}else{
			$handbook_version->save();
			return $handbook_version->id();
		}
    }




    public function get_last_remains($license_id, $period, $farm, $nomenclature_model, $nomenclature_id, $amount_units, $discount_price_units){

		$amount_units = ($amount_units==1 || $amount_units==2 || $amount_units==3 || $amount_units==4) ? array(1, 2, 3, 4) : array($amount_units);

        $where_arr = array();
        $where_arr[] = 'transactions.deleted = 0';
        $where_arr[] = 'transactions.type = 5';
        $where_arr[] = 'transactions.license_id = '.$license_id;
        $where_arr[] = 'transactions.farm_id = '.$farm;
        $where_arr[] = 'transactions.period_id = '.$period;
        $where_arr[] = 'transaction_nomenclatures.deleted = 0';
        $where_arr[] = 'transaction_nomenclatures.nomenclature_model = \''.$nomenclature_model.'\'';
        $where_arr[] = 'transaction_nomenclatures.nomenclature_id = '.$nomenclature_id;
        $where_arr[] = 'transaction_nomenclatures.amount_units IN ('.implode(', ', $amount_units).')';
        $where_arr[] = 'transaction_nomenclatures.price_without_nds_units = '.$discount_price_units;
        $where_arr[] = 'transaction_nomenclatures.client_transaction_id = transactions._id';
        //$where_arr[] = 'transactions.transaction_date = (select max(transaction_date) from transactions)';
        $WHERE = 'WHERE '.implode(' AND ', $where_arr);

        $query = "SELECT transactions.type,
                         transactions.transaction_date,
                         transactions.create_date,
                         transaction_nomenclatures.amount,
						 transaction_nomenclatures.amount_units,
                         transaction_nomenclatures.price_without_nds
					FROM transactions, transaction_nomenclatures
				  $WHERE
                ORDER BY transactions.transaction_date DESC, transactions.create_date DESC";

		$db = Database::instance();
		$result = $db->query(DATABASE::SELECT, $query, true);
		$data = array();
		foreach($result as $row){
		   $data = (array)$row; break;
		}

        return $data;
    }





    public function get_nomenclature_status($license_id, $data){

		$period = Session::instance()->get('periods');
		$period = arr::get($period, 0, 0);
        $farm = arr::get($data, 'farm_id', 0);
        $nomenclature_model = arr::get($data, 'nomenclature_model', 0);
        $nomenclature_id = arr::get($data, 'nomenclature_id', 0);
        $amount_units = arr::get($data['amount_units'], '_id', 0);
        $discount_price_units = arr::get($data['price_without_nds_units'], '_id', 0);
        $type = arr::get($data['type'], '_id', 0);
        $result = ($type==3 || $type==4) ? 'minus' : 'plus';

        $last_remains = $this->get_last_remains($license_id, $period, $farm, $nomenclature_model, $nomenclature_id, $amount_units, $discount_price_units);

        if(isset($last_remains['transaction_date'])){
            if($last_remains['transaction_date'] > $data['transaction_date_timestamp']){
                $result = 'not_relevant';
            }else if($last_remains['transaction_date'] == $data['transaction_date_timestamp']){
                if($last_remains['create_date'] > $data['create_date']){
                    $result = 'not_relevant';
                }
            }
        }

        return $result;
    }





	public function fix_transactions_version(){
		try{
			$transactions = Jelly::select('client_transaction')->where('deleted', '=', false)->execute()->as_array();
			foreach($transactions as $transaction) {
				$nomenclatures = Jelly::select('client_transactionnomenclature')->where('deleted', '=', false)->and_where('transaction', '=', $transaction['_id'])->execute()->as_array();
				foreach($nomenclatures as $nomenclature){
					$this->updateCurrentHandbook($transaction['license'], $transaction['period'], $transaction['farm'], $nomenclature['nomenclature_model'], $nomenclature['nomenclature_id'], $nomenclature['amount_units'], $nomenclature['price_without_nds_units']);
				}
			}
		}catch(Exception $e){
			return false;
		}
		return true;
	}

}