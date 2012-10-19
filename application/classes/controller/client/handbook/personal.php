<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Handbook_Personal extends Controller_Glossary_Abstract {

    protected $model_name = 'client_handbook_personal';
    protected $model_group_name = 'client_handbook_personalgroup';

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

        if (is_file(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/' . $filename)) {
            unlink(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/' . $filename);
        }

        if (is_file(DOCROOT . 'media/pictures/' . $filename)) { // удаляем превью
            unlink(DOCROOT . 'media/pictures/' . $filename);
        }



        $this->request->response = json_encode(array('success' => true, 'image' => $filename));
    }

    private function auth_user() {
        if (!(($user = Auth::instance()->get_user()) instanceof Jelly_Model) or !$user->loaded()) {
            $this->request->response = JSON::error(__("User ID is not specified"));
            return NULL;
        }

        return $user;
    }

    public function action_edit($id = null, $read = false, $parent_id = false) {
        if (is_null($user = $this->auth_user()))
            return;

        $model = null;

        if ($id) {
            $model = Jelly::select('client_handbook_personal')->with('group')->where(':primary_key', '=', (int) $id)->load();

            if (!($model instanceof Jelly_Model) or !$model->loaded()) {
                $this->request->response = JSON::error('Запись не найдена!');
                return;
            }
        }

        $view = Twig::factory('client/handbook/personal/read_personal');

        $view->model = array();

        if ($model) {
            $view->model = $model->as_array();
            $view->model['group'] = $model->group->id();
            $view->model['farm'] = $model->farm;
            $view->group_field = 'group';

            $subdir = floor($model->id() / 2000);
            $view->model['images'] = array();

            if (is_dir(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir)) {
                $files = scandir(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir);
                foreach ($files as $file) {
                    if (is_file(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/' . $file) && !(strpos($file, 'user_' . $model->id()) === FALSE)) {
                        $view->model['images'][] = Kohana::config('upload.path') . '/users/' . $subdir . '/' . $file;
                    }
                }
            }


            $date = $view->model['date'];
            $birth_date = $view->model['birth_date'];

            $view->model['date'] = array('year' => date('Y', $date), 'month' => date('m', $date), 'day' => date('d', $date));
            $view->model['birth_date'] = array('year' => date('Y', $birth_date), 'month' => date('m', $birth_date), 'day' => date('d', $birth_date));
        }

        if (!$read) {
            $view->edit = true;

            if ((int) $parent_id) {
                $view->model = array();
                $view->model['group'] = $parent_id;
                $view->group_field = 'group';
                $parent = Jelly::select('client_handbook_personalgroup', (int) $parent_id);
                $view->parent_color = $parent->color;
            }
        }
        
        
        // http://jira.ardea.kiev.ua/browse/AGC-1487
        // http://jira.ardea.kiev.ua/browse/AGC-1496
//        $view->model['login'] = ''; 
//        $view->model['password'] = '';
//        $view->model['fake_name'] = '';
//        $view->model['surname'] = '';
//        $view->model['patronymic'] = '';

        if ($model) {
            $view->model['properties'] = $model->get_properties();
            $view->model['chief_properties'] = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonal_chief')->execute()->as_array();
            $view->model['chief2_properties'] = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonal_chief2')->execute()->as_array();
            $view->model['chief3_properties'] = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonal_chief3')->execute()->as_array();
            $view->model['chief4_properties'] = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonal_chief4')->execute()->as_array();
            $view->model['end_properties'] = Jelly::select('extraproperty')->where('object', '=', $model->id())->and_where('block', '=', 'handbookpersonal_end')->execute()->as_array();

            if ($model->user->username != '' and $model->user->is_active) {
                $view->model['login'] = $model->user->username;
            }

            if ($model->user->password != '') {
                $view->model['password'] = $model->user->password_text;
            }

            if ($model->user->first_name != '') {
                $view->model['fake_name'] = $model->user->first_name;
            }

            if ($model->user->middle_name != '') {
                $view->model['patronymic'] = $model->user->middle_name;
            }

            if ($model->user->last_name != '') {
                $view->model['surname'] = $model->user->last_name;
            }
        }

        $fields = Jelly::meta($this->model_name)->fields();
        $addon_fields = array();
        $field_types = array();


        foreach ($fields as $field) {
            if (isset($field_types[$field->name]))
                $addon_fields[] = array(
                    'xtype' => $field_types[$field->name],
                    'name' => $field->name,
                    'label' => $field->label,
                    'value' => (isset($model) and $model instanceof Jelly_Model) ? $model->get($field->name) : null
                );
        }

        $view->model_fields = $addon_fields;


        $farms = Jelly::select('farm')->
                where('deleted', '=', false)->
                and_where('license', '=', $user->license->id())->
                //and_where('is_group', '=', false)->
                order_by('name', 'asc');

        $session = Session::instance();
        $s_farms = $session->get('farms');
        $s_farm_groups = $session->get('farm_groups');

        if (!is_array($s_farms))
            $s_farms = array();

        if (!is_array($s_farm_groups))
            $s_farm_groups = array();

        $farm_ids = array_unique(array_merge($s_farms, $s_farm_groups));

        if (count($s_farms) or count($s_farm_groups)) {
            $farms->where(':primary_key', 'IN', $farm_ids);
        }

        $farms = $farms->execute()->as_array();

        $selected_farm = Arr::get($_GET, 'selected', '');
        $selected_farm_name = '';
        $selected_farm_color = '';
        $selected_farm_is_group = '';

        if ($selected_farm != '') {
            $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int) $selected_farm);

            if (($selected_farm_obj instanceof Jelly_Model)
                    and $selected_farm_obj->loaded()) {
                $selected_farm_name = $selected_farm_obj->name;
                $selected_farm_color = $selected_farm_obj->color;
                $selected_farm_is_group = $selected_farm_obj->is_group;
            }
        }

        $last_farm = $session->get('last_create_farm', '');
        if ($selected_farm == '') {
            if ($last_farm != '' && in_array($selected_farm, $farm_ids)) {
                $selected_farm = $last_farm;

                $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int) $selected_farm);

                if (($selected_farm_obj instanceof Jelly_Model)
                        and $selected_farm_obj->loaded()) {
                    $selected_farm_name = $selected_farm_obj->name;
                    $selected_farm_color = $selected_farm_obj->color;
                    $selected_farm_is_group = $selected_farm_obj->is_group;
                }
            } else {
                if (count($farms)) {
                    $selected_farm = $farms[0]['_id'];
                    $selected_farm_name = $farms[0]['name'];
                    $selected_farm_color = $farms[0]['color'];
                    $selected_farm_is_group = $farms[0]['is_group'];
                }
            }
        }

        $view->selected_farm = $selected_farm;
        $view->selected_farm_name = $selected_farm_name;
        $view->selected_farm_color = $selected_farm_color;
        $view->selected_farm_is_group = $selected_farm_is_group;

        $view->farms = $farms;

        $view->salary_units = Jelly::factory('glossary_units')->getUnits('personal_payment');
        $view->work_hour_cost_units = Jelly::factory('glossary_units')->getUnits('work_hour_cost');

        $this->request->response = JSON::reply($view->render());
    }

    public function action_update() {
        if (is_null($user = $this->auth_user()))
            return;

        $farm = Arr::get($_POST, 'farm', null);

        $license_id = Auth::instance()->get_user()->license->id();

        $periods = Session::instance()->get('periods');
        if (!count($periods))
            $periods = array(-1);
        $_POST['period'] = (int) $periods[0];


        if ($id = arr::get($_POST, '_id', NULL)) {

            $model = Jelly::select($this->model_name, (int) $id);
            if (!($model instanceof Jelly_Model) or !$model->loaded())
                throw new Kohana_Exception('Record Not Found!');
        }else {
            $model = Jelly::factory($this->model_name);

            if ($farm) {
                $selected_farm_obj = Jelly::select('farm')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->load((int) $farm);
                if (($selected_farm_obj instanceof Jelly_Model) and $selected_farm_obj->loaded()) {
                    Session::instance()->set('last_create_farm', (int) $farm);
                }
                else
                    throw new Kohana_Exception('Хозяйства не существует');
            }
        }

        $model->update_date = time();

        $this->validate_data($_POST);

        if ((int) Arr::get($_POST, 'group', null) <= 0) {
            $_POST['group'] = null;
        }

        $model->set($_POST);

        $model->deleted = 0;

        $model->license = Auth::instance()->get_user()->license->id();
        $model->position = (int) (arr::get($_POST, 'position_id', 0));
        $model->date = strtotime(ACDate::convertMonth($_POST['date']));
        $model->birth_date = strtotime(ACDate::convertMonth($_POST['birth_date']));

        $group_id = (int) Arr::get($_POST, 'group', null);

        if ($group_id) {
            $group = Jelly::select('client_handbook_personalgroup')->where('license', '=', Auth::instance()->get_user()->license->id())->load((int) $group_id);

            if ($group instanceof Jelly_Model and $group->loaded()) {
                $model->salary = $group->average_salary;
                $model->salary_units = $group->average_salary_units;
            }
        }

        $login = trim(Arr::get($_POST, 'login', null));
        $password = trim(Arr::get($_POST, 'password', null));
        
        // http://jira.ardea.kiev.ua/browse/AGC-1487
        // http://jira.ardea.kiev.ua/browse/AGC-1496
        if($login && strlen($login)<3){
            throw new Kohana_Exception('Минимальная длина значения поля Логин должна быть не мeнее 3 символов');
        }
        
        if ($login != '' and $password != '') {
            if (!$model->user->id()) {

                // Посчитаем бойцов - не вылазим ли мы за лимиты
                $count = Jelly::select('user')->where('license', '=', (int) $license_id)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('is_active', '=', 1)->count();

                $license = Jelly::select('license', Auth::instance()->get_user()->license->id());

                if (!($license instanceof Jelly_Model) or !$license->loaded()) {
                    throw new Kohana_Exception('License not found');
                }

                if ($count + 1 > $license->max_users) {
                    throw new Kohana_Exception('Внимание! Вы достигли ограничения лицензии на количество пользователей!');
                }
            }
        }


        if ($login != '') {
            if (!$model->user->id()) {
                $check = Jelly::select('user')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('username', '=', $login)->
                        execute()->
                        count();
            } else {
                $check = Jelly::select('user')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                        where('username', '=', $login)->
                        where(':primary_key', '!=', (int) ($model->user->id()))->
                        execute()->
                        count();
            }

            if ($login && $check > 0) {
                $this->request->response = JSON::error('Уже есть запись с таким логином !');
                return;
            }
        }

        $model->save();

        $model = Jelly::select($this->model_name)->with('user')->load($model->id());

        if ($login != '' and $password != '') {
            if (!$model->user->id()) {
                $user = Jelly::factory('user');
                $user->is_root = 0;
                $user->is_active = 1;
                $user->deleted = 0;

                $user->username = $login;
                $user->password = $password;
                $user->password_confirm = $password;
                $user->password_text = $password;
                $user->email = trim(Arr::get($_POST, 'email', ''));
                $user->license = $license_id;

                $user->name = Arr::get($_POST, 'fake_name', '');
                $user->last_name = Arr::get($_POST, 'surname', '');
                $user->middle_name = Arr::get($_POST, 'patronymic', '');

                $user->save();

                $model->user = $user->id();
                $model->save();

                if ($user->email != '') {
                    Controller_Client_Farm::send_welcome_email($user, $user->last_name . ' ' . $user->first_name . ' ' . $user->middle_name);
                }
            } else {
                if ($login != $model->user->username) {
                    $model->user->username = $login;
                }

                if ($password != $model->user->password_text) {
                    $model->user->password = $password;
                    $model->user->password_confirm = $password;
                    $model->user->password_text = $password;
                }

                $model->user->is_active = 1;
                $model->user->deleted = 0;

                $model->user->name = Arr::get($_POST, 'fake_name', '');
                $model->user->last_name = Arr::get($_POST, 'surname', '');
                $model->user->middle_name = Arr::get($_POST, 'patronymic', '');


                $model->user->save();
            }
        } else {
            //'ac'.Text::random('alpha', 15).'@forbidden.agroclever.com'
            if (!$model->user->id()) {
                $user = Jelly::factory('user');
                $user->is_root = 0;
                $user->is_active = 0;
                $user->deleted = 0;

                $user->username = $login == '' ? 'ac' . Text::random('alpha', 15) . '@forbidden.agroclever.com' : $login;
                $user->password = $password == '' ? md5($user->username) : $password;
                $user->password_confirm = $password == '' ? md5($user->username) : $password;
                $user->password_text = $password == '' ? '' : $password;
                $user->email = Arr::get($_POST, 'email', '');
                $user->license = $license_id;

                $user->name = Arr::get($_POST, 'fake_name', '');
                $user->last_name = Arr::get($_POST, 'surname', '');
                $user->middle_name = Arr::get($_POST, 'patronymic', '');

                $user->save();

                $model->user = $user->id();
                $model->save();
            } else {
                if ($login == '') {
                    $model->user->username = 'ac' . Text::random('alpha', 15) . '@forbidden.agroclever.com';
                } else {
                    $model->user->username = $login;
                }

                if ($password == '') {
                    $model->user->password_text = $password;
                } else {
                    $user->password = $password;
                    $user->password_confirm = $password;
                    $user->password_text = $password;
                }

                $model->user->name = Arr::get($_POST, 'fake_name', '');
                $model->user->last_name = Arr::get($_POST, 'surname', '');
                $model->user->middle_name = Arr::get($_POST, 'patronymic', '');

                $model->user->save();
            }
            
            
            // http://jira.ardea.kiev.ua/browse/AGC-1487
            // http://jira.ardea.kiev.ua/browse/AGC-1496
            if(!$login){
                $model->user = 0;
                $model->save();
            }
        }


        // image manipulating
        $photo = Arr::get($_POST, 'photo', null);
        if ($photo != '') {
            $photos = explode(',', $photo);
            foreach ($photos as $photo) {
                // Посмотрим, или это временное что-то
                if (preg_match('#(.*)original_(.*).jpg#', $photo)) {
                    // Да, надо перенести куда следует
                    $subdir = floor($model->id() / 2000);

                    if (!is_dir(DOCROOT . Kohana::config('upload.path') . '/users/')) {
                        @mkdir(DOCROOT . Kohana::config('upload.path') . '/users/');
                    }

                    if (!is_dir(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir)) {
                        @mkdir(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir, 0777);
                    }
                    if (is_file(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/user_' . $model->id() . '.jpg')) {
                        rename(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/user_' . $model->id() . '.jpg', DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/user_' . $model->id() . '_changed_' . time() . '_' . Text::random('alnum', 15) . '.jpg');
                    }

                    if (is_file(DOCROOT . $photo)) {
                        copy(DOCROOT . $photo, DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/user_' . $model->id() . '.jpg');
                        chmod(DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir . '/user_' . $model->id() . '.jpg', 0777);
                    }
                }
                // Если же нет - тогда делать ничего не надо. И это прекрасно!
            }
        }

        $this->inner_update($model->id());

        // Допполя
        if (!$this->ignore_addons) {
            $add = array();

            /*
              insert_property_
              name_insert
             */

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
                $model->set_property(0, $key, $value);
            }

            // Старые допполя

            foreach ($_POST as $key => $value) {
                if (UTF8::strpos($key, 'property_') !== false) {
                    $id = (int) UTF8::str_ireplace('property_', '', $key);

                    if (array_key_exists('property_' . $id . '_label', $_POST)) {
                        $model->set_property($id, $_POST['property_' . $id . '_label'], $_POST['property_' . $id]);
                    }
                }
            }
        }


        $this->request->response = JSON::success(array(
                    'script' => 'Запись сохранена успешно!',
                    'url' => null,
                    'success' => true,
                    'item_id' => $model->id()
                ));
    }

    public function action_tree() {
        $user = Auth::instance()->get_user();

        $farm_id = (int) Arr::get($_GET, 'farm_id', null);

        $data = Jelly::factory($this->model_name)->get_tree($user->license->id(), $this->group_field, array(), true, $farm_id);
        $this->request->response = Json::arr($data, count($data));
    }

    public function inner_update($contragent_id) {

        $addons = array();
        foreach ($_POST as $key => $value) {
            if (UTF8::strpos($key, 'chief_prop_') !== false and UTF8::strpos($key, '_label') === false) {
                $addons[] = array('_id' => (int) UTF8::str_ireplace('chief_prop_', '', $key),
                    'name' => arr::get($_POST, $key . '_label', ''),
                    'value' => $value);
            }
            if (UTF8::strpos($key, 'insert_chief_property_') !== false) {
                $addons[] = array('name' => arr::get($_POST, 'name_chief_insert_' . UTF8::str_ireplace('insert_chief_property_', '', $key), ''),
                    'value' => $value);
            }
        }
        Jelly::factory('extraproperty')->updateOldFields((int) $contragent_id, 'handbookpersonal_chief', $addons);

        $addons = array();
        foreach ($_POST as $key => $value) {
            if (UTF8::strpos($key, 'chief2_prop_') !== false and UTF8::strpos($key, '_label') === false) {
                $addons[] = array('_id' => (int) UTF8::str_ireplace('chief2_prop_', '', $key),
                    'name' => arr::get($_POST, $key . '_label', ''),
                    'value' => $value);
            }
            if (UTF8::strpos($key, 'insert_chief2_property_') !== false) {
                $addons[] = array('name' => arr::get($_POST, 'name_chief2_insert_' . UTF8::str_ireplace('insert_chief2_property_', '', $key), ''),
                    'value' => $value);
            }
        }
        Jelly::factory('extraproperty')->updateOldFields((int) $contragent_id, 'handbookpersonal_chief2', $addons);

        $addons = array();
        foreach ($_POST as $key => $value) {
            if (UTF8::strpos($key, 'chief3_prop_') !== false and UTF8::strpos($key, '_label') === false) {
                $addons[] = array('_id' => (int) UTF8::str_ireplace('chief3_prop_', '', $key),
                    'name' => arr::get($_POST, $key . '_label', ''),
                    'value' => $value);
            }
            if (UTF8::strpos($key, 'insert_chief3_property_') !== false) {
                $addons[] = array('name' => arr::get($_POST, 'name_chief3_insert_' . UTF8::str_ireplace('insert_chief3_property_', '', $key), ''),
                    'value' => $value);
            }
        }
        Jelly::factory('extraproperty')->updateOldFields((int) $contragent_id, 'handbookpersonal_chief3', $addons);

        $addons = array();
        foreach ($_POST as $key => $value) {
            if (UTF8::strpos($key, 'chief4_prop_') !== false and UTF8::strpos($key, '_label') === false) {
                $addons[] = array('_id' => (int) UTF8::str_ireplace('chief4_prop_', '', $key),
                    'name' => arr::get($_POST, $key . '_label', ''),
                    'value' => $value);
            }
            if (UTF8::strpos($key, 'insert_chief4_property_') !== false) {
                $addons[] = array('name' => arr::get($_POST, 'name_chief4_insert_' . UTF8::str_ireplace('insert_chief4_property_', '', $key), ''),
                    'value' => $value);
            }
        }
        Jelly::factory('extraproperty')->updateOldFields((int) $contragent_id, 'handbookpersonal_chief4', $addons);


        $addons = array();
        foreach ($_POST as $key => $value) {
            if (UTF8::strpos($key, 'end_prop_') !== false and UTF8::strpos($key, '_label') === false) {
                $addons[] = array('_id' => (int) UTF8::str_ireplace('end_prop_', '', $key),
                    'name' => arr::get($_POST, $key . '_label', ''),
                    'value' => $value);
            }
            if (UTF8::strpos($key, 'insert_end_property_') !== false) {
                $addons[] = array('name' => arr::get($_POST, 'name_end_insert_' . UTF8::str_ireplace('insert_end_property_', '', $key), ''),
                    'value' => $value);
            }
        }
        Jelly::factory('extraproperty')->updateOldFields((int) $contragent_id, 'handbookpersonal_end', $addons);
    }

    public function action_delete($id = null) {

        $del_ids = arr::get($_POST, 'del_ids', '');
        if ($del_ids) {
            $del_ids = explode(',', $del_ids);
        } else {
            $del_ids = array();
        }


        for ($i = 0; $i < count($del_ids); $i++) {

            $id = mb_substr($del_ids[$i], 0, 1) == 'g' || mb_substr($del_ids[$i], 0, 1) == 'n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

            $m = mb_substr($del_ids[$i], 0, 1) == 'g' ? $this->model_group_name : $this->model_name;
            if ($id == -2 || $id == '-2') {

                $items_to_delete = Jelly::select($this->model_name)->with('_id')->where('group_id', '=', NULL || 0)->execute()->as_array();

                for ($j = 0; $j < count($items_to_delete); $j++) {
                    $item = Jelly::select($this->model_name, (int) ($items_to_delete[$j]['_id']));
                    $item->user->delete();
                    $item->delete();
                }
            } else {

                $model = Jelly::select($m, (int) $id);

                if (!($model instanceof Jelly_Model) or !$model->loaded()) {
                    $this->request->response = JSON::error('Записи не найдены.');
                    return;
                }
                $model->user->delete();
                $model->delete();
            }
        }

        $this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
    }

}
