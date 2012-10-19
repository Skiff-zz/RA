<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_SharePayment extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta){
		$meta->table('sharepayments')
			->fields(array(
				
				'_id' => new Field_Primary,
				
				'share'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'client_share',
					'column'	=> 'client_share_id',
					'label'	=> 'Пай',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				
				'payment_start_date' =>  Jelly::field('Integer', array('label' => 'Дата начала аренды')),
				'payment_end_date'	 =>  Jelly::field('Integer', array('label' => 'Дата окончания аренды')),
				
				'years'			=>  Jelly::field('Integer', array('label' => 'К-во лет')),
				
				'land_price'	=> Jelly::field('Float', array('label' => 'Оценка земли')),
				'percents'		=> Jelly::field('Float', array('label' => '%')),
				'year_price'	=> Jelly::field('Float', array('label' => 'грн/год')),
				'rent_price'	=> Jelly::field('Float', array('label' => 'Стоимость аренды')),
				'land_tax'		=> Jelly::field('Float', array('label' => 'Налог на землю')),
				'income_tax'	=> Jelly::field('Float', array('label' => 'Подоходный налог'))
		));
	}
	
	
	public function save_from_grid($grid_data, $share_id){
		$ids = array(); $first = true;

		foreach($grid_data as $item){
			if($first){
				$first = false;
				continue;
			}
			
			if(UTF8::strpos($item['rowId'], 'new_') !== false){
				$sharepayment_row = Jelly::factory('client_sharepayment');
			}else{
				$sharepayment_row = Jelly::select('client_sharepayment', (int)$item['rowId']);
				if(!$sharepayment_row instanceof Jelly_Model || !$sharepayment_row->loaded()) $sharepayment_row = Jelly::factory('client_sharepayment');
			}
			
			$sharepayment_row->share = $share_id;
			$sharepayment_row->payment_start_date = (int)trim($item['payment_date']['payment_start_date']);
			$sharepayment_row->payment_end_date   = (int)trim($item['payment_date']['payment_end_date']);
			$sharepayment_row->years      = (int)$item['years'];
			$sharepayment_row->land_price = (float)$item['land_price'];
			$sharepayment_row->percents   = (float)$item['percents'];
			$sharepayment_row->year_price = (float)$item['year_price'];
			$sharepayment_row->rent_price = (float)$item['rent_price'];
			$sharepayment_row->land_tax	  = (float)$item['land_tax'];
			$sharepayment_row->income_tax = (float)$item['income_tax'];
			
			$sharepayment_row->save();
			
			$ids[] = $sharepayment_row->id();
		}
		
		if(count($ids)){
			Jelly::delete('client_sharepayment')->where('share', '=', $share_id)->where('_id', 'NOT IN', $ids)->execute();
		}else{
			Jelly::delete('client_sharepayment')->where('share', '=', $share_id)->execute();
		}
	}
	
	
	public function get_share_payments_data($share_id){
		$result = array('land_price' => 0, 'year_price' => 0, 'rent_price' => 0, 'land_tax' => 0, 'income_tax' => 0);
		$payments = Jelly::select('Client_SharePayment')->where('share', '=', $share_id)->order_by('payment_end_date')->execute()->as_array();
		$sum = 0;
		$years = 0;
		
		foreach($payments as $payment){
			$result['land_price'] = $payment['land_price'];
			
			$sum += $payment['year_price'] * $payment['years'];
			$years += $payment['years'];
			
			$result['rent_price'] += $payment['rent_price'];
			$result['land_tax'] += $payment['land_tax'];
			$result['income_tax'] += $payment['income_tax'];
		}
		
		$result['year_price'] = $years>0 ? $sum/$years : 0;
		
		return $result;
	}
	
	
	
	public function delete($key = NULL){
        //wtf? falling back to parent
        if (!is_null($key)) return parent::delete($key);
		
		Jelly::delete('Client_SharePayment')->where('_id', '=', $this->id())->execute();
    }
	
	
}

