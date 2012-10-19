<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_AbstractGroup extends AC_Controller {

    protected $model_name = '__abstract';
    protected $model_group_name = '__abstact_group';
    protected $items_field = 'items';
    protected $ignore_addons = false;
    public $auto_render = false;

    public function action_photo() {
        if (!$_POST or !$_FILES)
            throw new Kohana_Exception(__('POST method required. Please, contact developers'));

        $image = Arr::get($_FILES, 'image', null);

        if (!$image)
            throw new Kohana_Exception(__('No image given'));

        $name = 'original_' . Text::random('alnum', 15) . '.jpg';
        $filename = Upload::save($image, $name, DOCROOT . 'media/pictures/', 0777);

        $this->request->response = json_encode(array('success' => true, 'image' => '/media/pictures/' . $name));
    }

    public function action_delphoto() {

        if (!$_POST)
            throw new Kohana_Exception(__('POST method required. Please, contact developers'));

        $image_path = Arr::get($_POST, 'img_path', null);

        if (!$image_path)
            throw new Kohana_Exception(__('No image given'));

        $dirs = explode('/', $image_path);
        $filename = $dirs[count($dirs) - 1];
        $subdir = $dirs[count($dirs) - 2];

        if (is_file(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/' . $filename)) {

            unlink(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/' . $filename);
        }

        if (is_file(DOCROOT . 'media/pictures/' . $filename)) { // удаляем превью
            unlink(DOCROOT . 'media/pictures/' . $filename);
        }

        $this->request->response = json_encode(array('success' => true, 'image' => $filename));
    }

    public function action_savephoto($id) {

        // image manipulating
        $photo = Arr::get($_POST, 'photo', null);
        if ($photo != '') {
            $photos = explode(',', $photo);
            foreach ($photos as $photo) {
                // Посмотрим, или это временное что-то
                if (preg_match('#(.*)original_(.*).jpg#', $photo)) {
                    // Да, надо перенести куда следует
                    $subdir = floor($id / 2000);

                    if (!is_dir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/')) {
                        @mkdir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/');
                    }

                    if (!is_dir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir)) {
                        @mkdir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir, 0777);
                    }
                    if (is_file(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/item_' . $id . '.jpg')) {
                        rename(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/item_' . $id . '.jpg', DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/item_' . $id . '_changed_' . time() . '_' . Text::random('alnum', 15) . '.jpg');
                    }

                    if (is_file(DOCROOT . $photo)) {
                        copy(DOCROOT . $photo, DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/item_' . $id . '.jpg');
                        chmod(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/item_' . $id . '.jpg', 0777);
                    }
                }
                // Если же нет - тогда делать ничего не надо. И это прекрасно!
            }
        }
    }

    public function action_getphoto(&$view, $id) {
        $subdir = floor($id / 2000);
        $view->model['images'] = array();
        if (is_dir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir)) {
            $files = scandir(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir);
            foreach ($files as $file) {
                if (is_file(DOCROOT . Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/' . $file) && (!(strpos($file, 'item_' . $id . '_') === FALSE) || !(strpos($file, 'item_' . $id . '.') === FALSE) )) {
                    $view->model['images'][] = Kohana::config('upload.path') . '/' . $this->model_group_name . '/' . $subdir . '/' . $file;
                }
            }
        }
    }

    public function action_index() {
        
    }

    public function action_read($id = null) {
        return $this->action_edit($id, true);
    }

    public function before() {
        parent::before();
        if ($this->model_group_name == '__abstract_group') {
            throw new Kohana_Exception('Abstract controller should not be called directly!');
        }

        if ($this->model_name == '__abstact' or $this->model_name == '') {
            $t = Jelly::meta($this->model_group_name)->fields($this->items_field);
            if ($t) {
                $this->model_name = $t->foreign['model'];
            }
        }

        if ($this->model_name == '__abstract') {
            throw new Kohana_Exception('Abstract controller should not be called directly!');
        }
    }

    public function action_edit($id = null, $read = false, $parent_id = false) {

        $group = null;

        if ($id && $id != -2) {
            $group = Jelly::select($this->model_group_name)->with('parent')->load((int) $id);
            if (!($group instanceof Jelly_Model) or !$group->loaded()) {
                $this->request->response = JSON::error('Не найдена Запись');
                return;
            }
        }

        $tpl = str_replace('glossary_', '', $this->model_group_name);
        $folder = str_replace('group', '', $tpl);
        $view = Twig::factory('glossary/' . $folder . '/read_' . $tpl);

        if ($id)
            $view->id = $id;

        if (!$read) {
            $view->edit = true;
            $view->parent_id = $parent_id !== false ? $parent_id : ($group ? $group->parent->id() : 0);
            $view->hasChildren = false;
        }

        if ($group) {
            $view->model = $group->as_array();
        } else {
            $view->model = array();
        }

        if ($id) {
            $this->action_getphoto($view, $id);
        }

        $view->fake_group = $id == -2;

        if (!$this->ignore_addons) {
            if (!$group) {
                if (!(int) $parent_id) {
                    $view->model = array();
                }
                $view->model['properties'] = Jelly::factory($this->model_name)->get_properties();
            } else {
                $view->model['properties'] = $group->get_properties();
            }
        }

        $this->request->response = JSON::reply($view->render());
    }

    public function action_create($parent_id = 0) {
        if (array_key_exists(Jelly::meta($this->model_group_name)->primary_key(), $_POST))
            unset($_POST[Jelly::meta($this->model_group_name)->primary_key()]);

        return $this->action_edit(null, false, $parent_id);
    }

    public function action_tree() {

        $user = Auth::instance()->get_user();

        $exclude = arr::get($_GET, 'exclude', '');
        $exclude = explode(',', $exclude);
        if (!$exclude[0] || !$exclude) {
            $exclude = array();
        }
        for ($i = 0; $i < count($exclude); $i++) {
            //  $exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
            $exclude[$i] = mb_substr($exclude[$i], 0, 1) == 'g' ? mb_substr($exclude[$i], 1) : $exclude[$i];
        }

        $with_cultures = Arr::get($_GET, 'both_trees', false);

        $data = Jelly::factory($this->model_group_name)->get_tree('delete_this_shit', $with_cultures, $exclude, $this->items_field);

        $this->request->response = Json::arr($data, count($data));
    }

    public function action_move() {

        $target = arr::get($_POST, 'target', '');
        if ($target == 'g-2' || $target == '')
            $target = 'g0';
        $target = mb_substr($target, 0, 1) == 'g' || mb_substr($target, 0, 1) == 'n' ? mb_substr($target, 1) : $target;

        $move_ids = arr::get($_POST, 'move_ids', '');
        $move_ids = explode(',', $move_ids);

        for ($i = 0; $i < count($move_ids); $i++) {

            $id = mb_substr($move_ids[$i], 0, 1) == 'g' || mb_substr($move_ids[$i], 0, 1) == 'n' ? mb_substr($move_ids[$i], 1) : $move_ids[$i];
            $model = Jelly::select($this->model_group_name, (int) $id);

            if (!($model instanceof Jelly_Model) or !$model->loaded()) {
                $this->request->response = JSON::error('Группа не найдена.');
                return;
            }

            $model->parent = $target;
            $model->save();
        }

        $this->request->response = JSON::success(array('script' => 'Moved', 'url' => null, 'success' => true));
    }

    public function action_delete($id = null) {

        $del_ids = arr::get($_POST, 'del_ids', '');
        $del_ids = explode(',', $del_ids);

        for ($i = 0; $i < count($del_ids); $i++) {

            $id = mb_substr($del_ids[$i], 0, 1) == 'g' || mb_substr($del_ids[$i], 0, 1) == 'n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

            $m = mb_substr($del_ids[$i], 0, 1) == 'n' ? $this->model_name : $this->model_group_name;

            if ($id == -2 || $id == '-2') {

                $items_to_delete = Jelly::select($this->model_name)->with('_id')->where('group_id', 'IS', NULL)->execute()->as_array();
                $items_to_delete = array_merge($items_to_delete, Jelly::select($this->model_name)->with('_id')->where('group_id', '=', 0)->execute()->as_array());
                for ($j = 0; $j < count($items_to_delete); $j++) {
                    $item = Jelly::select($this->model_name, (int) ($items_to_delete[$j]['_id']));
                    $item->delete();
                }
            } else {

                $model = Jelly::select($m, (int) $id);

                if (!($model instanceof Jelly_Model) or !$model->loaded()) {
                    $this->request->response = JSON::error('Записи не найдены.');
                    return;
                }

                Jelly::delete($this->model_name)->where('group', '=', $model->id())->execute();

                $model->delete();
            }
        }
        $this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
    }

    public function action_update() {

        $user = Auth::instance()->get_user();

        $values = array('name', 'color', 'parent');
        if ($group_id = arr::get($_POST, '_id', NULL)) {
            $model = Jelly::select($this->model_group_name, (int) $group_id);
        } else {
            $model = Jelly::factory($this->model_group_name);
        }

        $this->validate_data($_POST);

        $model->set($_POST);

        $model->update_date = time();
        $_POST['parent'] = (int) Arr::get($_POST, 'parent', 0);
        $model->set(Arr::extract($_POST, $values));
        $model->name = trim($model->name);

        $model->save();

        $this->action_savephoto($model->id());

        $group_id = $model->id();

        $this->inner_update($group_id);

        // Допполя
        if (!$this->ignore_addons) {
            $add = array();

            // insert_property_
            // name_insert
            // Удаляем старые
            $properties = $model->get_properties();
            
            foreach ($properties as $property_id => $property) {
                if (!array_key_exists('property_' . $property_id, $_POST)) {
                    $model->delete_property($property_id);
                }
            }

            //Новые допполя
            foreach ($_POST as $key => $value) {
                if (UTF8::strpos($key, 'insert_property_') !== false) {
                    $property_id = (int) UTF8::str_ireplace('insert_property_', '', $key);

                    $add[$_POST['name_insert_' . $property_id]] = $_POST['insert_property_' . $property_id];
                }
            }

            foreach ($add as $key => $value) {
                $model->set_property(0, $key, $value, $_POST['insert_property_' . $property_id.'_order']);
            }

            // Старые допполя
            foreach ($_POST as $key => $value) {
                if (UTF8::strpos($key, 'property_') !== false) {
                    $id = (int) UTF8::str_ireplace('property_', '', $key);

                    if (array_key_exists('property_' . $id . '_label', $_POST)) {
                        $model->set_property($id, $_POST['property_' . $id . '_label'], $_POST['property_' . $id], $_POST['property_' . $id.'_order']);
                    }
                }
            }
        }


        //если редактировали группу "без группы", то всех безхозных чаилдов цепляем к ней
        if (Arr::get($_POST, 'fake_group', false)) {
            $db = Database::instance();
            $db->query(DATABASE::UPDATE, 'UPDATE ' . Jelly::meta($this->model_name)->table() . ' SET group_id = ' . $group_id . ' WHERE (group_id=0 OR group_id IS NULL) AND deleted = 0', true);
        }

        $this->request->response = JSON::success(array('script' => "Группа сохранена успешно!",
                    'url' => null,
                    'success' => true,
                    'item_id' => $group_id));
    }

    public function inner_update($id) {
        
    }

    public function validate_data($data) {
        
    }

}