<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Target_SzrGroup extends Controller_Glossary_AbstractGroup
{
    protected $model_name = 'glossary_szr_target';
    protected $model_group_name = 'glossary_szrgroup';
    protected $items_field = 'target_szr';
    
    protected $SYSTEM_SZR_GROUPS = array('g1001','g1002','g1003','g1004');

	public function action_delete($id = null) {

            $del_ids = arr::get($_POST, 'del_ids', '');
            $del_ids = explode(',', $del_ids);
            if (count(array_intersect($del_ids, $this->SYSTEM_SZR_GROUPS))) {
                $this->request->response = JSON::error('Нельзя удалять системные группы СЗР.');
                return;
            }

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

                if (in_array('g' . $group_id, $this->SYSTEM_SZR_GROUPS)) {
                    $this->request->response = JSON::error('Нельзя редактировать системные группы СЗР.');
                    return;
                }



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
            $group_id = $model->id();

            $this->inner_update($group_id);


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
}

