<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Seed extends Model_Glossary_Abstract
{

	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_seeds', $group_model = 'glossary_culture')
	{
		parent::initialize($meta,  $table_name, $group_model );

        $meta->table($table_name)
			->fields(array(
					'bio_crop' =>  Jelly::field('String', array('label' => 'Био-урожай')),
					'bio_crop_units' => Jelly::field('BelongsTo',array(
						'foreign' => 'glossary_units',
						'column' => 'bio_corp_units_id',
						'label'	   => 'Единицы измерения'
					)),
					'producer' => Jelly::field('ManyToMany',array(
							'foreign'	=>'client_producer',
							'label'		=> 'Производитель',
							'column'	=> 'producer',
							'through'   => array('model'=>'seed2prdcr','columns'=>array('seed_id','producer_id'))
					)),
					'units'	    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'units_id',
							'label'		=> 'Единицы измерения'
					)),

					'crop_norm_min' =>  Jelly::field('String', array('label' => 'Норма внесения мин.')),
					'crop_norm_mid' =>  Jelly::field('String', array('label' => 'Норма внесения средн.')),
					'crop_norm_max' =>  Jelly::field('String', array('label' => 'Норма внесения макс.')),
					'crop_norm_units'  => Jelly::field('BelongsTo',array(
							'foreign'	=> 'glossary_units',
							'column'	=> 'crop_norm_units_id',
							'label'		=> 'Единицы измерения'
					))
			 ));

	}


	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $group_field = 'group', $exclude = array(), $extras = false){
		$this->result = array();
		$this->counter = 0;
		$res = array();
        $it = 0;

		$groups = Jelly::select('glossary_culturegroup')->with('parent')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$cultures = Jelly::select('glossary_culture')->with('group')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$seeds = Jelly::select('glossary_seed')->with('group')->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();

		$this->get_groups($groups, 0);
		$this->get_cultures($this->result, $cultures);

		$this->result[] = array('id' => 0, 'culture_color' => $this->counter ? 'BBBBBB' : 'FFFFFF',  'group_color' => $this->counter ? 'BBBBBB' : 'FFFFFF');
		foreach($this->result as $culture){
			$items = array();
			foreach($seeds as $seed){
				if($seed[':group:_id']==$culture['id']){ $items[] = $seed; }
			}

			foreach($items as $item) {
				if(in_array($item['_id'], $exclude)){ continue; }
				$res[$it] = array(
					'id'	   => 's'.$item['_id'],
					'title'    => $item['name'],
					'clear_title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':group:_id'] ? 'n'.$item[':group:_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $culture['group_color']
				);
                if($extras)$res[$it]['extras'] = array('bio_crop'=>$item['bio_crop'], 'bio_crop_units'=>$item['bio_crop_units']);
                $it++;
			}
		}

		return $res;
	}

	protected function get_groups($groups, $parent){
		foreach($groups as $group){
			if($group[':parent:_id']==$parent){
				$this->result[$this->counter] = array('id' => $group['_id'], 'color' => $group['color']);
				$this->counter++;
				$this->get_groups($groups, $group['_id']);
			}
		}
	}

	private function get_cultures($groups, $cultures){
		$groups[] = array('id' => 0, 'color' => $this->counter ? 'BBBBBB' : 'FFFFFF');
		$this->result = array();
		$this->counter = 0;
		foreach ($groups as $group) {
			foreach ($cultures as $culture) {
				if($culture['group']==$group['id']){
					$this->result[$this->counter] = array('id' => $culture['_id'], 'culture_color' => $culture['color'], 'group_color' => $group['color']);
					$this->counter++;
				}
			}
		}
	}

	public function get_seeds_by_cultures($cultures_ids, $ids_only = false){
		$seeds = Jelly::select('glossary_seed')->where('deleted', '=', false)->and_where('group', 'IN', $cultures_ids)->execute()->as_array();

		if($ids_only){
			for($i=0; $i<count($seeds); $i++){
				$seeds[$i] = $seeds[$i]['_id'];
			}
		}

		return $seeds;
	}

}

