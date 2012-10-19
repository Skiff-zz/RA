<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Client_HandbookVersionName extends Model_Glossary_Abstract {

	public static function initialize(Jelly_Meta $meta, $table_name = 'client_handbookversionname', $group_model = NULL) {
		parent::initialize($meta, $table_name, $group_model);

		$meta->table($table_name)->fields(array(
			'datetime' => Jelly::field('String', array('label' => 'Дата и время создания', 'type' => 'hiddenfield')),
			'update_datetime' => Jelly::field('String', array('label' => 'Дата и время последнего обновления', 'type' => 'hiddenfield')),
			'outdated' => Jelly::field('Integer', array('label' => '"Свежесть" версии', 'type' => 'hiddenfield')),
			'license' => Jelly::field('BelongsTo', array(
				'foreign' => 'license',
				'column' => 'license_id',
				'label' => 'Лицензия',
				'rules' => array(
					'not_empty' => NULL
				)
			)),
			'farm' => Jelly::field('BelongsTo', array(
				'foreign' => 'farm',
				'column' => 'farm',
				'label' => 'Хозяйство',
				'rules' => array(
					'not_empty' => NULL
				)
			)),
			'period' => Jelly::field('BelongsTo', array(
				'foreign' => 'client_periodgroup',
				'column' => 'period_id',
				'label' => 'Период',
				'rules' => array(
					'not_empty' => NULL
				)
			))
		));
	}

	protected $result = array();
	protected $counter = 0;

	public function get_tree($license_id, $group_field = 'group', $exclude = array(), $extras = true, $truncate = true) {
		$farms = Jelly::factory('farm')->get_session_farms();
		if (!count($farms))
			$farms = array(-1);

		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);

		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name = $this->meta()->model();
		$t = $this->meta()->fields($group_field);
		$group_model_name = $t->foreign['model'];
		$groups = array();
		$names = Jelly::select($model_name)->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id)->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				order_by('name', 'asc')->
				execute()->
				as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;

		foreach ($names as $item) {
			if (in_array($item['_id'], $exclude)) {
				continue;
			}
			$date_title = date('d.m.Y H:i', $item['datetime']);
			$item['datetime'] = date('d.m.Y', $item['datetime']) . '<br />' . date('H:i', $item['datetime']);
			$res[] = array(
				'id' => 'n' . $item['_id'],
				'clear_title' => $item['name'],
				'outdated' => $item['outdated'],
				'date_title' => $date_title,
				'title' => '<div style="text-overflow:ellipsis; white-space: nowrap; width:100%; overflow:hidden; '.(((int)$item['outdated']>0)?'color:gray;':'').'">'.$item['name'].'</div></div>  ' .
								'<div style="color: #666666; display:-webkit-box; -webkit-box-orient: horizontal; height: 28px; padding-top:3px; padding-right:4px;' .
								'font-size:12px; line-height:14px;">' . $item['datetime'] . '</div>' .
								($extras ? '<div class="link-arrow" onclick="Ext.getCmp(\'planningInterfaceHandbookVersions\').showTable(' . $item['_id'] . ');"></div>' : '') . '<div>',
				'is_group' => false,
				'is_group_realy' => false,
				'level' => 0,
				'children_g' => array(),
				'children_n' => array(),
				'parent' => '',
				'color' => $item['color'],
				'parent_color' => $item['color']
			);
		}

		return $res;
	}

}
