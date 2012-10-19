<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Planning_AtkOperation2Personal extends Jelly_Model
{


    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('planning_atkoperation2personal')
			->fields(array(
				'_id' 			=> new Field_Primary,

                'atk'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk',
                        'column'	=> 'client_planning_atk_id',
                        'label'		=> 'АТК'
                )),

                'atk_operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_planning_atk2operation',
                        'column'	=> 'client_planning_atk2operation_id',
                        'label'		=> 'АТК операция'
                )),

                'personal'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personalgroup',
                        'column'	=> 'personal_id',
                        'label'		=> 'Персонал'
                )),

				'checked' => Jelly::field('Boolean', array('label' => 'Включен')),
                'personal_count' => Jelly::field('Integer', array('label' => 'К-во персонала')),

                'price' => Jelly::field('String', array('label' => 'Цена')),
                'total' => Jelly::field('String', array('label' => 'Затраты'))
		));
	}

    public function save_from_grid($personal, $atk_id, $is_version = false){
        $data = array();
        foreach($personal as $operation){
            if(!isset($operation['personal']) || !count($operation['personal']) || !isset($operation['personal'][0]['id']) || !$operation['personal'][0]['id'])continue;

            for($i=0; $i<count($operation['personal']); $i++){
                $data[] = array(
                    //'id' => $material['rowId'],
                    'atk' => $atk_id,
                    'atk_operation' => $operation['atk_operation'],
                    'personal' => (int)$operation['personal'][$i]['id'],
					'checked' => $operation['personal'][$i]['checked'],
                    'personal_count' => (int)$operation['personal_count'][$i],
                    'price' => (float)$operation['price'][$i]>0 ? (float)$operation['price'][$i] : 0,
                    'total' => (float)$operation['total'][$i]>0 ? (float)$operation['total'][$i] : 0
                );

                /** Обратная связь **/
                $op = Jelly::select('client_planning_atk2operation', (int)$operation['atk_operation']);

                if($op instanceof Jelly_Model and $op->loaded() and $op->operation->id())
                {
                	$op_test = Jelly::select('client_operations2personal')
								->where('operation', '=', $op->operation->id())
								->where('personal', '=', (int)$operation['personal'][$i]['id'])
					->load();

                	if(!($op_test instanceof Jelly_Model) or !$op_test->loaded())
                	{
               			$p = Jelly::select('client_handbook_personalgroup', (int)$operation['personal'][$i]['id']);

               			if($p instanceof Jelly_Model and $p->loaded())
               			{
			   				$n = Jelly::factory('client_operations2personal');

	               			$n->operation 		= $op->operation->id();
	               			$n->personal	 	= $p->id();
	               			$n->salary		 	= $p->average_salary_units->id()==52 ? $p->average_salary : 0; // грн/га
	               			$n->salary_units 	= 52; //$p->average_salary_units;

	               			$n->save();

	               			unset($n);
               			}
 					}
                }
            }
        }

        Jelly::delete('Client_Planning_AtkOperation2Personal')->where('atk', '=', $atk_id)->execute();

        foreach($data as $item){
            $model = Jelly::factory('Client_Planning_AtkOperation2Personal');
            $model->set($item);
            $model->save();
        }
    }

}

