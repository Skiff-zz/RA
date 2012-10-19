<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_TransactionNomenclature extends Jelly_Model
{
	public static $money_units = array(
		1 => array('_id'=>1, 'name'=>'грн')
	);

	public static $amount_units = array(
		1 => array('_id'=>1, 'name'=>'т'),
        2 => array('_id'=>2, 'name'=>'ц'),
        3 => array('_id'=>3, 'name'=>'кг'),
        4 => array('_id'=>4, 'name'=>'г'),
		5 => array('_id'=>5, 'name'=>'л'),
        6 => array('_id'=>6, 'name'=>'п.е.')
	);

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('transaction_nomenclatures')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

				'transaction'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_transaction',
					'column'	=> 'client_transaction_id',
					'label'	=> 'Транзакция'
				)),

				'amount'		 =>  Jelly::field('String', array('label' => 'Кол-во/Объём')),
				'amount_units' =>  Jelly::field('Integer', array('label' => 'Единицы измерения')),

				'price_without_nds'			  => Jelly::field('String', array('label' => 'Цена без НДС')),
				'price_without_nds_units'	=> Jelly::field('Integer', array('label' => 'Единицы измерения')),

				'sum_without_nds'		  => Jelly::field('String', array('label' => 'Сумма без НДС')),
				'sum_without_nds_units'	=> Jelly::field('Integer', array('label' => 'Единицы измерения')),

				'sum_with_nds'	       => Jelly::field('String', array('label' => 'Сумма с НДС')),
				'sum_with_nds_units' => Jelly::field('Integer', array('label' => 'Единицы измерения')),

				'nds'		  => Jelly::field('String', array('label' => 'НДС')),
				'nds_units'	=> Jelly::field('Integer', array('label' => 'Единицы измерения')),

				'nomenclature_model'			=>  Jelly::field('String', array('label' => 'Модель номенклатуры',
					'rules' => array(
						'not_empty' => NULL
					))),
				'nomenclature_id'			=>  Jelly::field('Integer', array('label' => 'Номенклатура',
					'rules' => array(
						'not_empty' => NULL
					)))
		));
	}


	public function clear_transaction_nomenclature($transaction_id){
		Jelly::delete('client_transactionnomenclature')->where('transaction', '=', $transaction_id)->execute();
		//Jelly::update('client_transactionnomenclature')->value('deleted', true)->where('transaction', '=', $transaction_id)->execute();

	}
	
}

