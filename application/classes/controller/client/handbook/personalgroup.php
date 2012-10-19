<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Handbook_PersonalGroup extends Controller_Glossary_AbstractGroup
{
	protected $model_name = 'client_handbook_personal';
	protected $model_group_name = 'client_handbook_personalgroup';

	public function action_read($id = null){
		//$is_position = Arr::get($_POST,'is_position',0);
		if($is_position = substr($id, 0, 1)=='n'){
			$id = substr($id, 1);
		}
		
		$record = Jelly::select('glossary_personalgroup', (int)$id);
		
		if($record->loaded() && !$is_position)
		{
			$this->request->response = Request::factory('/glossary/personalgroup/read/'.$id)->execute();
			return;
		}
		
		$record = Jelly::select('glossary_personal', (int)$id);
		
		if($record->loaded())
		{
			$this->request->response = Request::factory('/glossary/personal/read/'.$id)->execute();
			return;
		}
		
		return $this->action_edit($id, true);
	}
	
	public function action_delete($id = null)
	{

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		
		// Так как дерево должностей - сводное, необходимо превратить его в реальное
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$tmp = array();
		
		$group_ids = array();
		
		for($i=0; $i<count($del_ids); $i++)
		{
			if(mb_substr($del_ids[$i], 0, 1) == 'g' && mb_substr($del_ids[$i], 0, 2) != 'gn')
			{
				
				if($del_ids[$i] == 'g-2')
				{
					$tmp[] = $del_ids[$i];
				}
				
				$groups = Jelly::select('client_handbook_personalgroup')
						  ->where('farm', 'IN', $farms)
						  ->where('period', 'IN', $periods)
						  ->where('license', '=', Auth::instance()->get_user()->license->id() )
						  ->where('id_in_glossary', '=', mb_substr($del_ids[$i], 1))
						  ->execute();
				
				foreach($groups as $group)
				{
					$tmp[] = 'g'.$group->id();
					$group_ids[] =  $group->id();
					
					$subs = Jelly::select('client_handbook_personalgroup')
							->where('path', 'LIKE', '%/'.$group->id().'/%')
							->execute();
							
							foreach($subs as $sg)
							{
								$group_ids[] = $sg->id();
								$tmp[] = 'g'.$sg->id();
							}
				}
				
				
			}
			else
			{
				$tmp[] = $del_ids[$i];
			}
		}
		
		$del_ids = $tmp;

		if(count($group_ids))
		{
			Jelly::update('client_handbook_personal')
			->set(array('deleted' => 1))
			->where('group', 'IN', $group_ids)
			->execute();
			
			foreach($group_ids as $g_id)
			{
				$subs = Jelly::select('client_handbook_personalgroup')
				->where('path', 'LIKE', '%/'.$g_id.'/%')
				 ->where('farm', 'IN', $farms)
				  ->where('period', 'IN', $periods)
				  ->where('license', '=', Auth::instance()->get_user()->license->id() )
				->execute();
				
				foreach($subs as $sg)
				{
					Jelly::update('client_handbook_personal')
					->set(array('deleted' => 1))
					->where('group', '=', $sg->id())
					 ->where('farm', 'IN', $farms)
					  ->where('period', 'IN', $periods)
					  ->where('license', '=', Auth::instance()->get_user()->license->id() )
					->execute();
				}
				
				Jelly::update('client_handbook_personalgroup')
				->set(array('deleted' => 1))
				 ->where('farm', 'IN', $farms)
				  ->where('period', 'IN', $periods)
				  ->where('license', '=', Auth::instance()->get_user()->license->id() )
				->where('path', 'LIKE', '%/'.$g_id.'/%')
				->execute();
			}
		}
		
		foreach($del_ids as $id)
		{
			if(mb_substr($id, 0, 1) == 'gn')
			{
				 $id = mb_substr($id, 1);
				 
				 $item = Jelly::select('client_handbook_personalgroup', (int)$id);
				 
				 if($item->loaded())
				 	$item->delete();
			}
			else	if($id == 'g-2')
			{
					Jelly::update('client_handbook_personal')
					->set(array('deleted' => 1))
					->where('group', 'IS', null)
					->where('farm', 'IN', $farms)
					->where('period', 'IN', $periods)
				  	->where('license', '=', Auth::instance()->get_user()->license->id() )
					->execute();
			}
		}
		
		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

	public function action_edit($id = null, $read = false, $parent_id = false){

		if($id && substr($id, 0, 1)=='n'){
			$id = substr($id, 1);
		}
		
        $model = null;

        if($id && $id!=-2){
            $model = Jelly::select('client_handbook_personalgroup')->with('parent')->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$view = Twig::factory('client/handbook/personal/read_personalgroup');

        if($id)
			$view->id = $id;

		if(!$read){
			$view->edit			 	= true;
			$view->parent_id = $parent_id!==false ? $parent_id: ($model ? $model->parent->id() : 0);
			$view->hasChildren = false;
		}

        if($model){
			$view->model 	 = $model->as_array();
        }else{
			$view->model	=	array();
		}

        if($model){
			$view->model['properties']  = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonalgroup_prop')->execute()->as_array();
            $this->action_getphoto($view, $model->id());
        }

		$view->fake_group = $id==-2;
		$this->inner_edit($view);

		$this->request->response = JSON::reply($view->render());
	}

	public function action_update(){

		$user = Auth::instance()->get_user();

		$values = array('name', 'color', 'parent');
        if($group_id = arr::get($_POST, '_id', NULL)){
			$model = Jelly::select($this->model_group_name, (int)$group_id);
		}else{
			$model = Jelly::factory($this->model_group_name);
		}

		$this->validate_data($_POST);

       	$model->set($_POST);

		$model->update_date = time();
		$_POST['parent'] = (int)Arr::get($_POST,'parent',0);
		$model->set(Arr::extract($_POST, $values));
		$model->name = trim($model->name);
		$model->license   = Auth::instance()->get_user()->license->id();
		$model->save();
		$group_id = $model->id();
        $this->action_savephoto($model->id());
		$this->inner_update($group_id);


		//если редактировали группу "без группы", то всех безхозных чаилдов цепляем к ней
		if(Arr::get($_POST,'fake_group',false)){
			$db = Database::instance();
			$db->query(DATABASE::UPDATE, 'UPDATE '.Jelly::meta($this->model_name)->table().' SET group_id = '.$group_id.' WHERE (group_id=0 OR group_id IS NULL) AND deleted = 0', true);
		}

		$this->request->response = JSON::success(array('script'	   => "Группа сохранена успешно!",
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $group_id));
	}

	public function action_perm_tree()
	{
		$data = array();

		$myself = Auth::instance()->get_user();

		// Лицензиат
		$data[] = $this->tree_helper($myself->license);

		$license = $myself->license;

		$period = null;


		$periods = Session::instance()->get('periods');

		if(is_array($periods) and count($periods))
		{
			$period = $periods[0];
		}
		

		// Администраторы лицензиата

		/** Выбираем юзеров, привязанных к хозяйствам **/
		$farms = Jelly::select('farm')->with('admin')->where('farm.deleted', '=', 0)->where('license', '=', $license->id())->order_by('admin.first_name')->execute()->as_array();

		$admin_users = array();

		foreach($farms as $farm)
		{
			if((int)$farm['admin'] and (int)$farm[':admin:is_active'])
			{
				$admin_users[] = $farm['admin'];
			}
		}


			$personal = Jelly::select('Client_Handbook_Personal')->with('user')->where('user.is_active', '=', 1)->where('deleted', '=', 0)->where('license', '=', $license->id())->execute();

			$personal_array = array();

			foreach($personal as $p)
			{
				$personal_array[] = $p->user->id();
			}

			$merged = array_merge($admin_users, $personal_array);

			if(count($merged))
			{
				$license_users = Jelly::select('user')->where('license', '=', $license->id())->where('deleted', '=', 0)->where('is_active', '=', 1)->where('_id', 'NOT IN', $merged)->order_by('first_name', 'ASC')->execute();
			}
			else
			{
				$license_users = Jelly::select('user')->where('license', '=', $license->id())->where('deleted', '=', 0)->where('is_active', '=', 1)->order_by('first_name', 'ASC')->execute();
			}

		$license_array   		= array();
		$license_user_objects 	= array();

		foreach($license_users as $user)
		{
			$license_array[] = $user->id();
			$license_user_objects[] = $user;
		}

		$selected_farm_groups = Session::instance()->get('farm_groups');
		$selected_farms_only  = Session::instance()->get('farms');

		$selected_farms = is_array($selected_farm_groups) ? $selected_farm_groups : array();

		$selected_farms =  array_merge($selected_farms, is_array($selected_farms_only) ? $selected_farms_only : array());


		if(in_array($myself->id(), $license_array))
		{
			$data[] = array(
						 'id' => 'g_admin_'.$license->id(),
						 'title' => 'Администраторы',
						 'is_group' => 1,
						 'is_group_realy' => 1,
						 'children_g' => array(),
						 'children_n' => array(),
						 'parent' => 'g_license_'.$license->id(),
						 'level' => 1,
						 'color' => $license->color,
						 'parent_color' => $license->color
					 );

			foreach($license_user_objects as $obj)
			{
				$data[] =  $this->tree_helper($obj, 'g_admin_'.$license->id(), 2, $license->color);
			}


			if(!count($selected_farms))
			{
				$this->fix_children($data);
				$this->request->response = Json::arr($data, count($data));
				return;
			}

			$current_farm  = null;

			$farms = Jelly::select('farm')->with('parent')->where('farm.deleted', '=', 0)->where('license', '=', $license->id())->with('admin')->order_by('farm.path')->order_by('farm.is_group', 'ASC')->order_by('farm.name')->where(':primary_key', 'IN', $selected_farms)->execute();
		}
		else
		{
			/** Кто же к нам пришел, админ какого хозяйства? **/
			$current_farm = Jelly::select('farm')->where('farm.deleted', '=', 0)->where('license', '=', $license->id())->where('admin', '=', $myself->id())->load();

			if(!($current_farm instanceof Jelly_Model) or !$current_farm->loaded())
				throw new Kohana_Exception('Unknown user!');
			
			$farms = Jelly::select('farm')->with('parent')->where('farm.deleted', '=', 0)->with('admin')->where('license', '=', $license->id())->where('path', 'LIKE', '/'.$current_farm->id().'/')->order_by('farm.path', 'ASC')->order_by('farm.is_group', 'ASC')->order_by('farm.name');
			
			if(is_array($selected_farms) and count($selected_farms))
			{
				$farms = $farms->where(':primary_key', 'IN', $selected_farms);
			}

			$farms = $farms->execute();
		}

		$farm_objects = array();

		if($current_farm and array_key_exists($current_farm->id(), $selected_farms))
		{
			$farm_objects[(int)$current_farm->id()] = $current_farm;
		}

		foreach($farms as $f)
		{
			$farm_objects[(int)$f->id()] = $f;
		}

		$base_level = 1;

		foreach($farm_objects as $farm)
		{
			$level = 0;

			if(!$farm->parent->id() or !array_key_exists((int)$farm->parent->id(), $farm_objects))
			{
				$data[] = $this->tree_helper($farm, 'g_license_'.$license->id(), $base_level, $license->color, $farm->id());
			}
			else
			{
				$farm_level 	= 0;
				$parent_level 	= 0;

				$path = explode('/', $farm->path);

				for($i = 0; $i < $k = count($path); $i++)
				{
					if($path[$i] == '')
						continue;
					else
						$farm_level++;
				}

				$path = explode('/', $farm->parent->path);

				for($i = 0; $i < $k = count($path); $i++)
				{
					if($path[$i] == '')
						continue;
					else
						$parent_level++;
				}

				$level = $farm_level - $parent_level;

				$data[] = $this->tree_helper($farm, 'g_farm_'.$farm->parent->id(), $base_level + $level, $farm->parent->color, $farm->id());
			}

			$parent = $this->tree_get_element_by_id($data, 'g_farm_'.$farm->id());

			// Администратор группы хозяйств (если есть)
			// Велено все равно выводить пустую группу, даже если админов нэт
			$data[] = array(
						 'id' => 'g_farm_admin_'.$farm->id(),
						 'title' => 'Администраторы',
						 'is_group' => 1,
						 'is_group_realy' => 1,
						 'children_g' => array(),
						 'children_n' => array(),
						 'parent' => 'g_farm_'.$farm->id(),
						 'level' => $base_level + $level + 1,
						 'color' => $farm->color,
						 'parent_color' => $farm->parent->color
					 );


			if($farm->admin->id() and $farm->admin->is_active)
			{
				$data[] = $this->tree_helper($farm->admin, 'g_farm_admin_'.$farm->id(), $base_level + $level + 2, $farm->color, $farm->id() );
			}

			$farm_entry_level = $base_level + $level + 1;

			/**
			 *   Группы должностей и должности, если есть период **/

			if($period)
			{
				$groups = Jelly::select('Client_Handbook_PersonalGroup')
							->with('parent')
							->where('license', '=', $license->id())
							->where('period', '=', $period)
							->where('deleted', '=', 0)
							->order_by('path', 'ASC')
							->execute();
	
				$group_objects = array();
	
				foreach($groups as $group)
				{
					$group_objects[$group->id()] = $group;
				}
	
				$personal = Jelly::select('Client_Handbook_Personal')
							->with('group')
							->with('user')
							->where('license', '=', $license->id())
							->where('farm', '=', $farm->id())
							->where('deleted', '=', 0)
							->where('user.is_active', '=', 1)
							->order_by('name', 'ASC')
							->execute();
	
				$personal_objects = array();
	
				foreach($personal as $p)
				{
					$personal_objects[] = $p;
				}
	
				$current_group_level = $base_level + $level + 1;
	
				foreach($group_objects as $group)
				{
					$level = -1;
	
					if(!$group->parent->id() or !array_key_exists((int)$group->parent->id(), $group_objects))
					{
						$data[] = $this->tree_helper($group, 'g_farm_'.$farm->id(), $current_group_level, $farm->color, $farm->id());
					}
					else
					{
						$group_level 	= -1;
						$parent_level 	= -1;
	
						$path = explode('/', $group->path);
	
						for($i = 0; $i < $k = count($path); $i++)
						{
							if($path[$i] == '')
								continue;
							else
								$group_level++;
						}
						
						if($group_level == -1)
							$group_level = 0;
							
	
						$path = explode('/', $group->parent->path);
	
						for($i = 0; $i < $k = count($path); $i++)
						{
							if($path[$i] == '')
								continue;
							else
								$parent_level++;
						}
						
						if($parent_level == -1)
							$parent_level = 0;
	
						$level = $group_level + $parent_level;
	
						$data[] = $this->tree_helper($group, 'g_personal_group_'.$group->parent->id().'_g_farm_'.$farm->id(), $current_group_level + $level, $group->parent->color, $farm->id());
					}
	
	
					foreach($personal_objects as $personal)
					{
						if($personal->group->id() == $group->id())
						{
							$data[] = $this->tree_helper($personal, 'g_personal_group_'.$group->id().'_g_farm_'.$farm->id(), $current_group_level + $level + 1, $group->color, $farm->id());
						}
					}
				}
	
				/** Персонал без группы **/
				foreach($personal_objects as $personal)
				{
					if(!$personal->group->id())
					{
						$data[] = $this->tree_helper($personal, 'g_farm_'.$farm->id(), $farm_entry_level, $farm->color, $farm->id());
					}
				}
			
			}

		}

		$this->fix_children($data);

		$data = $this->clean_empty_groups($data);

		$this->request->response = Json::arr($data, count($data));


	}

	public function action_tree(){

		/*
		$user = Auth::instance()->get_user();
		$check = Arr::get($_GET, 'check', false);

		$both_trees = filter_var(Arr::get($_GET, 'both_trees', false), FILTER_VALIDATE_BOOLEAN);
		$linked_with_names = filter_var(Arr::get($_GET, 'linked_with_names', true), FILTER_VALIDATE_BOOLEAN);
		$only = Arr::get($_GET, 'only', '');
		$jobs_are_groups = filter_var(Arr::get($_GET, 'jobs_are_groups', false), FILTER_VALIDATE_BOOLEAN);
		$with_friends = filter_var(Arr::get($_GET, 'with_friends', false), FILTER_VALIDATE_BOOLEAN);
		
		$farm_id = (int)Arr::get($_GET, 'farm_id', null);

		$data =	Jelly::factory('client_handbook_personalgroup')->get_tree($user->license->id(), $both_trees, $check, 'items',  false, $jobs_are_groups, $with_friends, $linked_with_names, $only, $farm_id);

		$this->request->response = Json::arr($data, count($data));
		*/
		
		$farms = Jelly::factory('farm')->get_session_farms();
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$user = Auth::instance()->get_user();
		
		$personals = Jelly::select('client_handbook_personal')
					->with('group')
					->where_open()
					->where('deleted', 'IS', null)
					->or_where('deleted', '=', 0)
					->where_close()
					->where('license', '=', $user->license->id());
					
		if(count($farms))
		{					
					$personals->where('farm', 'IN', $farms);
		}			
		
		$personals = $personals
					->where('period', 'IN', $periods)
					->execute();
		
		$data = array();			
		
		$glossary_ids = array();
		
		$personal_objects = array();
		
		$people = array();
		
		$glossary_group_ids = array();
		$glossary_item_ids  = array();
		
		foreach($personals as $personal)
		{
			if( $personal->group->id() )
			{
				if( $personal->group->is_position )
				{
					$glossary_item_ids[]  = $personal->group->id_in_glossary;
				}
				else
				{
					$glossary_group_ids[] = $personal->group->id_in_glossary;		
				}
			}
			
			$people[] = $personal;
		}
		
		$glossary_item_ids 	= array_unique($glossary_item_ids);
		$glossary_group_ids = array_unique($glossary_group_ids);
		
		if(! count ($glossary_item_ids) and !count($glossary_group_ids))
		{
			$this->request->response = Json::arr($data, count($data));
			return;
		}
		
		$items_result = Jelly::select('glossary_personal')->with('group')->where('_id', 'IN', $glossary_item_ids)->execute();
		
		$items = array();
		
		foreach($items_result as $itm)
		{
			$items[] =  $itm;
			
			if($itm->group->id())
			{
				$glossary_group_ids[] = $itm->group->id();
			}
		}
		
		$glossary_group_ids = array_unique($glossary_group_ids);
		
		if ( !count($glossary_group_ids) )
		{
			$glossary_group_ids = array(-1);
		}
				
		$group_result = Jelly::select('glossary_personalgroup')->with('parent')->where('_id', 'IN', $glossary_group_ids)->order_by('path', 'ASC')->execute();
		
		$groups = array();
		
		foreach($group_result as $gr)
		{
			$groups[] = $gr;
		}
		
		// Айдищники всех групп по дереву
		$group_ids = array();
		
		foreach($groups as $group)
		{
			$group_ids[] = $group->id();
			
			$path = explode('/', $group->path);

			for($i = 0; $i < $k = count($path); $i++)
			{
				if($path[$i] == '')
					continue;
				else
					$group_ids[] = (int)$path[$i];
			}
		}
		
		$group_ids = array_unique($group_ids);
		
		if( !count($group_ids) )
			$group_ids = array(-1);
		
		// Полное дерево групп
		$group_result = Jelly::select('glossary_personalgroup')->with('parent')->where('_id', 'IN', $group_ids)->order_by('path', 'ASC')->execute();
		
		$groups = array();
		
		foreach($group_result as $gr)
		{
			$groups[] = $gr;
		}
		
		
		foreach($groups as $g)
		{
			$level 	= -1;
			$path = explode('/', $g->path);

			for($i = 0; $i < $k = count($path); $i++)
			{
				if($path[$i] == '')
					continue;
				else
					$level++;
			}
			
			if($level == -1)
				$level = 0;
					
			$children_n = array();
			
			@reset($people);
			
			/*
			foreach($people as $ppl)
			{
				if($ppl->group->id_in_glossary == $g->id())
				{
					$children_n[] = 'n'.$ppl->id();
				}
			}*/
					
			$data[] = array(
							 'id' => 'g'.$g->id(),
							 'title' => $g->name,
							 'is_group' => 1,
							 'is_group_realy' => 1,
							 'children_g' => array(),
							 'children_n' => $children_n,
							 'parent' => $g->parent->id() ? 'g'.$g->parent->id() : null,
							 'level' => $level,
							 'color' => $g->color,
							 'parent_color' => $g->parent->color
						 );
			/** Детишке **/
			
			@reset($items);
			
			foreach($items as $p)
			{
				if( $p->group->id() != $g->id())
					continue;
				
				$children_n = array();
			
				@reset($people);
				
				foreach($people as $ppl)
				{
					if($ppl->group->id_in_glossary == $p->id())
					{
						$children_n[] = 'n'.$ppl->id();
					}
				}
				
				$data[] = array(
							 'id' => 'gn'.$p->id(),
							 'title' => $p->name,
							 'is_group' => 1,
							 'is_group_realy' => 0,
                             //'is_position' => 1,
							 'children_g' => array(),
							 'children_n' => $children_n,
							 'parent' => $p->group->id() ? 'g'.$p->group->id() : null,
							 'level' => $level + 1,
							 'color' => $p->color,
							 'parent_color' => $p->group->color
						 );
				
						 
					
			}
		}
		
		/** Без группы */
		
		foreach($items as $p)
		{
			$children_n = array();
			
			if($p->group->id())
				continue;
			
			@reset($people);	
			
			foreach($people as $ppl)
			{
				if($ppl->group->id_in_glossary == $p->id())
				{
					$children_n[] = 'n'.$ppl->id();
				}
			}
				
			$data[] = array(
						 'id' => 'gn'.$p->id(),
						 'title' => $p->name,
						 'is_group' => 1,
						 'is_group_realy' => 0,
                         //'is_position' => 1,
						 'children_g' => array(),
						 'children_n' => $children_n,
						 'parent' => null,
						 'level' => 0,
						 'color' => $p->color,
						 'parent_color' => '',
					 );
			
					 
		}
						
		$this->fix_children($data);
			
		$this->request->response = Json::arr($data, count($data));
		
		//new AC_Profiler;	
	}
	
	public function action_farm_tree()
	{
		$groups  = (int)Arr::get($_GET, 'groups', null);
		$farm_id = (int)Arr::get($_GET, 'farm_id', null);
		
		if( $farm_id < 0)
		{
			throw new Kohana_Exception('Farm id not found!');
		}
		
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);
		
		$data = array();
		
		if($groups)
		{
			$groups_by_farm = Jelly::select('client_handbook_personalgroup')
								->with('parent')
								->where('license', '=', Auth::instance()->get_user()->license->id())
								->where('farm', '=', $farm_id)
								->where_open()->where('is_position', '=', 0)->or_where('is_position', 'IS', null)->where_close()
								->where('period', 'IN', $periods)
								->where_open()->where('deleted', 'IS', null)->or_where('deleted', '=', 0)->where_close()
								->order_by('path', 'ASC')
								->order_by('name', 'ASC')
								->execute();
			
			foreach($groups_by_farm as $g)
			{
					
					$level 	= -1;
					$path = explode('/', $g->path);

					for($i = 0; $i < $k = count($path); $i++)
					{
						if($path[$i] == '')
							continue;
						else
							$level++;
					}
					
					if($level == -1)
						$level = 0;
					
					$data[] = array(
							 'id' => 'g'.$g->id(),
							 'title' => $g->name,
							 'is_group' => 1,
							 'is_group_realy' => 1,
							 'children_g' => array(),
							 'children_n' => array(),
							 'parent' => $g->parent->id() ? 'g'.$g->parent->id() : null,
							 'level' => $level,
							 'color' => $g->color,
							 'id_in_glossary' => $g->id_in_glossary,
							 'parent_color' => $g->parent->id() ? $g->parent->color : 'BBBBBB'
						 );
			}
			
			$positions = Jelly::select('client_handbook_personalgroup')
								->with('parent')
								->where('license', '=', Auth::instance()->get_user()->license->id())
								->where('farm', '=', $farm_id)
								->where('is_position', '=', 1)
								->where('period', 'IN', $periods)
								->where_open()->where('deleted', 'IS', null)->or_where('deleted', '=', 0)->where_close()
								->order_by('path', 'ASC')
								->order_by('name', 'ASC')
								->execute();
								
			foreach($positions as $position)
			{
				$parent = $this->tree_get_element_by_id($data, 'g'.$position->parent->id());
				
				if(array_key_exists($parent, $data))
				{
					$data[$parent]['children_n'][] = 'n'.$position->id();
				}	
			}
		}
		else
		{
			$positions = Jelly::select('client_handbook_personalgroup')
									->with('parent')
									->where('license', '=', Auth::instance()->get_user()->license->id())
									->where('farm', '=', $farm_id)
									->where('is_position', '=', 1)
									->where('period', 'IN', $periods)
									->where_open()->where('deleted', 'IS', null)->or_where('deleted', '=', 0)->where_close()
									->order_by('path', 'ASC')
									->order_by('name', 'ASC')
									->execute();
			
			foreach($positions as $position)
			{
				$data[] = array(
							 'id' => 'n'.$position->id(),
							 'title' => $position->name,
							 'is_group' => 1,
							 'is_group_realy' => 0,
							 'children_g' => array(),
							 'children_n' => array(),
							 'parent' => $position->parent->id() ? 'g'.$position->parent->id() : 'g-2',
							 'level' => 0,
							 'color' => $position->color,
							 'id_in_glossary' => $position->id_in_glossary,
							 'parent_color' => $position->parent->id() ? $position->parent->color : 'BBBBBB'
						 );
			}						
		}
		
		$this->fix_children($data);
		
		$this->request->response = Json::arr($data, count($data));
	}
	

	private function fix_children(&$data)
	{
		for($i = 0; $i < $k = count($data); $i++)
		{
			if(!$data[$i]['parent'] or  $data[$i]['parent'] == '')
				continue;

			$parent = $this->tree_get_element_by_id($data, $data[$i]['parent']);

			if(!array_key_exists($parent, $data))
				continue;

			$found = false;

			for($j = 0; $j < $tt = count($data[$parent]['children_g']); $j++)
			{
				if($data[$parent]['children_g'][$j] == $data[$i]['id'])
				{
					$found = true;
					break;
				}
			}

			if(!$found)
			{
				$data[$parent]['children_g'][] = $data[$i]['id'];
				$data[$i]['level'] = $data[$parent]['level'] + 1; 
			}
		}
	}

	private function clean_empty_groups($data)
	{
		$max_level = 0;
		$min_level = 9999;
		/** Ищем максимальный и минимальный левел */
		for($i = 0; $i < $k = count($data); $i++)
		{
			if(stripos($data[$i]['id'], 'g_personal_group_') !== false)
			{
				if($max_level < $data[$i]['level'])
					$max_level =  $data[$i]['level'];

				if($min_level > $data[$i]['level'])
					$min_level =  $data[$i]['level'];
			}
		}

		for($level = $max_level; $level >= $min_level - 1; $level--)
		{
			$tmp = array();


			for($i = 0; $i < $k = count($data); $i++)
			{

				if(stripos($data[$i]['id'], 'g_personal_group_') !== false and (int)$data[$i]['level'] == $level )
				{
					if(!count($data[$i]['children_g']))
					{
						if($data[$i]['parent'] != '')
						{
							$parent = $this->tree_get_element_by_id($data, $data[$i]['parent']);

							if(!$parent)
								continue;

							$tmp2 = array();

							for($j = 0; $j < $kk = count($data[$parent]['children_g']); $j++)
							{
								if($data[$parent]['children_g'][$j] !=  $data[$i]['id'])
								{
									$tmp2[] = $data[$parent]['children_g'][$j];
								}
							}

							$data[$parent]['children_g'] = $tmp2;

							$p_tmp = $this->tree_get_element_by_id($tmp, $data[$i]['parent']);

							if($p_tmp)
							{
								$tmp[$p_tmp]['children_g'] = $tmp2;
							}

						}
						continue;
					}
					else
						$tmp[] = $data[$i];
				}
				else
					$tmp[] = $data[$i];
			}

			$data = $tmp;
		}

		return $data;
	}


	private function tree_get_element_by_id(&$data, $id)
	{
		for($i = 0; $i < $k = count($data); $i++)
		{
			if($data[$i]['id'] == $id)
			{
				return $i;
			}
		}

		return null;
	}

	private function tree_helper($object, $parent_id = null, $level =  0, $parent_color = 0, $farm_id = null)
	{
		if($object instanceof Model_User)
		{
			return array(
				 'id' => 'g_user_'.$object->id(),
				 'title' => trim(htmlspecialchars($object->first_name.' '.$object->middle_name.' '.$object->last_name)) == '' ? $object->username : htmlspecialchars($object->first_name.' '.$object->middle_name.' '.$object->last_name),
				 'is_group' => 1,
				 'is_group_realy' => 0,
				 'children_g' => array(),
				 'children_n' => array(),
				 'parent' => $parent_id,
				 'level' => $level,
				 'color' => $object->color,
				 'parent_color' => $parent_color
			);
		}

		if($object instanceof Model_Farm)
		{
			return array(
				 'id' => 'g_farm_'.$object->id(),
				 'title' => $object->name,
				 'is_group' => 1,
				 'is_group_realy' => $object->is_group ? 1: 0,
				 'children_g' => array(),
				 'children_n' => array(),
				 'parent' => $parent_id,
				 'level' => $level,
				 'color' => $object->color,
				 'parent_color' => $parent_color
			);
		}

		if($object instanceof Model_License)
		{
			return array(
				 'id' => 'g_license_'.$object->id(),
				 'title' => $object->name,
				 'is_group' => 1,
				 'is_group_realy' => 1,
				 'children_g' => array(),
				 'children_n' => array(),
				 'parent' => null,
				 'level' => 0,
				 'color' => $object->color,
				 'parent_color' => null
			);
		}

		if($object instanceof Model_Client_Handbook_PersonalGroup)
		{
			return array(
					 'id' => 'g_personal_group_'.$object->id().'_g_farm_'.$farm_id,
					 'title' => $object->name,
					 'is_group' => 1,
					 'is_group_realy' => 1,
					 'children_g' => array(),
					 'children_n' => array(),
					 'parent' => $parent_id,
					 'level' => $level,
					 'color' => $object->color,
					 'parent_color' => $parent_color
				);
		}

		if($object instanceof Model_Client_Handbook_Personal)
		{
			return array(
					 'id' => 'g_personal_user_'.$object->id(),
					 'title' => $object->name,
					 'is_group' => 1,
					 'is_group_realy' => 0,
					 'children_g' => array(),
					 'children_n' => array(),
					 'parent' => $parent_id,
					 'level' => $level,
					 'color' => $object->color,
					 'parent_color' => $parent_color
				);
		}
	}


	public function inner_update($id){}
	public function validate_data($data){}


	public function action_addnomenclature(){

		$names = Arr::get($_POST, 'ids', '');
		$names = explode(',', $names);
		
		$names_arr = array();
		
		foreach($names as $name)
		{
			$names_arr[] = (int)substr($name, 1, strlen($name) - 1);
		}
		
		if(!count($names_arr))
		{
			return	$this->request->response = JSON::success(array('script' => "None Added", 'url' => null, 'success' => true));
		}		
		
		$base_groups = Jelly::select('glossary_personal')->with('group')->where('_id', 'IN', $names_arr)->execute();
		
		$groups_arr  = array();		
				
		foreach($base_groups as $bd)
		{
			
			if(!$bd->group->id())
				continue;
			
			$groups_arr[] = $bd->group->id();
			
			$path = explode('/', $bd->group->path);

			for($i = 0; $i < $k = count($path); $i++)
			{
				if($path[$i] == '')
					continue;
				
				$groups_arr[] = (int)$path[$i];
			}
		}
		
		$groups = array();
		
		foreach(array_unique($groups_arr) as $gi)
		{
			$groups[] = 'g'.$gi;
		}
				
		$merged = array_merge($groups, $names);
			
        $farm_id = Arr::get($_POST, 'farm_id', NULL);

		Jelly::factory('client_handbook_personalgroup')->add_nomenclature($merged, $farm_id, Auth::instance()->get_user()->license->id());

		$this->request->response = JSON::success(array('script' => "Added", 'url' => null, 'success' => true));
	}


	public function inner_edit(&$view){
//		if($view->model && isset($view->model['_id'])){
//			$view->model['grasp_units'] = array('_id'=>$view->model['grasp_units']->id(), 'name' => $view->model['grasp_units']->name);
//			$view->model['gsm'] = array('_id'=>$view->model['gsm']->id(), 'name' => $view->model['gsm']->name);
//		}

		$view->hours_units = Jelly::factory('glossary_units')->getUnits('personal_time');
		$view->productivity_units = Jelly::factory('glossary_units')->getUnits('personal_productivity');
		$view->payment_units = Jelly::factory('glossary_units')->getUnits('personal_payment');

		$view->model_fields = array();
	}

	public function action_find_group($id = NULL){
		if(!$id)return;

		$user = Auth::instance()->get_user();

		$periods = Session::instance()->get('periods');

		if(!is_array($periods) or !count($periods))
			throw new Kohana_Exception('Period not defined!');

		$period = $periods[0];

		$groups = Jelly::select('client_handbook_personalgroup')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('license', '=', (int)($user->license->id()))->
					where('period', '=', (int)$period)->
					where('is_position', '=', 1)->
					where('_id', '=', (int)$id)->
					execute();


		if($groups[0] && $groups[0]->id()){
			$this->request->response = JSON::success(array('script' => "Found", 'url' => null, 'success' => true, 'id' => $groups[0]->id(), 'name' => $groups[0]->name));
		} else {
			$this->request->response = JSON::error('Запись не найдена!');
		}

	}
}
