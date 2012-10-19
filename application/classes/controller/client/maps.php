<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Maps extends AC_Controller
{
	public $auto_render  = false;
    protected $model_name = 'field';
    
    public function action_photo()
	{
		if(!$_POST or !$_FILES)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image = Arr::get($_FILES, 'image', null);

		if(!$image)
			throw new Kohana_Exception(__('No image given'));

		$name = 'original_'.Text::random('alnum', 15).'.jpg';
		$filename = Upload::save($image, $name, DOCROOT.'media/pictures/', 0777);

		$this->request->response = json_encode(array('success' => true, 'image' => '/media/pictures/'.$name));
	}

	public function action_delphoto()
	{

		if(!$_POST)
			throw new Kohana_Exception(__('POST method required. Please, contact developers'));

		$image_path = Arr::get($_POST, 'img_path', null);

		if(!$image_path)
			throw new Kohana_Exception(__('No image given'));

		$dirs = explode('/',$image_path);
		$filename = $dirs[count($dirs)-1];
		$subdir = $dirs[count($dirs)-2];

		if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename))
		{

			unlink(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$filename);
		}

		if(is_file(DOCROOT.'media/pictures/'.$filename)) // удаляем превью
		{
			unlink(DOCROOT.'media/pictures/'.$filename);
		}

		$this->request->response = json_encode(array('success' => true, 'image' => $filename));
	}

	public function action_savephoto($id)
	{

	// image manipulating
		$photo = Arr::get($_POST, 'photo', null);
		if($photo != '')
		{
			$photos = explode(',',$photo);
			foreach($photos as $photo){
				// Посмотрим, или это временное что-то
				if(preg_match('#(.*)original_(.*).jpg#', $photo))
				{
					// Да, надо перенести куда следует
					$subdir = floor($id / 2000);

					if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'))
					{
						@mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/');
					}

					if(!is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
					{
						@mkdir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir, 0777);
					}
					if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg')){
						rename(	DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg',
								DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'_changed_'.time().'_'.Text::random('alnum', 15).'.jpg');
					}

					if(is_file(DOCROOT.$photo)){
						copy(DOCROOT.$photo, DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg');
						chmod(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/item_'.$id.'.jpg', 0777);
					}
				}
				// Если же нет - тогда делать ничего не надо. И это прекрасно!
			}
		}

	}

	public function action_getphoto(&$view, $id)
	{
		$subdir = floor($id / 2000);
		$view->model['images'] = array();
		if(is_dir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir))
		{
			$files = scandir(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir);
			foreach($files as $file){
				if(is_file(DOCROOT.Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file) && ( !(strpos($file, 'item_'.$id.'_')===FALSE) || !(strpos($file, 'item_'.$id.'.')===FALSE)       )   ){
					$view->model['images'][] = Kohana::config('upload.path').'/'.$this->model_name.'/'.$subdir.'/'.$file;
				}
			}
		}
	}

	public function action_read()
	{
		$edit = arr::get($_GET, 'edit', false);
		$ids = arr::get($_GET, 'ids', 'all');
		$group_by = arr::get($_GET, 'group_by', 'culture');
		$flds = $ids=='all' ? $ids : explode('_', $ids);
		if(isset($flds[0]) && !trim($flds[0])) $flds = array(-1);

		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);

		$session = Session::instance();
		$periods = $session->get('periods');
		if(!count($periods)) $periods = array(-1);

		$user = Auth::instance()->get_user();
		$license_id = $user->license->id();

		$exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $license_id);
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $license_id);
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
		$cultures = Jelly::factory('glossary_culturegroup')->get_tree($license_id, true, $exclude, 'items');
		$cultures[] = array('id' => 'n0');

        $view = Twig::factory('client/maps/list');
		$view->formats = Jelly::factory('client_format')->get_formats($license_id);

		$fields= Jelly::select('field')->with('culture')->with('culture_before')->with('seed')->with('production')->with('productionclass')->with('farm')
									   ->where('deleted', '=', false)->and_where('license', '=', $license_id)->and_where('period', 'IN', $periods);

		if($flds=='all') $fields = $fields->and_where('farm', 'IN', $farms)->order_by('farm');
		else                  $fields = $fields->and_where('_id', 'IN', $flds)->order_by('farm');
		$fields = $fields->execute()->as_array();
		

		$blocks = array();
		foreach($fields as $field){
			if(!isset($blocks[$field[':farm:_id']]))$blocks[$field[':farm:_id']] = array();
			$field['notes'] = Jelly::select('fieldnote')->where('field', '=', $field['_id'])->execute()->as_array();
			$blocks[$field[':farm:_id']][] = $field;
		}

		$res_blocks = array();
		foreach($blocks as &$block){
			foreach($cultures as $culture){
				foreach($block as &$item){
					if($item[$group_by]==substr($culture['id'], 1) && substr($culture['id'], 0, 1)=='n'){
						$item['farm'] = array('_id' => $item[':farm:_id'], 'name' => $item[':farm:name']);
						$item['culture'] = array('_id' => $item[':culture:_id'], 'title' => $item[':culture:title'], 'color' => $item[':culture:color']);
						$item['culture_before'] = array('_id' => $item[':culture_before:_id'], 'title' => $item[':culture_before:title'], 'color' => $item[':culture_before:color']);
						$item['seed'] = array('_id' => $item[':seed:_id'], 'name' => $item[':seed:name'], 'color' => $item[':seed:color']);
						$item['production'] = array('_id' => $item[':production:_id'], 'name' => $item[':production:name'], 'color' => $item[':production:color']);
						$item['productionclass'] = array('_id' => $item[':productionclass:_id'], 'name' => $item[':productionclass:name'], 'color' => $item[':productionclass:color']);
						$res_blocks[] = $item;
					}
				}
			}
		}


		$view->fields = $res_blocks;
		$view->flds = $res_blocks;
		$view->edit = $edit;
		$view->group_by = $group_by;
		setlocale(LC_NUMERIC, 'C');
        $this->request->response = JSON::reply($view->render());
	}
	
	public function action_work()
	{
		$edit = arr::get($_GET, 'edit', false);
		$ids = arr::get($_GET, 'ids', 'all');
		$group_by = arr::get($_GET, 'group_by', 'culture');
		$flds = $ids=='all' ? $ids : explode('_', $ids);
		if(isset($flds[0]) && !trim($flds[0])) $flds = array(-1);
		
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('field')->get_work_grid_data($user->license->id());

        $view = Twig::factory('client/maps/work_grid');
		$view->data = $data;

		setlocale(LC_NUMERIC, 'C');
        $this->request->response = JSON::reply($view->render());
	}

	public function action_create()
	{
		if (is_null($user = $this->auth_user())) return;

        $view = $this->edit_form($user);
		$view->formats = Jelly::factory('client_format')->get_formats($user->license->id());
		$view->shares = array();

		$values = array();

		$values['properties'] 			= Jelly::factory('client_model_properties')->get_properties('field', null);
		$values['ground_properties'] 	= Jelly::factory('client_model_properties')->get_properties('fieldground', null);

		$view->field = $values;

        $this->request->response = JSON::reply($view->render());
	}

	public function action_edit($id)
	{
		$edit = arr::get($_GET, 'edit', true);
		
		if (is_null($user = $this->auth_user())) return;
		$id = substr($id, 1);

	    $obj = Jelly::select('field')->with('notes')->with('works')->with('acidity')->load($id);

	    if (!$obj->loaded()) { echo 'not found'; return; }

        $view = $this->edit_form($user);
		$values = $obj->as_array();
		$shares = Jelly::factory('Client_Share')->get_shares_for_field((int)$values['_id']);

        $values['culture'] = array('_id'=>$values['culture']->id(), 'name'=>$values['culture']->title, 'color'=>$values['culture']->color);
        $values['farm'] = $obj->farm->id();
        $values['farm_name'] = $obj->farm->name;
        $values['culture_before'] = array('_id'=>$values['culture_before']->id(), 'name'=>$values['culture_before']->title, 'color'=>$values['culture_before']->color);
		$values['acidity'] = array('_id'=>$values['acidity']->id(), 'name'=>$values['acidity']->name, 'acidity_from'=>number_format($values['acidity']->acidity_from, 1), 'acidity_to'=>number_format($values['acidity']->acidity_to, 1));


		$values['properties'] 			= Jelly::factory('client_model_properties')->get_properties('field', $id);
		$values['ground_properties'] 	= Jelly::factory('client_model_properties')->get_properties('fieldground', $id);

		if($values['acidity']['acidity_from']=='0.0' && $values['acidity']['acidity_to']=='0.0') $values['acidity']['options'] = '';
		else{
			$first_value = $values['acidity']['acidity_from'].'-'.$values['acidity']['acidity_to'];
			$values['acidity']['options'] = '<option '.($values['acidity_ratio']==$first_value ? 'selected="selected"':'').'>'.$first_value.'</option>';
			$from = (float)$values['acidity']['acidity_from']; $to = (float)$values['acidity']['acidity_to'];
			while($from<=$to){
				$values['acidity']['options'] .= '<option '.(number_format($from, 1)==$values['acidity_ratio'] ? 'selected="selected"':'').'>'.number_format($from, 1).'</option>';
				$from += 0.1;
			}
		}

        if($obj){
            $view->model = array();
            $this->action_getphoto($view, $obj->id());
        }
        $view->field = $values;
		$view->formats = Jelly::factory('client_format')->get_formats($user->license->id());
		$view->shares = $shares;
		$view->edit = $edit;

        $view->selected_farm_color = $obj->farm->color;

        $view->selected_farm_is_group = $obj->farm->is_group;

        $this->request->response = JSON::reply($view->render());
	}


	public function action_list_cultures()
    {
		if (is_null($user = $this->auth_user())) return;

		$exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $user->license->id());
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $user->license->id());
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);

		$data =	Jelly::factory('glossary_culturegroup')->get_tree($user->license->id(), true, $exclude, 'items');

		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods', array());
		if(!is_array($periods) || !count($periods)) $periods = array(-1);
		$fields = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->execute()->as_array();

		foreach($fields as $field){
			foreach($data as &$culture){
				if('n'.$field['culture']==$culture['id']){
					$culture['square'] = isset($culture['square']) ? (float)$culture['square']+(float)$field['area'] : (float)$field['area'];
					if($culture['parent'])$this->update_culture_parent_square($data, $culture['parent'], $field['area']);
					break;
				}
			}
		}

		foreach($data as &$culture){
			if(isset($culture['square']) && (float)$culture['square']>0) $culture['title'] = $culture['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.str_replace (',', '.', $culture['square']).' га</div><div>';
		}
		$this->request->response = Json::arr($data, count($data));
	}

	public function action_list_fields()
    {
        if (is_null($user = $this->auth_user())) return;

		$sort = Arr::get($_GET, 'sort', '');
		$sort = @json_decode($sort, true);
		if(!$sort) $sort = array(array('property'=>'culture', 'direction'=>'asc'));

        $data =	Jelly::factory('field')->get_tree($user->license->id(), true, $sort[0]['property']);
		$this->request->response = Json::arr($data, count($data));
	}

	public function action_update()
	{
        if (is_null($user = $this->auth_user())) return;

		$farm = Arr::get($_POST, 'farm', null);

        $session = Session::instance();
		$periods = $session->get('periods');
		if(count($periods)<1){
			$this->request->response = JSON::error('Необходимо выбрать период.');
            return;
		}

        $period = (int)$periods[0];

        if (isset($_POST['_id']))
        {
            $model = Jelly::select('field', $_POST['_id']);
            $sq = Auth::instance()->get_user()->license->get_square($period, (int)$_POST['_id']);
        }
        else
        {
            $model = Jelly::factory('field');

            $sq = Auth::instance()->get_user()->license->get_square($period);

            $max_fields = Jelly::select('license', Auth::instance()->get_user()->license->id());
            $max_fields = $max_fields->max_fields;

            $fields_count = Auth::instance()->get_user()->license->get_fields_count($period);

            if($fields_count >= $max_fields)
            {
                throw new Kohana_Exception('Нельзя создать поле -- действует ограничение на количество полей в Лицензии');
            }

            if($farm)
            {
                $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int)$farm);
                if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded())
                {
                    Session::instance()->set('last_create_farm', (int)$farm);
                }
                else throw new Kohana_Exception('Хозяйства не существует');
            }
        }

        $max_square = Jelly::select('license', Auth::instance()->get_user()->license->id());
        $max_square = $max_square->square;

        $area = Arr::get($_POST, 'area', 0);

        if($area)
        {
            if($sq + $area > $max_square)
            {
                throw new Kohana_Exception('Общая площадь полей превышает пороговое ограничение Лицензии. Заложено '.$max_square. ' га, на данный момент - '.($sq + $area).' га.');
            }
        }




		$corp_r_n = Arr::get($_POST, 'crop_rotation_number', false);
		$field_n = Arr::get($_POST, 'number', false);
		$sector_n = Arr::get($_POST, 'sector_number', false);

        if($corp_r_n && $field_n && $sector_n)
        {
			$existed = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->and_where('farm', '=', $farm)->and_where('period', 'IN', $periods)->and_where('crop_rotation_number', '=', $corp_r_n)->and_where('number', '=', $field_n)->and_where('sector_number', '=', $sector_n);
			if(isset($_POST['_id'])) $existed = $existed->and_where ('_id', '<>', (int)$_POST['_id']);
			$existed = $existed->execute()->as_array();
			if(count($existed)){
				$this->request->response = JSON::error('В выбраном хозяйстве уже существует поле с такой комбинацией № в севообороте, № поля, № участка.'); return;
			}
		}


		$_POST['period']           = (int)$periods[0];
        $_POST['culture']           = (int)$_POST['culture'];
        $_POST['culture_before']    = (int)$_POST['culture_before'];
		$_POST['name']						 = Arr::get($_POST, 'name', '');
		$_POST['crop_rotation_number'] = Arr::get($_POST, 'crop_rotation_number', '');
		$_POST['number']					= Arr::get($_POST, 'number', '');
		$_POST['sector_number']			 = Arr::get($_POST, 'sector_number', '');

		setlocale(LC_NUMERIC, 'C');
		$_POST['area']			   = (float)Arr::get($_POST, 'area', '0.00');
		$_POST['kadastr_area'] = (float)Arr::get($_POST, 'kadastr_area', '0.00');

		$acidity = Arr::get($_POST, 'acidity', '');
		$acidity = explode('_', $acidity);
		$_POST['acidity'] = (int)Arr::get($acidity, 0, 0);
		$_POST['acidity_ratio'] = Arr::get($acidity, 1, '');
		$_POST['ground_type'] = (int)Arr::get($_POST, 'ground_type', 0);
		
		$_POST['seed'] = (int)Arr::get($_POST, 'seed', 0);
		$_POST['production'] = (int)Arr::get($_POST, 'production', 0);
		$_POST['productionclass'] = (int)Arr::get($_POST, 'productionclass', 0);
		$_POST['culture'] = (int)Arr::get($_POST, 'culture', 0);
		$_POST['culture_before'] = (int)Arr::get($_POST, 'culture_before', 0);

		$coordinates = Arr::get($_POST, 'coordinates', false);
		$c_arr = explode(',', $coordinates);
		if(count($c_arr)<8){ $this->request->response = JSON::error('Координаты поля не заданы'); return false; }


		$coordinates = Arr::get($_POST, 'arrow_coordinates', false);
		$c_arr = explode(',', $coordinates);
		if(count($c_arr)<2 && isset($_POST['arrow_coordinates'])){ 
			unset($_POST['arrow_coordinates']);
		}

        $model->set($_POST);

        $model->set('license', $user->license);

        $model->save();
        
        $this->action_savephoto($model->id());
		$this->save_notes($model->id());
		$this->save_works($model->id());

		$shares = Arr::get($_POST, 'shares', '');
		$shares = @json_decode($shares, true);
		Jelly::factory('client_fieldshare')->save_shares($model->id(), $shares);

		/*
		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'property_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('property_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'insert_property_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'name_insert_'.UTF8::str_ireplace('insert_property_', '', $key),''),
								  'value' => $value);
			}
		}*/

		Jelly::factory('client_model_properties')->update_properties('field', $_POST, 'property', $model->id());
		Jelly::factory('client_model_properties')->update_properties('fieldground', $_POST, 'groundprop', $model->id(), 'groundprop');

		/*
		Jelly::factory('extraproperty')->updateOldFields((int)$model->id(), 'field', $addons);

		$addons = array();
		foreach($_POST as $key => $value){
			if(UTF8::strpos($key, 'groundprop_') !== false and UTF8::strpos($key, 'label') === false){
				$addons[] = array('_id'   => (int)UTF8::str_ireplace('groundprop_', '', $key),
								  'name'  => arr::get($_POST,$key.'_label',''),
								  'value' => $value);
			}
			if(UTF8::strpos($key, 'insert_groundprop_') !== false){
				$addons[] = array('name'  => arr::get($_POST,'name_insert_groundprop_'.UTF8::str_ireplace('insert_groundprop_', '', $key),''),
								  'value' => $value);
			}
		}
		Jelly::factory('extraproperty')->updateOldFields((int)$model->id(), 'fieldground', $addons);
		*/

		$this->request->response = JSON::success(array('script' => "Поле сохранено успешно!",
																			'url'    => null,
																			'field_id'    => $model->id(),
																			'success'    => true,));
	}
	
	
	private function save_notes($field_id){
		$notes_grid = Arr::get($_POST, 'notes_grid', false);
		if($notes_grid){
			$notes_grid = @json_decode($notes_grid, true);
			if(!$notes_grid) $notes_grid = array();
			Jelly::factory('fieldnote')->save_from_grid($notes_grid, $field_id);
		}
	}
	
	private function save_works($field_id){
		$works_grid = Arr::get($_POST, 'work_grid', false);
		if($works_grid){
			$works_grid = @json_decode($works_grid, true);
			if(!$works_grid) $works_grid = array();
			Jelly::factory('fieldwork')->save_from_grid($works_grid, $field_id);
		}
	}
	
    private function edit_form($user)
    {
        $tpl = Twig::factory('client/maps/read');


        $cultures = Jelly::select('glossary_culture')->
                    where('deleted', '=', false)->
                    //and_where('license', '=', $user->license->id())->
                    order_by('name', 'asc')->
                    execute()->
                    as_array();

        $farms = Jelly::select('farm')->
                    where('deleted', '=', false)->
                    and_where('license', '=', $user->license->id())->
                    //and_where('is_group', '=', false)->
                    order_by('name', 'asc');

        $session 		= Session::instance();
        $s_farms 		= $session->get('farms');
        $s_farm_groups 	= $session->get('farm_groups');

        if(!is_array($s_farms))
        	$s_farms = array();

		if(!is_array($s_farm_groups))
        	$s_farm_groups = array();

		$farm_ids = array_unique(array_merge($s_farms, $s_farm_groups));

		if(count($s_farms) or count($s_farm_groups)){
  			$farms->where(':primary_key', 'IN', $farm_ids);
        }

        $farms  = $farms->execute()->as_array();

        $selected_farm = Arr::get($_GET, 'selected', '');
        $selected_farm_name = '';
        $selected_farm_color = '';
		$selected_farm_is_group = '';

        if($selected_farm != ''){
            $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int)$selected_farm);

            if(($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded()){
				$selected_farm_name  = $selected_farm_obj->name;
				$selected_farm_color = $selected_farm_obj->color;
				$selected_farm_is_group = $selected_farm_obj->is_group;
            }
        }

        /**
         *  3) В случае, когда нет фокуса на хозяйстве, в первый раз при создании поля по умолчанию ставить ему первое хозяйство как родительское, и все последующие разы по умолчанию родительское хозяйство у поля - это последнее выбранное хозяйство, для которого создавалось поле (фокус на хозяйстве приоритетней чем значение по умолчанию)
         * */

        /** ХЗ, что такое "первое хозяйство", надеюсь, это первое по названию, но кто его знает. И не описано, че делать при включенном фильтре.
         *  I was forced to write this code. Forgive me.
         **/

        $last_farm = $session->get('last_create_farm', '');
        if($selected_farm == '') {
            if($last_farm != '' && in_array($selected_farm,$farm_ids) ) {
                $selected_farm = $last_farm;
                $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int)$selected_farm);

                if( ($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded() ) {
                    $selected_farm_name = $selected_farm_obj->name;
                    $selected_farm_color = $selected_farm_obj->color;
					$selected_farm_is_group = $selected_farm_obj->is_group;
                }
            }else{
                if(count($farms)) {
                    $selected_farm      = $farms[0]['_id'];
                    $selected_farm_name = $farms[0]['name'];
                    $selected_farm_color = $farms[0]['color'];
					$selected_farm_is_group = $farms[0]['is_group'];
                }
            }
        }

        $tpl->selected_farm = $selected_farm;
        $tpl->selected_farm_name = $selected_farm_name;
        $tpl->selected_farm_color = $selected_farm_color;
		$tpl->selected_farm_is_group = $selected_farm_is_group;

		$acidities = Jelly::select('glossary_acidity')->execute()->as_array();
		foreach($acidities as &$acidity){
			$acidity['acidity_from'] = number_format($acidity['acidity_from'], 1);
			$acidity['acidity_to'] = number_format($acidity['acidity_to'], 1);
		}
		$tpl->acidities = $acidities;
		$tpl->ground_types = Jelly::select('glossary_groundtype')->execute()->as_array();
        $tpl->cultures = $cultures;
        $tpl->farms = $farms;
		$tpl->edit = true;

        return $tpl;
    }

    private function auth_user()
    {
		if(!(($user = Auth::instance()->get_user()) instanceof Jelly_Model) or !$user->loaded())
        {
            $this->request->response = JSON::error(__("User ID is not specified"));
            return NULL;
        }

        return $user;
    }

	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);

		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 1);

			$field = Jelly::select('field', (int)$id);

			if(!($field instanceof Jelly_Model) or !$field->loaded())	{
				$this->request->response = JSON::error('Записи не найдены.');
				return;
			}

			$field->deleted = true;
			$field->save();
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

	private function update_culture_parent_square(&$cultures, $parent, $square){
		foreach($cultures as &$culture){
			if($culture['id']==$parent){
				$culture['square'] = isset($culture['square']) ? (float)$culture['square']+(float)$square : (float)$square;
				if($culture['parent'])$this->update_culture_parent_square($cultures, $culture['parent'], $square);
				break;;
			}
		}
	}

	public function action_list_predecessors()
    {
		if (is_null($user = $this->auth_user())) return;

		$exclude_groups = Jelly::factory('client_handbook')->get_excludes('glossary_culturegroup', $user->license->id());
		$exclude_names = Jelly::factory('client_handbook')->get_excludes('glossary_culture', $user->license->id());
		$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);

		$data =	Jelly::factory('glossary_culturegroup')->get_tree($user->license->id(), true, $exclude, 'items');

		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);
		$periods = Session::instance()->get('periods', array());
		if(!is_array($periods) || !count($periods)) $periods = array(-1);
		$fields = Jelly::select('field')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->and_where('farm', 'IN', $farms)->and_where('period', 'IN', $periods)->execute()->as_array();

		foreach($fields as $field){
			foreach($data as &$culture){
				if('n'.$field['culture_before']==$culture['id']){
					$culture['square'] = isset($culture['square']) ? (float)$culture['square']+(float)$field['area'] : (float)$field['area'];
					if($culture['parent'])$this->update_culture_parent_square($data, $culture['parent'], $field['area']);
					break;
				}
			}
		}

		for($i=count($data)-1; $i>=0; $i--){
			$do_not_delete = false;
			if(substr($data[$i]['id'], 0, 1)=='g'){
				$do_not_delete = true;
			}else{
				foreach($fields as $field){
					if('n'.$field['culture_before']==$data[$i]['id']) $do_not_delete = true;
				}
			}
			if(!$do_not_delete){
				array_splice($data, $i, 1);
			}
		}

		for($i=count($data)-1; $i>=0; $i--){
			if(substr($data[$i]['id'], 0, 1)=='g'){
				$children = $this->get_culture_group_children($data, $data[$i]['id']);
				if(count($children)){

				}else{
					array_splice($data, $i, 1);
				}
			}
		}

		foreach($data as &$culture){
			if(isset($culture['square']) && (float)$culture['square']>0) $culture['title'] = $culture['title'].'</div>  <div style="color: #666666; width: auto; height: 28px; margin-top:3px;">'.str_replace (',', '.', $culture['square']).' га</div><div>';
		}
		$this->request->response = Json::arr($data, count($data));
	}

	private function get_culture_group_children($cultures, $parent_id){
		$res = array();
		foreach($cultures as $culture) {
			if($culture['parent']==$parent_id && trim($parent_id)){
				if(substr($culture['id'], 0, 1)=='n'){
					$res[] = $culture['id'];
				}else{
					$res = array_merge($res, $this->get_culture_group_children($cultures, $culture['id']));
				}
			}
		}

		return $res;
	}

	public function action_update_keys(){
		$data = arr::get($_POST, 'data', '');
		$data = @json_decode($data, true);

		if($data && is_array($data)){
			foreach($data as $record){
				$field_id = (int)$record['field_id'];
				$key = $record['key'];
				$value = $record['value'];
				$pair = array($key => $value);

				if(!$field_id || !$key) {$this->request->response = JSON::error('Запись не найдена.'); return;}

				$field = Jelly::select('field', $field_id);
				if(!$field instanceof Jelly_Model || !$field->loaded()) {$this->request->response = JSON::error('Запись не найдена.'); return;}

				$field->set($pair);
				$field->save();
			}
		}

		$this->request->response = JSON::success(array('success' => true));
	}

	public function action_generate_fields(){
		$count = 40;

		for($i=0; $i<$count; $i++){
			$field = Jelly::factory('field');
			$field->license = 1;
			$field->crop_rotation_number = 4;
			$field->number = 8;
			$field->sector_number = $i;
			$field->kadastr_area = $i*23;
			$field->area = $i*27;
			$field->culture = rand(1, 6);
			$field->culture_before = rand(1, 6);
			$field->farm = 6;
			$field->period = 1;
			$field->coordinates = 's:109:"["50.'.rand(0, 9).'58604,30.'.rand(0, 9).'86124","50.'.rand(0, 9).'70431,30.'.rand(0, 9).'92303","50.'.rand(0, 9).'6846,30.'.rand(0, 9).'40025","50.'.rand(0, 9).'46775,30.'.rand(0, 9).'0329","50.'.rand(0, 9).'58604,30.'.rand(0, 9).'86124"]";';
			$field->save();
		}
	}
	
	
	public function action_fix_fields_names(){
		$fields = Jelly::select('field')->execute();
		
		foreach($fields as $field){
			$field->save();
		}
	}
}
