<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_OperationStage extends Model_Glossary_Abstract
{

    
    public static function initialize(Jelly_Meta $meta, $table_name  = '', $group_model = '')
	{
		$meta->table('client_operationstage')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удалена')),
				'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата последнего изменения',
					'rules' => array(
						'not_empty' => NULL
					))
				),

				'name'	=> Jelly::field('String', array('label' => 'Название',
					'rules' => array(
						'not_empty' => NULL
					))),

				'color'	=> Jelly::field('String', array('label' => 'Цвет')),

                'group'	=> Jelly::field('BelongsTo',array(
                        'foreign'	=> 'client_operationstagegroup',
                        'column'	=> 'group_id',
                        'label'		=> 'Группа'
                )),	
                
                
                //stuff
                
				'license' => Jelly::field('BelongsTo',array(
                        'foreign'	=> 'license',
                        'column'	=> 'license_id',
                        'label'		=> 'Лицензия'
                ))
		));
	}
	
	
	public function get_tree($license_id, $group_field = '', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$groups = Jelly::select('client_operationstagegroup')->with('parent')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select('client_operationstage')->with('group')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':group:_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':group:_id'] ? 'g'.$item[':group:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':group:color'] ? $item[':group:color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}
		return $res;
	}

}

