<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Abstract extends AC_Controller
{

	protected $model_name 		= '__abstract';
	protected $model_group_name = '__abstract_group';
    protected $group_field      = 'group';
	protected $ignore_addons = false;

    protected $relation_templates = array();

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

	public function before()
	{
		parent::before();

		if($this->model_name == '__abstract')
		{
			throw new Kohana_Exception('Abstract controller should not be called directly!');
		}

        if($this->model_group_name == '__abstract_group' or $this->model_group_name == '')
        {
    		$t = Jelly::meta($this->model_name)->fields($this->group_field);
    		if($t)
    		{
    			$this->model_group_name = $t->foreign['model'];
    		}
        }

		if($this->model_group_name == '__abstract_group')
		{
			throw new Kohana_Exception('Abstract controller should not be called directly!');
		}
	}

//    public function proceed_temporary($tmp, $post)
//    {
//        return $tmp;
//    }
//
//    public function action_temporary()
//    {
//        $session = Session::instance();
//
//        $relation = Arr::get($_POST, 'relation', null);
//        $tmp_id   = Arr::get($_POST, 'tmp_id', null);
//        $_id      = Arr::get($_POST, '_id', null);
//
//        unset($_POST['tmp_id']);
//        unset($_POST['_id']);
//
//        if(!$relation)
//            throw new Kohana_Exception('Relation Id NOT found!');
//
//        $tmp_record = $session->get('tmp_record', null);
//
//        if(!$tmp_record)
//            $tmp_record = array('__model' => $this->model_name);
//        else
//        {
//            if(Arr::get('__model', $tmp_record) != $this->model_name)
//            {
//                $tmp_record = array('__model' => $this->model_name);
//            }
//        }
//
//        array_merge($tmp_record, $_POST);
//
//        $tmp_record = $this->proceed_temporary($tmp_record, $_POST);
//
//        $tpl = str_replace('client_', '', $this->model_name);
//        $view = Twig::factory('client/'.$tpl.'/'.$tpl.'_'.$relation);
//
//        $view->model        = $tmp_record;
//        $view->_id          = $_id;
//        $view->tmp_id       = $tmp_id;
//        $view->relation      = $relation;
//
//        $session->set('tmp_record', $tmp_record);
//
//        $this->request->response = $view->render();
//
//    }

	public $auto_render  = false;

	public function action_index(){}


	public function action_tree(){
		$data =	Jelly::factory($this->model_name)->get_tree('delete_this_shit', $this->group_field);
		$this->request->response = Json::arr($data, count($data));
	}



	public function action_move()
	{

		$target = arr::get($_POST, 'target', '');
         if($target == 'g-2' || $target == '')
            $target = 'g0';
		$target = mb_substr($target, 0, 1)=='g' || mb_substr($target, 0, 1)=='n' ? mb_substr($target, 1) : $target;

		$move_ids = arr::get($_POST, 'move_ids', '');
		$move_ids = explode(',', $move_ids);

		for($i=0; $i<count($move_ids); $i++){

			$id = mb_substr($move_ids[$i], 0, 1)=='g' || mb_substr($move_ids[$i], 0, 1)=='n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
			$model = Jelly::select($this->model_name, (int)$id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error("Запись не найдена");
				return;
			}

			$model->group = $target;
			$model->save();
		}

		$this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
	}


	public function action_read($id = null){
		return $this->action_edit($id, true);
	}

    public function prepare_view(&$view, &$tmp_record, $id = null, $read = false, $parent_id = false)
    {

    }


	public function action_edit($id = null, $read = false, $parent_id = false){

        $model = null;

        if($id){
            $model = Jelly::select($this->model_name)->with($this->group_field)->where(':primary_key', '=', (int)$id)->load();

            if(!($model instanceof Jelly_Model) or !$model->loaded()){
                $this->request->response = JSON::error('Запись не найдена!');
				return;
			}
        }

		$tpl = str_replace('glossary_', '', $this->model_name);

		$view = Twig::factory('glossary/'.$tpl.'/read_'.$tpl);

        // Проверим, или группа принадлежит лицензиату. А то мало ли вдруг чего
        if((int)$parent_id)
        {
            $group = Jelly::select($this->model_group_name)->where(':primary_key', '=', $parent_id)->load();

            if(!($group instanceof Jelly_Model) or !$group->loaded())
            {
                $this->request->response = JSON::error('Группа не найдена!');
				return;
            }
        }

        

        if($model)
        {
            $view->model  			               = $model->as_array();
            $view->model['group']                  = $model->get($this->group_field)->id();
            
            if((int)$parent_id)
            {
           		$view->model['group_name']                  = $group->name;
			}
			
			if($model->loaded() and $model->group->id())
			{
				$view->model['group_name']                  = $model->group->name;
			}
            
            $view->group_field                     = $this->group_field;

			$this->action_getphoto($view, $model->id());

        }

//        $session = Session::instance();
//        $tmp_record = $session->get('tmp_record', null);
//
//        if($tmp_record)
//        {
//            if(Arr::get('__model', $tmp_record) == $this->model_name)
//            {
//                $view->model = $tmp_record;
//                $this->prepare_view($view, $tmp_record, $id, $read, $parent_id);
//            }
//            else
//                $session->delete('tmp_record');
//        }

        if(!$read){
			$view->edit			 	= true;

            if((int)$parent_id)
            {
                    $view->model                           = array();
                    $view->model['group']                = $parent_id;
                    $view->model['group_name']                = $group->name;
                    $view->group_field                     = $this->group_field;
					$view->parent_color                     = $group->color;
            }
		}

        if(!$this->ignore_addons)
		{
			if(!$model)
			{
				if(!(int)$parent_id) {
					$view->model = array();
				}
				$view->model['properties'] = Jelly::factory($this->model_name)->get_properties();
			}
			else
			{
				$view->model['properties']  = $model->get_properties();
			}
        }

        // Уличная магия -- показываем дополнительные поля из модели, которых нет в абстрактном варианте
        $abstract_fields = Jelly::meta('glossary_abstract')->fields();
        $fields          = Jelly::meta($this->model_name)->fields();

        $addon_fields = array();

        foreach($fields as $field)
        {
            $field_found = false;

            foreach($abstract_fields as $abstract)
            {
                if(strtolower($abstract->name) == strtolower($field->name))
                {
                    $field_found = true;
                    break;
                }
            }

            if(!$field_found and !($field instanceof Jelly_Field_Relationship) )
            {
                $xtype = 'textfield';

                // Тут добавлять остальные допполя
                $addon_fields[] = array(
                    'xtype' => $xtype,
                    'name'  => $field->name,
                    'label' => $field->label,
                    'value' => (isset($model) and $model instanceof Jelly_Model) ? $model->get($field->name) : null
                );
            }
        }

        $view->model_fields = $addon_fields;

		$this->inner_edit($view);
		$this->request->response = JSON::reply($view->render());
	}

    public function action_create($parent_id = 0){
        if(array_key_exists(Jelly::meta($this->model_name)->primary_key(), $_POST))
            unset($_POST[Jelly::meta($this->model_name)->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }

	public function action_update(){


        if($id = arr::get($_POST, '_id', NULL)){

			$model = Jelly::select($this->model_name, (int)$id);
			if(!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');

		}else{
			$model = Jelly::factory($this->model_name);
		}

		$model->update_date = time();


		if(!$id)
		{
			$check = Jelly::select($this->model_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int)$_POST[$this->group_field] : 0)->load();
        }
        else
        {
       		$check = Jelly::select($this->model_name)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int)$_POST[$this->group_field] : 0)->where(':primary_key', '!=', (int)$id)->load();
   		}

    	if(($check instanceof Jelly_Model) and $check->loaded())
        {
            $this->request->response = JSON::error('Уже есть такая запись в другой группе ('.$check->group->name.') !');
            return;
        }


		$this->validate_data($_POST);

		$model->set($_POST);

		$model->deleted = 0;

		$model->save();

		$this->action_savephoto($model->id());

        $this->inner_update($model->id());

        // Допполя
        if(!$this->ignore_addons){
			$add = array();

			/*
			insert_property_
			name_insert
			*/

			// Удаляем старые
			$properties = $model->get_properties();

			foreach($properties as $property_id => $property)
			{
				if(!array_key_exists('property_'.$property_id, $_POST))
				{
					$model->delete_property($property_id);
				}
			}

			//Новые допполя
			foreach($_POST as $key => $value)
			{
				if(UTF8::strpos($key, 'insert_property_') !== false)
				{
					$property_id = (int)UTF8::str_ireplace('insert_property_', '', $key);

					$add[$_POST['name_insert_'.$property_id]] = $_POST['insert_property_'.$property_id];
				}
			}

			foreach($add as $key => $value)
			{
				$model->set_property(0, $key, $value);
			}

			// Старые допполя

			foreach($_POST as $key => $value)
			{
				if(UTF8::strpos($key, 'property_') !== false)
				{
					$id = (int)UTF8::str_ireplace('property_', '', $key);

					if(array_key_exists('property_'.$id.'_label', $_POST))
					{
						  $model->set_property($id, $_POST['property_'.$id.'_label'], $_POST['property_'.$id]);
					}
				}
			}
		}

		$culture_id = $model->id();

		$this->request->response = JSON::success(array('script'	   => 'Запись сохранена успешно!',
																		     'url'		  => null,
																		     'success' => true,
																		     'item_id' => $culture_id));
	}


	public function inner_update($id){}
	public function validate_data($data){}
	public function inner_edit(&$view){}


	public function action_delete($id = null)
	{

		$del_ids = arr::get($_POST, 'del_ids', '');
		if($del_ids){
			$del_ids = explode(',', $del_ids);
		} else {
			$del_ids = array();
		}


		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='g' || mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$m = mb_substr($del_ids[$i], 0, 1)=='g' ? $this->model_group_name : $this->model_name;
			if ($id==-2 || $id=='-2') {

				$items_to_delete = Jelly::select($this->model_name)->with('_id')->where('group_id','=',NULL || 0)->execute()->as_array();
				for ($j=0; $j<count($items_to_delete); $j++) {
					$item = Jelly::select($this->model_name, (int)($items_to_delete[$j]['_id']));
					$item->delete();
				}

			} else {

				$model = Jelly::select($m, (int)$id);

				if(!($model instanceof Jelly_Model) or !$model->loaded())	{
					$this->request->response = JSON::error('Записи не найдены.');
					return;
				}

				$model->delete();
			}
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

}
