<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_Contragent extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'client_contragent', $group_model = 'client_contragentgroup')
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)
			->fields(array(
				'license'	    => Jelly::field('BelongsTo',array(
						'foreign'	=> 'license',
						'column'	=> 'license_id',
						'label'		=> 'Лицензия'
				)),

				'address_country'	    => Jelly::field('String', array('label' => 'Страна')),
				'address_region'	   => Jelly::field('String', array('label' => 'Область')),
				'address_city'			=> Jelly::field('String', array('label' => 'Город')),
				'address_zip'			=> Jelly::field('String', array('label' => 'Индекс')),
				'address_street'		=> Jelly::field('String', array('label' => 'Улица и дом')),

				'phone'	=> Jelly::field('String', array('label' => 'Телефон')),
				'email' => new Field_Email(array('label' => 'E-mail',
					'rules' => array(
						'max_length' => array(1000),
						'min_length' => array(3)
					)
				)),
				'unp'	=> Jelly::field('String', array('label' => 'Номер УНП (УНН)')),
				'kzpo'	=> Jelly::field('String', array('label' => 'Код КЗПО (ЕГРПОУ)')),


				//Руководитель
				'first_name'	=> Jelly::field('String', array('label' => 'Имя',
					'rules'  => array(
						'max_length' => array(1000)
					))),
				'last_name'		=> Jelly::field('String', array('label' => 'Фамилия',
					'rules'  => array(
						'max_length' => array(1000)
					))),
				'middle_name'	=> Jelly::field('String', array('label' => 'Отчество',
					'rules'  => array(
						'max_length' => array(1000)
					))),
				'chief_email' => new Field_Email(array('label' => 'E-mail',
					'rules' => array(
						'max_length' => array(1000),
						'min_length' => array(3)
					)
				))
				
			 ));
	}
	
	
	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();
		
		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];

		$groups = Jelly::select($group_model_name)->with('parent')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select($model_name)->with($group_field)->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF')
				);
			}
		}
		return $res;
	}
	
	protected function get_groups($groups, $parent){
		foreach($groups as $group){
			if($group[':parent:_id']==$parent){
				$this->result[$this->counter] = $group['_id'];
				$this->counter++;
				$this->get_groups($groups, $group['_id']);
			}
		}
	}

}

