<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_Atk2Operation extends Jelly_Model
{


    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atk2operation')
			->fields(array(
				'_id' 			=> new Field_Primary,

                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),

                'number' => Jelly::field('String', array('label' => 'Номер')),

				'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),

                'checked' => Jelly::field('Boolean', array('label' => 'Включен')),

                'from_date' =>  Jelly::field('Integer', array('label' => 'Дата начала')),
                'to_date' =>  Jelly::field('Integer', array('label' => 'Дата конца')),

                'processing_koef' => Jelly::field('String', array('label' => 'Коэф. обработки')),
                'crop_lost' => Jelly::field('String', array('label' => 'Потеря урожайности')),

                'materials_costs' => Jelly::field('String', array('label' => 'Затраты на материалы')),
                'technics_costs' => Jelly::field('String', array('label' => 'Затраты на технику')),
                'personal_costs' => Jelly::field('String', array('label' => 'Затраты на персонал')),
                'total_costs' => Jelly::field('String', array('label' => 'Затраты полные')),
				
				'profit' => Jelly::field('String', array('label' => 'Прибыль, грн/га')),
				'rentability' => Jelly::field('String', array('label' => 'Рентабельность, %')),

                'materials' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperation2material',
                    'label'	=> 'Материалы',
                )),

                'technics' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperation2technic',
                    'label'	=> 'Техника',
                )),

                'personal' => Jelly::field('HasMany',array(
                    'foreign'	=> 'client_planning_atkoperation2personal',
                    'label'	=> 'Персонал',
                ))
		));
	}


    public function save_from_grid($operations, $atk_id, $is_version = false){
        $data = array();
        
        // Обратная связь
        $atk = Jelly::select('client_planning_atk', (int)$atk_id);
        
        if(!($atk instanceof Jelly_Model) or !$atk->loaded())
        	return;
        
        foreach($operations as $operation)
		{
            $date_from = 0; $date_to = 0;
            $dates = explode(' - ', $operation['date_from_to']);
            if(isset($dates[0])) $date_from = strtotime($dates[0]);
            if(isset($dates[1])) $date_to = strtotime($dates[1]);

            $data[] = array(
                //'id' => $operation['rowId'],
                'atk' => $atk_id,
                'operation' => $operation['name']['id'],
                'number' => $operation['number'],
                'checked' => $operation['undefined'], //тут лежит чекбокс.
                'from_date' => (int)$date_from,
                'to_date' => (int)$date_to,
                'processing_koef' => (float)$operation['processing_koef']>0 ? (float)$operation['processing_koef'] : '',
                'crop_lost' => (float)$operation['crop_lost']>0 ? (float)$operation['crop_lost'] : 0,
                'materials_costs' => (float)$operation['materials_costs']>0 ? (float)$operation['materials_costs'] : 0,
                'technics_costs' => (float)$operation['technics_costs']>0 ? (float)$operation['technics_costs'] : 0,
                'personal_costs' => (float)$operation['personal_costs']>0 ? (float)$operation['personal_costs'] : 0,
                'total_costs' => (float)$operation['total_costs']>0 ? (float)$operation['total_costs'] : 0,
				'profit' => ((float)$operation['profit'])+0,
                'rentability' => ((float)$operation['rentability'])+0
            );
            
            if($atk->culture->id())
            {
            	$op_class = Jelly::select('operations2cultures')->where('operation_id', '=', (int)$operation['name']['id'])->where('culture_id', '=', $atk->culture->id())->load();
            	
            	if(!($op_class instanceof Jelly_Model) or !$op_class->loaded())
            	{
           			$n = Jelly::factory('operations2cultures');
           			
           			$n->culture_id 		= $atk->culture->id();
           			$n->operation_id 	= (int)$operation['name']['id'];
           			
					$n->save();
					
					unset($n);
				}
            }
        }

        Jelly::delete('Client_Planning_Atk2Operation')->where('atk', '=', $atk_id)->execute();

        $result = array();
        foreach($data as $item)
		{
            $model = Jelly::factory('Client_Planning_Atk2Operation');
            $model->set($item);
            $model->save();
            $result[] = $model->id();
        }

        return $result;
    }


    public function prepare_operations($operations){
        $result = array(); $it = 0;

        foreach($operations as $operation){
            $result[$it] = $operation->as_array();
            unset($result[$it]['atk']);
            $result[$it]['operation'] = array(
                '_id' => $result[$it]['operation']->id(),
                'name' => $result[$it]['operation']->name,
                'color' => $result[$it]['operation']->color,
                'extras' => array('materials'=>array(), 'technics'=>array('techniquemobile'=>array(), 'techniquetrailer'=>array()), 'personal'=>array()) //Jelly::factory('client_operation')->get_operation_extras($result[$it]['operation']->id())
            );


            $result[$it]['materials'] = array();
            foreach($operation->materials as $material){
                $m = Jelly::factory('client_transaction')->get_nomenclature($material->material_model, $material->material_id);
                $result[$it]['materials'][] = array(
                    '_id' => $material->id(),
                    'material_model' => $material->material_model,
                    'material_id' => $material->material_id,
					'checked' => $material->checked,
					'profit' => $material->profit,
					'rentability' => $material->rentability,
					'crop_lost' => $material->crop_lost,
                    'crop_norm' => $material->crop_norm,
                    'units' => array('_id' => $material->units->id(), 'name' => $material->units->name),
                    'count' => $material->count,
                    'price' => $material->price,
                    'total' => $material->total,
                    'name' =>  $m['name'],
                    'color' => $m['color']
                );
            }
			
			$result[$it]['technics'] = Jelly::factory('client_planning_atkoperation2technic')->prepare_technics($operation->technics->as_array());

            $it++;
        }

        $keys = array();
        foreach($result as $op) $keys[] = $op['number'];
        array_multisort($keys, $result);

        return $result;
    }
    
    public function upd_operation($op_id, $atk_id){
        $atk = Jelly::select('client_planning_atk',(int)$atk_id);
        $op = Jelly::select('client_operation',(int)$op_id);
        $atk_op = Jelly::select('Client_Planning_Atk2Operation')->
                where('atk','=',(int)$atk_id)->
                where('operation','=',(int)$op_id)->
                limit(1)->
                execute();
        
        foreach($atk_op->materials as $a_mat){
            $a_mat->delete();
        }
        foreach($op->materials as $op_mat){
            $new_mat = Jelly::factory('client_planning_atkoperation2material');
            $new_mat->atk = $atk;
            $new_mat->atk_operation = $atk_op;
            $new_mat->material_model = $op_mat->material_model;
            $new_mat->material_id = $op_mat->material_id;
            
            $new_mat->crop_norm = $op_mat->crop_norm;
            $new_mat->units = $op_mat->crop_norm_units;
            $new_mat->save();
        }
        
        
        foreach($atk_op->personal as $a_pers){
            $a_pers->delete();
        }
        foreach($op->personal as $op_pers){
            $new_pers = Jelly::factory('client_planning_atkoperation2personal');
            $new_pers->atk = $atk;
            $new_pers->atk_operation = $atk_op;
            $new_pers->personal = $op_pers->personal;
            $new_pers->price = $op_pers->salary;
            $new_pers->save();
        }
        
        foreach($atk_op->technics as $a_tech){
            $a_tech->delete();
        }
        foreach($op->technics as $op_tech){
            $new_tech = Jelly::factory('client_planning_atkoperation2technic');
            $new_tech->atk = $atk;
            $new_tech->atk_operation = $atk_op;
            $new_tech->title = $op_tech->title;
            $new_tech->gsm = $op_tech->gsm;
            $new_tech->fuel_work = $op_tech->cons_norm;
            $new_tech->fuel_work_units = $op_tech->cons_norm_units;
            $new_tech->save();
            
            foreach($op_tech->trailer_block as $op_trail){
                $new_trail = Jelly::factory('Client_Planning_AtkOperationTechnicTrailerBlock');
                $new_trail->atk = $atk;
                $new_trail->atk_operation = $atk_op;
                $new_trail->atk_operation_technic = $new_tech->id();
                $new_trail->technic_trailer = $op_trail->technic_trailer;
                $new_trail->in_main = $op_trail->in_main;
                $new_trail->save();
            }
            
        
            foreach($op_tech->mobile_block as $op_mob){
                $new_mob = Jelly::factory('Client_Planning_AtkOperationTechnicMobileBlock');
                $new_mob->atk = $atk;
                $new_mob->atk_operation = $atk_op;
                $new_mob->atk_operation_technic = $new_tech->id();
                $new_mob->technic_mobile = $op_mob->technic_mobile;
                $new_mob->in_main = $op_mob->in_main;
                $new_mob->save();
            }
            
            foreach($op_tech->aggregates_block as $op_aggregate){
                $new_aggregate = Jelly::factory('Client_Planning_AtkOperationTechnicAggregateBlock');
                $new_aggregate->atk = $atk;
                $new_aggregate->atk_operation = $atk_op;
                $new_aggregate->atk_operation_technic = $new_tech->id();
                
                $new_aggregate->technic_mobile = $op_aggregate->technic_mobile;
                $new_aggregate->technic_trailer = $op_aggregate->technic_trailer;
                $new_aggregate->title = $op_aggregate->title;
                $new_aggregate->color = $op_aggregate->color;
                $new_aggregate->fuel_work = $op_aggregate->fuel_work;
                $new_aggregate->fuel_work_units = $op_aggregate->fuel_work_units;
                $new_aggregate->gsm = $op_aggregate->gsm;
                
                $new_aggregate->fuel_work_secondary = $op_aggregate->fuel_work_secondary;
                $new_aggregate->fuel_work_units_secondary = $op_aggregate->fuel_work_units_secondary;
                
                $new_aggregate->in_main = $op_aggregate->in_main;
                $new_aggregate->checked = $op_aggregate->checked;
                $new_aggregate->save();
            }
            
        }
        
    }

}