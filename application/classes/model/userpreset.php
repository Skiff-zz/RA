<?php defined('SYSPATH') or die ('No direct script access.');

class Model_UserPreset extends Jelly_Model
{

	const DENIED = 0; //запрещён
	const READ	  = 1; //просмотр
	const WRITE	  = 2; //редактирование

	const UNLOCK = 0; //разлочено
	const LOCK_W  = 1; //залочено редактирование
	const LOCK = 2;     //залочено всё

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('user_presets')
			->fields(array(
				'_id' 			=> new Field_Primary,
				
				'user'		    => Jelly::field('BelongsTo',array(
							'foreign'	=> 'user',
							'column'	=> 'user_id',
							'label'		=> 'Пользователь',
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
				'menu_order' => Jelly::field('Integer',array('label' => 'Порядок меню'))
		));
	}
	
	public function get_checked_menus($model, $field, $l_ids){
		
		if(!count($l_ids)) $l_ids = array(-1);
		
		$real_ids = array();
		
		for($i = 0; $i < $k = count($l_ids); $i++)
		{
			if(is_numeric($l_ids[$i]))
			{
				$user = Jelly::select('user', (int)$l_ids[$i]);
			}
			else if(strpos($l_ids[$i], 'g_personal_user_') !== false)
			{
				$real_id = (int)str_replace('g_personal_user_', '', $l_ids[$i]);
				
				$personal = Jelly::select('Client_Handbook_Personal')->with('user')->where('deleted', '=', 0)->where('_id', '=', $real_id)->load();
			
				if(!($personal instanceof Jelly_Model) or !$personal->loaded())
				{
					continue;
				}
				
				$user = $personal->user;	
				
			}
			else if(strpos($l_ids[$i], 'g_user_') !== false)
			{
				$real_id = (int)str_replace('g_user_', '', $l_ids[$i]);
				$user = Jelly::select('user', (int)$real_id);
			}
			else 
			{
				$real_id = (int)str_replace('g_license_', '', $l_ids[$i]);
				
				$license = Jelly::select('license', $real_id);
				
				if($license->id() != Auth::instance()->get_user()->license->id())
					throw new Kohan_Exception('Same license origin policy');
				
				$user = $license->user;
			}
			
			if(!($user instanceof Jelly_Model) or !$user->loaded() or $user->deleted)
			{
				continue;
			}
			
			$real_ids[] = $user->id();
		}
		
		if(!count($real_ids))
			return array();
		
		$permissions = Jelly::select($model)->where($field, 'IN', $real_ids)->execute()->as_array();
		
		$dictionary = array();
		
		foreach($permissions as $permission) {
			if(array_search($permission['menu_item'], $dictionary)===false && $permission['permission']>Model_UserPreset::DENIED) $dictionary[] = $permission['menu_item'];
		}
		return $dictionary;
	}

	public function get_permissions($users, $menus){
		//if(!count($users) || !count($menus)) return array();
		$data = Model_User::get_special_permissions($users, $menus);
		
		$result = array();
		foreach($data as $user_id => $permissions){
			foreach($permissions as $permission){
				$fullname = trim($permission[':user:first_name'].' '.$permission[':user:middle_name'].' '.$permission[':user:last_name']);
				$fullname = $fullname ? $fullname : ($permission[':user:username'] ? $permission[':user:username'] : $permission[':user:email']);

				$result[$user_id][] = array(
					'id' => $permission['_id'],
					'user_id' => $permission[':user:_id'],
					'user_name' => $fullname,
					'user_color' => $permission[':user:color'],
					'menu_item' => $permission['menu_item'],
					'menu_name' => $permission['menu_name'],
					'permission' => $permission['permission'],
					'lock' => $permission['lock']
				);
			}
		}
		
		return $result;
	}


	public function group_by($permissions, $edit){
		$blocks = array(); $res = array();
		$user = Auth::instance()->get_user();

		foreach($permissions as $user_id => $permissions) {
			if(!array_key_exists($user_id, $blocks)) $blocks[$user_id] = array(
					'user_id' => $user_id,
					'user_name' => $permissions[0]['user_name'],
					'items' => array()
			);

			foreach($permissions as $permission){

				//если режим просмотра, то не показываем записи в которых закрыт доступ
				if(!$edit && $permission['permission']==Model_UserPreset::DENIED)
				{
					continue;
				}	

				$lock_read = ($permission['lock']==Model_UserPreset::LOCK);
				$lock_write = ($permission['lock']>Model_UserPreset::UNLOCK);
                
                 // http://jira.ardea.kiev.ua/browse/AGC-1494
            // Anton Derkach added a comment - 20/Feb/12 11:06 AM
// При входе в режим редактирования Доступа на всех разделах стоят галочки (в блоке Разделы) - соответственно, на всех разделах в блоке Доступ тоже должны быть галочки и включен режим редактирования этих разделов.
            if($edit ){
                $permission['permission'] = Model_UserPreset::WRITE; // 2
            }

				$blocks[$user_id]['items'][] = array(
					'id' => 'l'.$permission['id'],
					'user_name' => $permission['user_name'],
					'menu_name' => $permission['menu_name'],
					'left_id' => 'g'.$permission['user_id'],
					'right_id' => 'g'.$permission['menu_item'],
					'cells' => array(
						array('type' => 'text', 'value' => $this->prepare_value($permission, $permission['menu_name'], $lock_read)),
						array('type' => 'checkbox', 'value' => $permission['permission']==Model_UserPreset::WRITE, 'contentCls' => 'toggle-control '.($lock_write ? ' toggle-disable':'')
																																																  .($permission['permission']<Model_UserPreset::READ ? ' toggle-disable-2':''))
					)
				);
			}
		}

		foreach($blocks as &$block) {
			$title = $block['user_name'];
			array_splice($block['items'], 0, 0, array(array(
				'id' => 'tg'.$block['user_id'],
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

		$checked = $permission['permission']>=Model_UserPreset::READ;
		$check_cls = 'x-check'.($lock_read ? ' x-check-dsbld':'');

		$checkbox = '<input class="'.$check_cls.'" type="checkbox"'.($checked ? ' checked="checked"':'').'>';
		$box = '<div style="display:inline-block; width:28px; height:28px; background-color:#'.$color.'; -webkit-border-radius:'.($is_group ? 5 : 14).'px; margin-right:8px; border:#aaa 1px solid;">'.$checkbox.'</div>';
		$str = '<div style="line-height:27px;"><div style="display:inline-block; width:'.($level*20).'px;"></div>'.$box.$title.'</div>';

		return $str;
	}



	public function fix_user_presets(){
		$menus = Jelly::factory('farmpreset')->menu;
		//print_r($menus); exit;
		$users = Jelly::select('user')->where('deleted', '=', false)->order_by('_id')->execute()->as_array();
		foreach($users as $user){
			foreach($menus as $menu){
				$perm = Jelly::select('userpreset')->where('user', '=', $user['_id'])->and_where('menu_item', '=', $menu['id'])->load();
				if(!($perm instanceof Jelly_Model) or !$perm->loaded()){
					$perm = Jelly::factory('userpreset');
					$perm->user = $user['_id'];
					$perm->menu_item = $menu['id'];
					$perm->menu_name = $menu['title'];
					$perm->menu_order = $menu['menu_order'];
					$perm->permission = Model_PersonalPreset::WRITE;
					$perm->lock = Model_PersonalPreset::UNLOCK;
					$perm->save();
				}
			}
		}
		return true;
	}
	
	
	
	public function fix_presets_menu_order(){
		$menus = Jelly::factory('farmpreset')->menu;
		//print_r($menus); exit;
		$permissions = Jelly::select('userpreset')->execute();
		foreach($permissions as $permission){
			$menu = Jelly::factory('farmpreset')->get_menu($permission->menu_item);
			$permission->menu_order = $menu['menu_order'];
			$permission->save();
		}
		$permissions2 = Jelly::select('farmpreset')->execute();
		foreach($permissions2 as $permission2){
			$menu = Jelly::factory('farmpreset')->get_menu($permission2->menu_item);
			$permission2->menu_order = $menu['menu_order'];
			$permission2->save();
		}
		$permissions3 = Jelly::select('farmpreset')->execute();
		foreach($permissions3 as $permission3){
			$menu = Jelly::factory('farmpreset')->get_menu($permission3->menu_item);
			$permission3->menu_order = $menu['menu_order'];
			$permission3->save();
		}
		return true;
	}

}

