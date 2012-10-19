<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Operations2Personal extends Jelly_Model
{


    public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('operations2personal')
			->fields(array(
				'_id' 			=> new Field_Primary,

                'operation'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operation',
                        'column'	=> 'client_operation_id',
                        'label'		=> 'Операция'
                )),

                'personal'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_handbook_personalgroup',
                        'column'	=> 'personal_id',
                        'label'		=> 'Персонал'
                )),


                'salary' =>  Jelly::field('String', array('label' => 'Зарплата')),
                'salary_units'  => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'glossary_units',
                        'column'	=> 'salary_units_id',
                        'label'		=> 'Единицы измерения'
                ))

		));
	}


    public function save_from_grid($personal, $operation_id){
        $data = array();
        foreach($personal as $pers){
            $data[] = array(
                'operation' => $operation_id,
                'personal' => $pers['name']['id'],
                'salary' => $pers['salary'],
                'salary_units' => $pers['salary_units']
            );
        }

        Jelly::delete('client_operations2personal')->where('operation', '=', $operation_id)->execute();

        foreach($data as $item){
            $model = Jelly::factory('client_operations2personal');
            $model->set($item);
            $model->save();
        }

		if(count($data)>0){
			$atk_operations = Jelly::select('client_planning_atk2operation')->
					with('atk')->
					where('operation','=',$operation_id)->
					execute();
			foreach($atk_operations as $atk_operation){
				$atk_operation->atk->set(array(
					'outdated'=>1
				))->save();
			}
		}
    }


    public function prepare_personal($personal){
        foreach($personal as &$pers){
            $handbook_item = Jelly::select('client_handbook_personalgroup', (int)$pers['personal']);
            if($handbook_item instanceof Jelly_Model && $handbook_item->loaded()){
                $model = 'glossary_personal'.($handbook_item->is_position ? '':'group');
                $p = Jelly::select($model, (int)$handbook_item->id_in_glossary);
                if($p instanceof Jelly_Model && $p->loaded()){
                    $pers['name'] = $p->name;
                    $pers['color'] = $p->color;
                }
            }
        }

        return $personal;
    }

}

