<?php defined('SYSPATH') or die ('No direct script access.');

class Model_FarmPreset extends Jelly_Model
{

	const DENIED = 0; //запрещён
	const READ	  = 1; //просмотр
	const WRITE	  = 2; //редактирование

	const UNLOCK = 0; //разлочено
	const LOCK_W  = 1; //залочено редактирование
	const LOCK = 2;     //залочено всё

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('farm_preset')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'farm'       => Jelly::field('BelongsTo',array(
					'foreign'	=> 'farm',
					'column'	=> 'farm_id',
					'label'	=> 'Хозяйство',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'menu_item'	=> Jelly::field('String',array(
					'label'		=> 'Раздел',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				'menu_name'	=> Jelly::field('String',array(
					'label'		=> 'Раздел',
					'rules' => array(
						'not_empty' => NULL
					)
				)),

				'permission' => Jelly::field('Integer',array('label' => 'Уровень доступа')),
				'lock' => Jelly::field('Integer',array('label' => 'Запрещено изменений')),
				'set_by_admin' => Jelly::field('Boolean',array('label' => 'Установлено вышестоящим админом')),
				'permission_by_admin' => Jelly::field('Integer',array('label' => 'Уровень доступа установленный администратором')),
				'menu_order' => Jelly::field('Integer',array('label' => 'Порядок меню'))
		));
	}


	public function get_permissions($both, $lchecked, $rchecked){

		if(!count($lchecked)) $lchecked = array(-1);
		if(!count($rchecked)) $rchecked = array(-1);

		$data = Jelly::select('farmpreset')->with('farm')->where('farm', 'IN', $lchecked);
		if($both) $data = $data->where ('menu_item', 'IN', $rchecked);
		$data = $data->execute()->as_array();

		$result = array();

		foreach($data as $permission){
			$result[] = array(
				'id' => $permission['_id'],
				'farm_id' => $permission[':farm:_id'],
				'farm_name' => $permission[':farm:name'],
				'farm_color' => $permission[':farm:color'],
				'farm_level' => count(explode('/', $permission[':farm:path']))-2,
				'farm_is_group' => $permission[':farm:is_group'],
				'farm_admin' => $permission[':farm:admin'],
				'menu_item' => $permission['menu_item'],
				'menu_name' => $permission['menu_name'],
				'permission' => $permission['permission'],
				'lock' => $permission['lock'],
				'set_by_admin' => $permission['set_by_admin'],
				'permission_by_admin' => $permission['permission_by_admin']
			);
		}

		return $result;
	}


	public function group_by($permissions, $edit){
		$blocks = array(); $res = array();
		$user = Auth::instance()->get_user();

		//сортируем сначала по меню, потом по хозяйствам
		$permissions = $this->sort_permissions($permissions, 'menu');
		$permissions = $this->sort_permissions($permissions, 'farm');
		
//		if($edit){
//			$dictionary = array(); // общий список пунктов меню для показа (только в редактировании)
//			foreach($permissions as $permission) {
//				if(array_search($permission['menu_item'], $dictionary)==-1 && $permission['permission']>Model_FarmPreset::DENIED) $dictionary[] = $permission['menu_item'];
//			}
//		}

		//делим на блоки по каждому хозяйству (группируем)
		foreach($permissions as $permission) {
			//если ещё не было группы по текущему хозяйству то создаём её (в ней указываем ид фермы чтоб если окажется что в группе нет ни одной записи, можно было идентифицировать эту группу и сделать для неё хэдер)
			if(!array_key_exists($permission['farm_id'], $blocks)) $blocks[$permission['farm_id']] = array(
					'farm_id'=>($permission['farm_is_group'] ? 'g' : 'n').$permission['farm_id'],
					'items' => array()
			);

			//если режим просмотра, то не показываем записи в которых закрыт доступ
			if(!$edit && $permission['permission']==Model_FarmPreset::DENIED) continue;
			//if($edit  && array_search($permission['menu_item'], $dictionary)==-1) continue;

			$check_parent_lock    = ($permission['farm_admin']==$user->id() && $permission['set_by_admin']); //проверять ли установку админа

			$lock_read_by_admin = ($check_parent_lock && $permission['permission_by_admin']<Model_FarmPreset::READ); //залочен ли просмотр вышестоящим админом
			$lock_write_by_admin = ($check_parent_lock && $permission['permission_by_admin']<Model_FarmPreset::WRITE); //залочено ли редактирование вышестоящим админом
			$lock_read_by_pfarm  = ($permission['lock']==Model_FarmPreset::LOCK); //залочен ли просмотр вышестоящим хозяйством
			$lock_write_by_pfarm = ($permission['lock']>Model_FarmPreset::UNLOCK); //залочено ли редактирование вышестоящим хозяйством
            
            // http://jira.ardea.kiev.ua/browse/AGC-1494
            // Anton Derkach added a comment - 20/Feb/12 11:06 AM
// При входе в режим редактирования Доступа на всех разделах стоят галочки (в блоке Разделы) - соответственно, на всех разделах в блоке Доступ тоже должны быть галочки и включен режим редактирования этих разделов.
            if($edit ){
                $permission['permission'] = Model_FarmPreset::WRITE; // 2
            }

			$blocks[$permission['farm_id']]['items'][] = array(
				'id' => 'l'.$permission['id'],
				'farm_name' => $permission['farm_name'],
				'menu_name' => $permission['menu_name'],
				'left_id' => ($permission['farm_is_group'] ? 'g' : 'n').$permission['farm_id'],
				'right_id' => 'g'.$permission['menu_item'],
				'cells' => array(
					array('type' => 'text', 'value' => $this->prepare_value($permission, $permission['menu_name'], $lock_read_by_pfarm, $lock_read_by_admin)),
					array('type' => 'checkbox', 'value' => $permission['permission']==Model_FarmPreset::WRITE, 'contentCls' => 'toggle-control'.($lock_write_by_admin ? ' p-dsbld':'')
																																															  .($lock_write_by_pfarm ? ' toggle-disable':'')
																																															  .($permission['permission']<Model_FarmPreset::READ ? ' toggle-disable-2':''))
				)
			);
		}

		foreach($blocks as &$block) {
			$title = Jelly::factory('farm')->getBreadCrumbs(substr($block['farm_id'], 1));
			$title = implode(' > ', $title);
			array_splice($block['items'], 0, 0, array(array(
				'id' => 't'.$block['farm_id'],
				'is_title' => true,
				'cls' => 'title-row',
				'cells' => array(
					array('colspan' => 2, 'type' => 'text', 'value' => $title)
				)
			)));
		}

		foreach($blocks as &$block) {
			$res = array_merge($res, $block['items']);
		}

		return $res;
	}


	public function prepare_value($permission, $title, $lock_read_by_pfarm, $lock_read_by_admin){
		$level = $this->get_menu_level($permission['menu_item']);
		$color = $this->get_menu_color($permission['menu_item']);
		$is_group = true;

		$checked = $permission['permission']>=Model_FarmPreset::READ;
		$check_cls =  'x-check'.($lock_read_by_pfarm ? ' x-check-dsbld':'').($lock_read_by_admin ? ' p-dsbld':'');

		$checkbox = '<input class="'.$check_cls.'" type="checkbox"'.($checked ? ' checked="checked"':'').'>';
		$box = '<div style="display:inline-block; width:28px; height:28px; background-color:#'.$color.'; -webkit-border-radius:'.($is_group ? 5 : 14).'px; margin-right:8px; border:#aaa 1px solid;">'.$checkbox.'</div>';
		$str = '<div style="line-height:27px;"><div style="display:inline-block; width:'.($level*20).'px;"></div>'.$box.$title.'</div>';

		return $str;
	}


	public function sort_permissions($permissions, $sort_by){
		$user = Auth::instance()->get_user();
		$result = array();
		if($sort_by=='menu'){
			foreach($this->menu as $menu){
				foreach($permissions as $permission){
					if($permission['menu_item']==$menu['id'])$result[]=$permission;
				}
			}
		}
		if($sort_by=='farm'){
			$farms = Jelly::factory('farm')->get_full_tree($user->license->id());
			foreach($farms as $farm){
				foreach($permissions as $permission){
					if($permission['farm_id']==substr($farm['id'], 1))$result[]=$permission;
				}
			}
		}
		return $result;
	}


	public $menu = array(
		array('color' => '612a89', 'level' => 0, 'id'=>'departmentsInterface', 'title'=>'Хозяйства', 'menu_order'=>1),
		array('color' => '13b329', 'level' => 1, 'id'=>'departmentsInterfaceShare', 'title'=>'Паи', 'menu_order'=>2),
		array('color' => '7232a3', 'level' => 0, 'id'=>'glossaryInterface', 'title'=>'Глоссарий', 'menu_order'=>3),
		array('color' => '8d41c5', 'level' => 1, 'id'=>'glossaryInterfaceCultures', 'title'=>'Культуры', 'menu_order'=>4),
		array('color' => 'a14ae1', 'level' => 2, 'id'=>'culturesInterfaceSeeds', 'title'=>'Семена', 'menu_order'=>5),
		array('color' => 'b051f9', 'level' => 2, 'id'=>'culturesInterfacePredecessor', 'title'=>'Предшественники', 'menu_order'=>6),
		array('color' => 'b785e5', 'level' => 3, 'id'=>'culturesInterfacePredecessorsAppointment', 'title'=>'Назначение предшественников', 'menu_order'=>7),
		array('color' => 'c992fb', 'level' => 2, 'id'=>'culturesInterfaceProduction', 'title'=>'Продукция', 'menu_order'=>8),
		array('color' => 'fd8ad5', 'level' => 1, 'id'=>'glossaryInterfaceMaterials', 'title'=>'Материалы', 'menu_order'=>9),
		array('color' => 'fe76f7', 'level' => 2, 'id'=>'materialsInterfaceSeeds', 'title'=>'Семена', 'menu_order'=>10),
		array('color' => '087317', 'level' => 2, 'id'=>'materialsInterfaceSZR', 'title'=>'СЗР', 'menu_order'=>11),
		array('color' => '0e8e1e', 'level' => 3, 'id'=>'materialsInterfaceSZRDV', 'title'=>'СЗР + ДВ', 'menu_order'=>12),
		array('color' => '13b329', 'level' => 3, 'id'=>'materialsInterfaceSZRTargets', 'title'=>'СЗР + Целевые объекты', 'menu_order'=>13),
		array('color' => '13b329', 'level' => 3, 'id'=>'materialsInterfaceSZRDeploymentType', 'title'=>'Способы внесения СЗР', 'menu_order'=>14),
		array('color' => '1ad433', 'level' => 2, 'id'=>'materialsInterfaceFertilizers', 'title'=>'Удобрения', 'menu_order'=>15),
		
		array('color' => '1ce738', 'level' => 3, 'id'=>'materialsInterfaceFertilizersDV', 'title'=>'Удобрения + ДВ', 'menu_order'=>16),
		array('color' => '1ce738', 'level' => 3, 'id'=>'materialsInterfaceFertilizersDeploymentType', 'title'=>'Способы внесения удобрений', 'menu_order'=>17),
		
		array('color' => '5c63c0', 'level' => 2, 'id'=>'materialsInterfaceGSM', 'title'=>'ГСМ', 'menu_order'=>18),
		array('color' => '6c76e2', 'level' => 1, 'id'=>'glossaryInterfaceTechnique', 'title'=>'Техника', 'menu_order'=>19),
		array('color' => '7581fa', 'level' => 2, 'id'=>'techniqueInterfacePodvSostav', 'title'=>'Подвижной состав', 'menu_order'=>20),
		array('color' => 'c91911', 'level' => 2, 'id'=>'techniqueInterfacePricSostav', 'title'=>'Прицепной состав', 'menu_order'=>21),
		array('color' => 'fb2217', 'level' => 1, 'id'=>'glossaryInterfacePersonal', 'title'=>'Персонал', 'menu_order'=>22),
		array('color' => 'fc9229', 'level' => 0, 'id'=>'handbookInterface', 'title'=>'Справочники', 'menu_order'=>23),
		array('color' => 'fdd381', 'level' => 1, 'id'=>'handbookInterfaceStore', 'title'=>'Складской', 'menu_order'=>24),
		
		array('color' => 'fdd381', 'level' => 1, 'id'=>'handbookInterfaceProduction', 'title'=>'Продукция', 'menu_order'=>25),
		
		array('color' => '612a89', 'level' => 1, 'id'=>'handbookInterfacePersonal', 'title'=>'Персонал', 'menu_order'=>26),
		array('color' => '7232a3', 'level' => 1, 'id'=>'handbookInterfaceTechnique', 'title'=>'Техника', 'menu_order'=>27),
		array('color' => '8d41c5', 'level' => 2, 'id'=>'handbookInterfaceTechniqueMobile', 'title'=>'Подвижной состав', 'menu_order'=>28),
		array('color' => 'a14ae1', 'level' => 2, 'id'=>'handbookInterfaceTechniqueTrailer', 'title'=>'Прицепной состав', 'menu_order'=>29),
		
		array('color' => 'b051f9', 'level' => 0, 'id'=>'planningInterface', 'title'=>'Планирование', 'menu_order'=>30),
		array('color' => 'b051f9', 'level' => 1, 'id'=>'planningInterfaceOperations', 'title'=>'Операции', 'menu_order'=>31),
		array('color' => 'b051f9', 'level' => 1, 'id'=>'planningInterfaceHandbookVersions', 'title'=>'Плановые справочники', 'menu_order'=>32),
		array('color' => 'b051f9', 'level' => 1, 'id'=>'planningInterfaceATK', 'title'=>'АТК', 'menu_order'=>33),
		array('color' => 'b051f9', 'level' => 1, 'id'=>'planningInterfacePlan', 'title'=>'План', 'menu_order'=>34),
		
		array('color' => 'b785e5', 'level' => 0, 'id'=>'workInterface', 'title'=>'Работа', 'menu_order'=>35),
		array('color' => 'c992fb', 'level' => 0, 'id'=>'reportsInterface', 'title'=>'Отчеты', 'menu_order'=>36),
		array('color' => 'fd8ad5', 'level' => 0, 'id'=>'settingsInterface', 'title'=>'Настройки', 'menu_order'=>37),
		array('color' => 'fe76f7', 'level' => 1, 'id'=>'settingsInterfaceLicense', 'title'=>'Лицензия', 'menu_order'=>38),
		array('color' => 'fe76e7', 'level' => 2, 'id'=>'settingsInterfaceLicenseUsers', 'title'=>'Пользователи', 'menu_order'=>39),
		array('color' => '087317', 'level' => 1, 'id'=>'settingsInterfaceFormats', 'title'=>'Форматы', 'menu_order'=>40),
		array('color' => '0e8e1e', 'level' => 1, 'id'=>'settingsInterfaceDepartments', 'title'=>'Хозяйства', 'menu_order'=>41),
		array('color' => '1ad433', 'level' => 1, 'id'=>'settingsInterfacePeriod', 'title'=>'Периоды', 'menu_order'=>42),
		array('color' => '1ce738', 'level' => 1, 'id'=>'settingsInterfaceCountries', 'title'=>'Страны', 'menu_order'=>43),
		array('color' => '41458a', 'level' => 1, 'id'=>'settingsInterfaceChemicalelements', 'title'=>'Химические элементы', 'menu_order'=>44),
		array('color' => '5c63c0', 'level' => 1, 'id'=>'settingsInterfaceProducers', 'title'=>'Производители', 'menu_order'=>45),
		array('color' => '6c76e2', 'level' => 1, 'id'=>'settingsInterfaceContragents', 'title'=>'Контрагенты', 'menu_order'=>46),
		array('color' => 'fe76d7', 'level' => 1, 'id'=>'settingsInterfacePermissions', 'title'=>'Доступ', 'menu_order'=>47),
		array('color' => '7581fa', 'level' => 0, 'id'=>'helpInterface', 'title'=>'Помощь', 'menu_order'=>48)
	);
	public function fix_farm_presets(){
		$farms = Jelly::select('farm')->where('deleted', '=', false)->execute()->as_array();

		foreach($farms as $farm){
			//if($farm['_id']<1060) continue;
			foreach($this->menu as $menu){
				$perm = Jelly::select('farmpreset')->where('farm', '=', $farm['_id'])->and_where('menu_item', '=', $menu['id'])->load();
				if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
					$perm = Jelly::factory('farmpreset');
					$perm->farm = $farm['_id'];
					$perm->menu_item = $menu['id'];
					$perm->menu_name = $menu['title'];
					$perm->menu_order = $menu['menu_order'];
					$perm->permission = Model_FarmPreset::WRITE;
					$perm->lock = Model_FarmPreset::UNLOCK;
					$perm->set_by_admin = false;
					$perm->permission_by_admin = Model_FarmPreset::DENIED;
					$perm->save();
				}
			}
		}

		return true;
	}

