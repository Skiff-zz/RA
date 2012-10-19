<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_PlanCulture2Field extends Jelly_Model
{

    
    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_planculture2field')
			->fields(array(
				'_id' 			=> new Field_Primary,
                
                'plan' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_plan',
                        'column'	=> 'client_planning_plan_id',
                        'label'		=> 'План'
                )),	
                
                'plan_culture'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_plan2culture',
                        'column'	=> 'client_planning_plan2culture_id',
                        'label'		=> 'Культура плана'
                )),	
                
                'field'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'field',
                        'column'	=> 'field_id',
                        'label'		=> 'Поле'
                )),
             
                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),	
				
				'atk_clone' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atkclone_atk',
                        'column'	=> 'client_planning_atkclone_atk_id',
                        'label'		=> 'АТК'
                )),	
                
                'field_square' => Jelly::field('String', array('label' => 'Подобрано полей, га')),
                
                'plan_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                'actual_inputs' => Jelly::field('String', array('label' => 'Затраты, грн/га')),
                
                'plan_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                'actual_income' => Jelly::field('String', array('label' => 'Доход, грн/га')),
                
                'plan_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                'actual_profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
                
                'plan_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),
                'actual_rentability' => Jelly::field('String', array('label' => 'Рентабельность, %'))
		));
	}
	
	
	public function save_from_grid($fields, $plan_id, $plan_culture_id){
		$do_not_delete = array();
		
		foreach($fields as $field){
			
			$model = Jelly::select('client_planning_planculture2field')->where('plan', '=', (int)$plan_id)
																	   ->and_where('plan_culture', '=', (int)$plan_culture_id)
																	   ->and_where('field', '=', (int)$field['field'])->limit(1)->load();
			if(!$model instanceof Jelly_Model || !$model->loaded()){
				$model = Jelly::factory('client_planning_planculture2field');
			}
			
			$model->set($field);
			$model->plan = (int)$plan_id;
			$model->plan_culture = (int)$plan_culture_id;
			$model->atk_clone = 0;
			$model->save();
			$do_not_delete[] = $model->id();
			
			$atk_clone = Jelly::factory('client_planning_atkclone_atk')->update_field_atk($plan_culture_id, $model->id(), $field['atk']);
			if($atk_clone){
				$model->atk_clone = $atk_clone;
				$model->save();
			}
		}
		
		
		if(count($do_not_delete)){
			Jelly::delete('client_planning_planculture2field')->where('plan_culture', '=', (int)$plan_culture_id)->and_where('_id', 'NOT IN', $do_not_delete)->execute();
			$old_items = Jelly::select('client_planning_atkclone_atk')->where('plan_culture', '=', (int)$plan_culture_id)->and_where('plan_field', 'NOT IN', $do_not_delete)->execute();
			foreach($old_items as $old_item) $old_item->delete();
		}else{
			Jelly::delete('client_planning_planculture2field')->where('plan_culture', '=', (int)$plan_culture_id)->execute();
			$old_items = Jelly::select('client_planning_atkclone_atk')->where('plan_culture', '=', (int)$plan_culture_id)->execute();
			foreach($old_items as $old_item) $old_item->delete();
		}
	}

}

