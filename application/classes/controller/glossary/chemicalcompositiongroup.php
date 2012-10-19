<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_ChemicalCompositionGroup extends Controller_Glossary_AbstractGroup {

    protected $model_name = 'glossary_chemicalcomposition';
    protected $model_group_name = 'glossary_chemicalcompositiongroup';
    
    
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
            
            //----------
            // а теперь делаем в дочерних названиях те же ддопсвойства что и в группе
            
            $group_properties = $model->get_properties();
            $properties_names = array();
            foreach($group_properties as $p){
                $properties_names[] = $p['value'];
            }
            
            
            $children = Jelly::select($this->model_name)->
                    where('deleted','=',FALSE)->
                    and_where('group','=',(int)$model->id())->
                    execute();
            
            foreach($children as $child){
                $contents = Jelly::select('glossary_chemicalcompositioncontent')->
                    where('chemicalcomposition','=',(int)$child->id())->
                    execute();
                
                $contents_names = array();
                foreach($contents as $c){
                    $contents_names[] = $c->text;
                }
                
                
                
                foreach($contents as $content){
                    if(!in_array($content->text, $properties_names)){
                        $content->delete();
                    }
                }
                
                foreach($group_properties as $gp){
                    if(!in_array($gp['value'], $contents_names)){
                        $new_content = Jelly::factory('glossary_chemicalcompositioncontent')->set(array(
                            'chemicalcomposition' => $child->id(),
                            'color' => $gp['name'],
                            'text' => $gp['value'],
                            'first_units' => 67,
                            'second_units' => 68
                        ))->save();
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
}