	public function insert_preset($farm_id){
		foreach($this->menu as $menu){
			$perm = Jelly::select('farmpreset')->where('farm', '=', $farm_id)->and_where('menu_item', '=', $menu['id'])->load();
			if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
				$perm = Jelly::factory('farmpreset');
				$perm->farm = $farm_id;
				$perm->menu_item = $menu['id'];
				$perm->menu_name = $menu['title'];
				$perm->menu_order = $menu['menu_order'];
				$perm->permission = Model_FarmPreset::WRITE;
				$perm->save();
			}
		}
	}

	public function get_menu_tree(){
		$data = array();
		foreach($this->menu as $menu){
			$parent = $this->get_parent($menu);
			$data[] = array(
				'id'	   => 'g'.$menu['id'],
				'title'    => $menu['title'],
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $menu['level'],
				'children_g' => $this->get_children_ids($menu),
				'children_n' => array(),
				'parent'   => $parent['id'] ? 'g'.$parent['id'] : '',
				'color'    => $menu['color'],
				'parent_color' => $parent['color']
			);
		}

		return $data;
	}

	public function get_children_ids($parent_menu){
		$ids = array(); $start = false;

		foreach($this->menu as $menu){
			if($menu['id']==$parent_menu['id']){ $start = true; continue; }
			if($start && $menu['level']<=$parent_menu['level']) { break; }

			if($start && $menu['level']==($parent_menu['level']+1)){ $ids[] = 'g'.$menu['id']; }
		}

		return $ids;
	}

	public function get_parent($child_menu){
		$parent = array('id'=>'', 'color'=>'ffffff', 'level'=>-2); $prev_menu = array('id'=>'', 'color'=>'ffffff', 'level'=>-2);

		foreach($this->menu as $menu){
			if($menu['id']==$child_menu['id'] && ($prev_menu['level']+1)==$child_menu['level']){ $parent = $prev_menu; break; }
			if(($menu['level']+1)==$child_menu['level'])$prev_menu = $menu;
		}

		return $parent;
	}

	public function get_menu_level($menu_id){
		$level = 0;
		foreach($this->menu as $menu){
			if($menu['id']==$menu_id) $level = $menu['level'];
		}
		return $level;
	}

	public function get_menu_color($menu_id){
		$color = 'FFFFFF';
		foreach($this->menu as $menu){
			if($menu['id']==$menu_id) $color = $menu['color'];
		}
		return $color;
	}
	
	public function get_menu($menu_id){
		foreach($this->menu as $menu){
			if($menu['id']==$menu_id) return $menu;
		}
	}

	public function getBreadCrumbs($menu_id){
		$menu = $this->get_menu($menu_id);
		$parent = $this->get_parent($menu);

		if($parent['id']) return array_merge($this->getBreadCrumbs($parent['id']), array($menu['title']));
		else return array($menu['title']);
	}


	public function get_menu_subtree($menu_id){
		$ids = array(); $start = false;
		$parent_menu = $this->get_menu($menu_id);

		foreach($this->menu as $menu){
			if($menu['id']==$parent_menu['id']){ $start = true; continue; }
			if($start && $menu['level']<=$parent_menu['level']) { break; }

			if($start){ $ids[] = $menu['id']; }
		}

		return $ids;
	}

	public function get_checked_menus($model, $field, $l_ids){
		if(!count($l_ids)) $l_ids = array(-1);
		$permissions = Jelly::select($model)->where($field, 'IN', $l_ids)->execute()->as_array();
		$dictionary = array();
		foreach($permissions as $permission) {
			if(array_search($permission['menu_item'], $dictionary)===false && $permission['permission']>Model_FarmPreset::DENIED) $dictionary[] = $permission['menu_item'];
		}
		return $dictionary;
	}

}

