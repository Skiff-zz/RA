<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Fertilizer_DeploymentType extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name = 'glossary_fertilizer_deploymenttype', $group_model = NULL)
	{
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)->fields(array());
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
		$groups = array();
		$names = Jelly::select($model_name)->where('deleted', '=', false)->order_by('name', 'asc')->execute()->as_array();
		$this->get_groups($groups, 0);

		$this->result[] = 0;

		foreach($names as $item) {
			if(in_array($item['_id'], $exclude)){ continue; }
			$res[] = array(
				'id'	   => 'n'.$item['_id'],
				'title'    => $item['name'],
				'is_group' => false,
				'is_group_realy' => false,
				'level'	   => 0,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => '',
				'color'    => $item['color'],
				'parent_color' => $item['color']
			);
		}

		return $res;
	}
}
