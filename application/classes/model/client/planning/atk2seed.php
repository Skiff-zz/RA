<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_Atk2Seed extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atk2seed')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),	
				
				'seed'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_seed',
                        'column'	=> 'glossary_seed_id',
                        'label'		=> 'Семена'
                )),
				
				'productions' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkseed2production',
                    'label'	=> 'Продукция',
                )),
				
				
				//ИННОВАЦИЯ
				'bio_crop' => Jelly::field('String', array('label' => 'Биологическая урожайность')),
				'bio_crop_units' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'units_id',
                        'label'		=> 'Единицы измерения'
                )),
				
                'calc_crop_percent' => Jelly::field('String', array('label' => 'Расчётная урожайность, %')),
                'calc_crop' => Jelly::field('String', array('label' => 'Расчётная урожайность')),
				'price' => Jelly::field('String', array('label' => 'Цена')),
				
				'disabled' => Jelly::field('Boolean', array('label' => 'Включен')),
				'operation_id' => Jelly::field('Integer', array('label' => 'ИД операции'))
                
		));
	}
    
    
    public function save_from_grid($seeds, $atk_id, $is_version = false){
        $data = array();
		$do_not_delete = array(); //ид записей, которые не надо удалять

        foreach($seeds as $seed){
			if(!(int)$seed['seed']['id'])continue;
			
			if(UTF8::strpos($seed['rowId'], 'new_')!==false || $is_version){
				$seed_row = Jelly::factory('Client_Planning_Atk2Seed');
			}else{
				$seed_row = Jelly::select('Client_Planning_Atk2Seed', (int)$seed['rowId']);
				if(!$seed_row instanceof Jelly_Model || !$seed_row->loaded()) $seed_row = Jelly::factory('Client_Planning_Atk2Seed');
			}
			
			$seed_row->atk = $atk_id;
			$seed_row->seed = (int)$seed['seed']['id'];
			$seed_row->operation_id = (int)$seed['seed']['operation_id'];
			$seed_row->disabled = (boolean)$seed['seed']['disabled'];
			if(!isset($seed['productionclass']) || !$seed['productionclass'] || !$seed['productionclass'][0] || !$seed['productionclass'][0]['id']){
				$seed_row->bio_crop = (float)$seed['bio_crop'][0]['value'];
				$seed_row->bio_crop_units = (int)$seed['bio_crop'][0]['selectedUnits'];
				$seed_row->calc_crop_percent = (float)$seed['calc_crop_percent'][0];
				$seed_row->calc_crop = (float)$seed['calc_crop'][0];
				$seed_row->price = (float)$seed['price'][0]['value'];
			}
			$seed_row->save();
			$do_not_delete[] = $seed_row->id();
			
			
			$do_not_delete_prod = array();
			
			for($i=0; $i<count($seed['productionclass']); $i++){
				if(!(int)$seed['productionclass'][$i]['id'])continue;
				
				$prod = Jelly::select('client_planning_atkseed2production')->where('atk_seed', '=', $seed_row->id())->and_where('productionclass', '=', (int)$seed['productionclass'][$i]['id'])->limit(1)->load();
				if(!$prod instanceof Jelly_Model || !$prod->loaded()){
					$prod = Jelly::factory('client_planning_atkseed2production');
				}
				
				$prod->atk = $atk_id;
				$prod->atk_seed = $seed_row->id();
				$prod->bio_crop = (float)$seed['bio_crop'][$i]['value'];
				$prod->bio_crop_units = (int)$seed['bio_crop'][$i]['selectedUnits'];
				$prod->calc_crop_percent = (float)$seed['calc_crop_percent'][$i];
				$prod->calc_crop = (float)$seed['calc_crop'][$i];
				$prod->production = (int)$seed['production'][$i]['id'];
				$prod->productionclass = (int)$seed['productionclass'][$i]['id'];
				$prod->price = (float)$seed['price'][$i]['value'];
				$prod->save();

				$do_not_delete_prod[] = $prod->id();
			}
			
			if(count($do_not_delete_prod)) Jelly::delete('client_planning_atkseed2production')->where('atk_seed', '=', $seed_row->id())->and_where('_id', 'NOT IN', $do_not_delete_prod)->execute();
			else						   Jelly::delete('client_planning_atkseed2production')->where('atk_seed', '=', $seed_row->id())->execute();
			
        }
        
        if(count($do_not_delete)){
			Jelly::delete('client_planning_atk2seed')->where('atk', '=', $atk_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
			Jelly::delete('client_planning_atkseed2production')->where('atk', '=', $atk_id)->and_where('atk_seed', 'NOT IN', $do_not_delete)->execute();
		}else{
			Jelly::delete('client_planning_atk2seed')->where('atk', '=', $atk_id)->execute();
			Jelly::delete('client_planning_atkseed2production')->where('atk', '=', $atk_id)->execute();
		}

    }

//Array
//(
//    [0] => Array
//        (
//            [rowId] => new_ext-gen2874
//            [seed] => Array
//                (
//                    [id] => 12
//                    [bio_crop] => 124
//                    [bio_crop_units] => 44
//                    [productionIds] => Array
//                        (
//                            [0] => 6
//                            [1] => 7
//                        )
//
//                    [value] => Боярашниковые семена с очень длинным названием
//                )
//
//            [bio_crop] => Array
//                (
//                    [0] => Array
//                        (
//                            [units] => Array
//                                (
//                                    [0] => Array
//                                        (
//                                            [value] => 44
//                                            [text] => т/га
//                                        )
//
//                                    [1] => Array
//                                        (
//                                            [value] => 45
//                                            [text] => ц/га
//                                        )
//
//                                    [2] => Array
//                                        (
//                                            [value] => 46
//                                            [text] => кг/га
//                                        )
//
//                                    [3] => Array
//                                        (
//                                            [value] => 47
//                                            [text] => п.е./га
//                                        )
//
//                                )
//
//                            [selectedUnits] => 44
//                            [value] => 124
//                        )
//
//                    [1] => Array
//                        (
//                            [units] => Array
//                                (
//                                    [0] => Array
//                                        (
//                                            [value] => 44
//                                            [text] => т/га
//                                        )
//
//                                    [1] => Array
//                                        (
//                                            [value] => 45
//                                            [text] => ц/га
//                                        )
//
//                                    [2] => Array
//                                        (
//                                            [value] => 46
//                                            [text] => кг/га
//                                        )
//
//                                    [3] => Array
//                                        (
//                                            [value] => 47
//                                            [text] => п.е./га
//                                        )
//
//                                )
//
//                            [selectedUnits] => 44
//                            [value] => 124
//                        )
//
//                )
//
//            [calc_crop_percent] => Array
//                (
//                    [0] => 100.00
//                    [1] => 100.00
//                )
//
//            [calc_crop] => Array
//                (
//                    [0] => 124.00 т/га
//                    [1] => 124.00 т/га
//                )
//
//            [production] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 4
//                            [value] => Варенье
//                        )
//
//                    [1] => Array
//                        (
//                            [id] => 4
//                            [value] => Варенье
//                        )
//
//                )
//
//            [productionclass] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 6
//                            [value] => 1 сорт
//                        )
//
//                    [1] => Array
//                        (
//                            [id] => 7
//                            [value] => 2 сорт
//                        )
//
//                )
//
//            [price] => Array
//                (
//                    [0] => Array
//                        (
//                            [units] => грн/т
//                            [value] => 23 грн/т
//                        )
//
//                    [1] => Array
//                        (
//                            [units] => грн/т
//                            [value] => 20 грн/т
//                        )
//
//                )
//
//        )
//
//    [1] => Array
//        (
//            [rowId] => new_ext-gen3261
//            [seed] => Array
//                (
//                    [id] => 9
//                    [productionIds] => Array
//                        (
//                            [0] => 7
//                            [1] => 8
//                        )
//
//                    [bio_crop] => 23
//                    [bio_crop_units] => 45
//                    [value] => Семена-Б
//                )
//
//            [bio_crop] => Array
//                (
//                    [0] => Array
//                        (
//                            [units] => Array
//                                (
//                                    [0] => Array
//                                        (
//                                            [value] => 44
//                                            [text] => т/га
//                                        )
//
//                                    [1] => Array
//                                        (
//                                            [value] => 45
//                                            [text] => ц/га
//                                        )
//
//                                    [2] => Array
//                                        (
//                                            [value] => 46
//                                            [text] => кг/га
//                                        )
//
//                                    [3] => Array
//                                        (
//                                            [value] => 47
//                                            [text] => п.е./га
//                                        )
//
//                                )
//
//                            [selectedUnits] => 45
//                            [value] => 23.00 ц/га
//                        )
//
//                    [1] => Array
//                        (
//                            [units] => Array
//                                (
//                                    [0] => Array
//                                        (
//                                            [value] => 44
//                                            [text] => т/га
//                                        )
//
//                                    [1] => Array
//                                        (
//                                            [value] => 45
//                                            [text] => ц/га
//                                        )
//
//                                    [2] => Array
//                                        (
//                                            [value] => 46
//                                            [text] => кг/га
//                                        )
//
//                                    [3] => Array
//                                        (
//                                            [value] => 47
//                                            [text] => п.е./га
//                                        )
//
//                                )
//
//                            [selectedUnits] => 45
//                            [value] => 23.00 ц/га
//                        )
//
//                )
//
//            [calc_crop_percent] => Array
//                (
//                    [0] => 100.00
//                    [1] => 100.00
//                )
//
//            [calc_crop] => Array
//                (
//                    [0] => 23.00 ц/га
//                    [1] => 23.00 ц/га
//                )
//
//            [production] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 4
//                            [value] => Варенье
//                        )
//
//                    [1] => Array
//                        (
//                            [id] => 4
//                            [value] => Варенье
//                        )
//
//                )
//
//            [productionclass] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 7
//                            [value] => 2 сорт
//                        )
//
//                    [1] => Array
//                        (
//                            [id] => 8
//                            [value] => 3 сорт
//                        )
//
//                )
//
//            [price] => Array
//                (
//                    [0] => Array
//                        (
//                            [units] => грн/ц
//                            [value] => 30 грн/ц
//                        )
//
//                    [1] => Array
//                        (
//                            [units] => грн/ц
//                            [value] => 40 грн/ц
//                        )
//
//                )
//
//        )
//
//    [2] => Array
//        (
//            [rowId] => new_ext-gen3648
//            [seed] => Array
//                (
//                    [id] => 13
//                    [bio_crop] => 65
//                    [bio_crop_units] => 44
//                    [value] => сем 2 культ 1 long-long-long time ago it used to be
//                )
//
//            [bio_crop] => Array
//                (
//                    [0] => 
//                )
//
//            [calc_crop_percent] => Array
//                (
//                    [0] => 
//                )
//
//            [calc_crop] => Array
//                (
//                    [0] => 
//                )
//
//            [production] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 
//                            [value] => 
//                        )
//
//                )
//
//            [productionclass] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 
//                            [value] => 
//                        )
//
//                )
//
//            [price] => Array
//                (
//                    [0] => Array
//                        (
//                            [value] => 12
//                        )
//
//                )
//
//        )
//
//    [3] => Array
//        (
//            [rowId] => new_ext-gen3959
//            [seed] => Array
//                (
//                    [id] => 
//                    [value] => 
//                )
//
//            [bio_crop] => Array
//                (
//                    [0] => 
//                )
//
//            [calc_crop_percent] => Array
//                (
//                    [0] => 
//                )
//
//            [calc_crop] => Array
//                (
//                    [0] => 
//                )
//
//            [production] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 
//                            [value] => 
//                        )
//
//                )
//
//            [productionclass] => Array
//                (
//                    [0] => Array
//                        (
//                            [id] => 
//                            [value] => 
//                        )
//
//                )
//
//            [price] => Array
//                (
//                    [0] => 54
//                )
//
//        )
//
//)

}

