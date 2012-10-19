<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_Plan2Culture extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_plan2culture')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'plan'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_plan',
                        'column'	=> 'client_planning_plan_id',
                        'label'		=> 'План'
                )),	
				
				'culture'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_culture',
                        'column'	=> 'glossary_culture_id',
                        'label'		=> 'Культура'
                )),
                
                
                'priority' => Jelly::field('Integer', array('label' => 'Приоритет')),
                'planned_square' => Jelly::field('String', array('label' => 'Плановые площади, га')),
                
                'choosen_fields_count' => Jelly::field('Integer', array('label' => 'Подобрано полей, шт')),
                'choosen_fields_square' => Jelly::field('String', array('label' => 'Подобрано полей, га')),
                
                'plan_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                'actual_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                
                'plan_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                'actual_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                
                'plan_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                'actual_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                
                'plan_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),
                'actual_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),
                
                'fields' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_planculture2field',
                    'label'	=> 'Поля'
                ))
		));
	}
	
	
	public function save_from_grid($cultures, $plan_id){
		$do_not_delete = array();
		
		foreach($cultures as $culture){
			
			$model = Jelly::select('Client_Planning_Plan2Culture')->where('plan', '=', (int)$plan_id)->and_where('culture', '=', (int)$culture['culture'])->limit(1)->load();
			if(!$model instanceof Jelly_Model || !$model->loaded()){
				$model = Jelly::factory('Client_Planning_Plan2Culture');
			}
			
			$model->set($culture);
			$model->plan = (int)$plan_id;
			$model->save();
			$do_not_delete[] = $model->id();
			
			Jelly::factory('client_planning_planculture2field')->save_from_grid($culture['culture_fields'], $plan_id, $model->id());
		}
		
		
		if(count($do_not_delete)){
			Jelly::delete('client_planning_plan2culture')->where('plan', '=', (int)$plan_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
		}else{
			Jelly::delete('client_planning_plan2culture')->where('plan', '=', (int)$plan_id)->execute();
		}
	}

}

