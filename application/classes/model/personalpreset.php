<?php defined('SYSPATH') or die ('No direct script access.');

class Model_PersonalPreset extends Jelly_Model
{

	const DENIED = 0; //запрещён
	const READ	  = 1; //просмотр
	const WRITE	  = 2; //редактирование

	const UNLOCK = 0; //разлочено
	const LOCK_W  = 1; //залочено редактирование
	const LOCK = 2;     //залочено всё

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('personal_preset')
			->fields(array(
				'_id' 			=> new Field_Primary,
				'personal'       => Jelly::field('Integer',array(
					'label'	=> 'Должность',
					'rules' => array(
						'not_empty' => NULL
					)
				)),
				'personal_is_group'       => Jelly::field('Boolean',array('label'	=> 'Группа или нет')),

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
				'menu_order' => Jelly::field('Integer',array('label' => 'Порядок меню'))
		));
	}


	public function get_permissions($both, $lchecked, $rchecked){

		if(!count($rchecked)) $rchecked = array(-1);

		$lchecked_g = array();
		$lchecked_n = array();
		
		/*
		foreach($lchecked as $ch) {
			if(substr($ch, 0, 1)=='g')$lchecked_g[] = substr($ch, 1);
			else							$lchecked_n[] = substr($ch, 1);
		}*/

		if(!count($lchecked_g)) $lchecked_g = array(-1);
		if(!count($lchecked)) 
			$lchecked_n = array(-1);
		else
			$lchecked_n = $lchecked;

		$data = Jelly::select('personalpreset')->where('personal', 'IN', $lchecked_n)->where('personal_is_group', '=', false);
		if($both) $data = $data->where('menu_item', 'IN', $rchecked);
		$data = $data->execute()->as_array();

		$result = array();

		foreach($data as $permission){
			$personal = Jelly::select('glossary_personal'.($permission['personal_is_group'] ? 'group' : ''))->load((int)$permission['personal']);
			$result[] = array(
				'id' => $permission['_id'],
				'personal_id' => ($permission['personal_is_group'] ? 'g':'n').$permission['personal'],
				'personal_name' => $personal->name,
				'personal_color' => $personal->color,
				'personal_level' => 0,
				'personal_is_group' => $permission['personal_is_group'],
				'menu_item' => $permission['menu_item'],
				'menu_name' => $permission['menu_name'],
				'permission' => $permission['permission'],
				'lock' => $permission['lock']
			);
		}

		return $result;
	}

	public function group_by($permissions, $edit){
		$blocks = array(); $res = array();

		$permissions = $this->sort_permissions($permissions, 'menu');
		$permissions = $this->sort_permissions($permissions, 'personal');

		foreach($permissions as $permission) {
			if(!array_key_exists($permission['personal_id'], $blocks)) $blocks[$permission['personal_id']] = array(
					'personal_id' => $permission['personal_id'],
					'personal_name' => $permission['personal_name'],
					'items' => array()
			);

			//если режим просмотра, то не показываем записи в которых закрыт доступ
			if(!$edit && $permission['permission']==Model_FarmPreset::DENIED) continue;

			$lock_read_by_ppersonal  = ($permission['lock']==Model_PersonalPreset::LOCK); //залочен ли просмотр вышестоящей группой персонала
			$lock_write_by_ppersonal = ($permission['lock']>Model_PersonalPreset::UNLOCK); //залочено ли редактирование вышестоящей группой персонала
            
             // http://jira.ardea.kiev.ua/browse/AGC-1494
            // Anton Derkach added a comment - 20/Feb/12 11:06 AM
// При входе в режим редактирования Доступа на всех разделах стоят галочки (в блоке Разделы) - соответственно, на всех разделах в блоке Доступ тоже должны быть галочки и включен режим редактирования этих разделов.
            if($edit ){
                $permission['permission'] = Model_PersonalPreset::WRITE; // 2
            }

			$blocks[$permission['personal_id']]['items'][] = array(
				'id' => 'l'.$permission['id'],
				'personal_name' => $permission['personal_name'],
				'menu_name' => $permission['menu_name'],
				'left_id' => $permission['personal_id'],
				'right_id' => 'g'.$permission['menu_item'],
				'cells' => array(
					array('type' => 'text', 'value' => $this->prepare_value($permission, $permission['menu_name'], $lock_read_by_ppersonal)),
					array('type' => 'checkbox', 'value' => $permission['permission']==Model_PersonalPreset::WRITE, 'contentCls' => 'toggle-control '.($lock_write_by_ppersonal ? ' toggle-disable':'')
																																																   .($permission['permission']<Model_PersonalPreset::READ ? ' toggle-disable-2':''))
				)
			);
		}

		foreach($blocks as &$block) {
			$title = $block['personal_name'];
			array_splice($block['items'], 0, 0, array(array(
				'id' => 't'.$block['personal_id'],
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


	public function prepare_value($permission, $title, $lock_read){
		$level = Jelly::factory('farmpreset')->get_menu_level($permission['menu_item']);
		$color = Jelly::factory('farmpreset')->get_menu_color($permission['menu_item']);
		$is_group = true;

		$checked = $permission['permission']>=Model_PersonalPreset::READ;
		$check_cls = 'x-check'.($lock_read ? ' x-check-dsbld':'');

		$checkbox = '<input class="'.$check_cls.'" type="checkbox"'.($checked ? ' checked="checked"':'').'>';
		$box = '<div style="display:inline-block; width:28px; height:28px; background-color:#'.$color.'; -webkit-border-radius:'.($is_group ? 5 : 14).'px; margin-right:8px; border:#aaa 1px solid;">'.$checkbox.'</div>';
		$str = '<div style="line-height:27px;"><div style="display:inline-block; width:'.($level*20).'px;"></div>'.$box.$title.'</div>';

		return $str;
	}


	public function sort_permissions($permissions, $sort_by){
		$user = Auth::instance()->get_user();
		$menus = Jelly::factory('farmpreset')->menu;
		$result = array();
		if($sort_by=='menu'){
			foreach($menus as $menu){
				foreach($permissions as $permission){
					if($permission['menu_item']==$menu['id'])$result[]=$permission;
				}
			}
		}
		if($sort_by=='personal'){
			$personals = Jelly::factory('glossary_personalgroup')->get_tree($user->license->id(), true);
			foreach($personals as $personal){
				foreach($permissions as $permission){
					if($permission['personal_id']==$personal['id'])$result[]=$permission;
				}
			}
		}
		return $result;
	}


	public function fix_personal_presets(){
		$menus = Jelly::factory('farmpreset')->menu;
		//print_r($menus); exit;
		$personals_g = Jelly::select('glossary_personalgroup')->where('deleted', '=', false)->execute()->as_array();
		foreach($personals_g as $personal){
			foreach($menus as $menu){
				$perm = Jelly::select('personalpreset')->where('personal', '=', $personal['_id'])->and_where('menu_item', '=', $menu['id'])->and_where('personal_is_group', '=', true)->load();
				if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
					$perm = Jelly::factory('personalpreset');
					$perm->personal = $personal['_id'];
					$perm->personal_is_group = true;
					$perm->menu_item = $menu['id'];
					$perm->menu_name = $menu['title'];
					$perm->permission = Model_PersonalPreset::WRITE;
					$perm->lock = Model_PersonalPreset::UNLOCK;
					$perm->save();
				}
			}
		}

		$personals_n = Jelly::select('glossary_personal')->where('deleted', '=', false)->execute()->as_array();
		foreach($personals_n as $personal){
			foreach($menus as $menu){
				$perm = Jelly::select('personalpreset')->where('personal', '=', $personal['_id'])->and_where('menu_item', '=', $menu['id'])->and_where('personal_is_group', '=', false)->load();
				if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
					$perm = Jelly::factory('personalpreset');
					$perm->personal = $personal['_id'];
					$perm->personal_is_group = false;
					$perm->menu_item = $menu['id'];
					$perm->menu_name = $menu['title'];
					$perm->permission = Model_PersonalPreset::WRITE;
					$perm->lock = Model_PersonalPreset::UNLOCK;
					$perm->save();
				}
			}
		}

		return true;
	}

	public function insert_preset($personal_id, $is_group){
		$menus = Jelly::factory('farmpreset')->menu;
		foreach($menus as $menu){
			$perm = Jelly::select('personalpreset')->where('personal', '=', $personal_id)->and_where('menu_item', '=', $menu['id'])->and_where('personal_is_group', '=', $is_group)->load();
			if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
				$perm = Jelly::factory('personalpreset');
				$perm->personal = $personal_id;
				$perm->personal_is_group = $is_group;
				$perm->menu_item = $menu['id'];
				$perm->menu_name = $menu['title'];
				$perm->permission = Model_PersonalPreset::WRITE;
				$perm->lock = Model_PersonalPreset::UNLOCK;
				$perm->save();
			}
		}
	}


	public function get_checked_menus($l_ids){

		$l_ids_g = array();
		$l_ids_n = array();
		foreach($l_ids as $ch) {
			if(substr($ch, 0, 1)=='g')$l_ids_g[] = substr($ch, 1);
			else							$l_ids_n[] = substr($ch, 1);
		}

		if(!count($l_ids_g)) $l_ids_g = array(-1);
		if(!count($l_ids_n)) $l_ids_n = array(-1);

		$permissions = Jelly::select('personalpreset')->where_open()
																	  ->where_open()->where('personal', 'IN', $l_ids_g)->and_where('personal_is_group', '=', true)->where_close()
																	  ->or_where_open()->where('personal', 'IN', $l_ids_n)->and_where('personal_is_group', '=', false)->or_where_close()
																	  ->where_close()->execute()->as_array();
		$dictionary = array();
		foreach($permissions as $permission) {
			if(array_search($permission['menu_item'], $dictionary)===false && $permission['permission']>Model_PersonalPreset::DENIED) $dictionary[] = $permission['menu_item'];
		}
		return $dictionary;
	}

}

