<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_ContragentGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name 	= 'client_contragentgroup', $items_model 	= 'client_contragent')
	{
		parent::initialize($meta, $table_name,  $items_model);

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
	
	
	public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items'){
		$this->result = array();
		$this->counter = 0;
		
		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];

		$groups = Jelly::select($this->meta()->model())->with('parent')->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();
		$names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->where('deleted', '=', false)->and_where('license', '=', $license_id)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, $names, 0, 0, $exclude, $with_cultures, $items_model->foreign['column']);

		if($with_cultures){
			
			$this->result = array_merge(array(array('id'=>'g0', 'level'=>-1, 'color'=>($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
			for($i=count($this->result)-1; $i>=0; $i--){
				$group_id = (int)mb_substr($this->result[$i]['id'], 1);
				$group_names =array();
				foreach($names as $name) {
					if(isset($exclude['names']) && in_array($name['_id'], $exclude['names'])){ continue; }
					if($name[':'.$items_model->foreign['column'].':_id']==$group_id) {
						$group_names[] = array(
							'id'	   => 'n'.$name['_id'],
							'title'    => $name['name'],
							'is_group' => true,
							'is_group_realy' => false,
							'level'	   => $this->result[$i]['level']+1,
							'children_g' => array(),
							'children_n' => array(),
							'parent'   => $group_id ? 'g'.$group_id : '',
							'color'    => $name['color'],
							'parent_color' => $this->result[$i]['color']
						);
					}
				}
				array_splice($this->result, $i+1, 0, $group_names);
			}
			if(isset($this->result[0]) && $this->result[0]['id']=='g0'){ array_splice($this->result, 0, 1); }
		}

		return $this->result;
	}
		
}

