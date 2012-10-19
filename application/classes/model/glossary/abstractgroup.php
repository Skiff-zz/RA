<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Glossary_AbstractGroup extends Jelly_Model {

    public static function initialize(Jelly_Meta $meta, $table_name = '__abstract_group', $items_model = '__abstract') {

        if ($table_name == '__abstract_group') {
            throw new Kohana_Exception('Abstract Client Group Model Should NOT be initialized directly!');
        }

        $myself = str_replace('model_', '', strtolower(get_called_class()));

        $meta->table($table_name)
                ->fields(array(
                    // Первичный ключ
                    '_id' => Jelly::field('Primary'),
                    'name' => Jelly::field('String', array('label' => 'Название')),
//				'license'	    => Jelly::field('BelongsTo',array(
//										'foreign'	=> 'license',
//										'column'	=> 'license_id',
//										'label'		=> 'Лицензия'
//								)),
                    'parent' => Jelly::field('BelongsTo', array(
                        'foreign' => $myself,
                        'column' => 'parent_id',
                        'label' => 'Родительский элемент'
                    )),
                    'items' => Jelly::field('HasMany', array(
                        'foreign' => $items_model . '.group',
                        'label' => 'Элементы группы',
                    )),
                    'deleted' => Jelly::field('Boolean', array('label' => 'Удален')),
                    'path' => Jelly::field('String', array('label' => 'Путь')),
                    'color' => Jelly::field('String', array('label' => 'Цвет',
                        'rules' => array(
                            'max_length' => array(6),
                            'regex' => array('/^[a-fA-F0-9]+$/ui')
                            ))),
                    'update_date' => Jelly::field('Integer', array('label' => 'Дата последнего изменения',
                        'rules' => array(
                            'not_empty' => NULL
                            ))
                    ),
                ));
    }

    public function save($key = NULL) {
        if (array_key_exists('parent', $this->_changed)) {
//			$license_id = null;
//            $license_id = array_key_exists('license', $this->_changed) ? $this->_changed['license'] : $this->_original['license'];

            if ((int) $this->_changed['parent']) {
                $parent = Jelly::select($this->meta()->model())->where('_id', '=', (int) $this->_changed['parent'])->where('deleted', '=', 0)->load();

                if (!($parent instanceof Jelly_Model) or !$parent->loaded()) {
                    unset($this->_changed['parent']);
                } else {
                    $this->_changed['path'] = $parent->path;
                }
            } else {
                $this->_changed['path'] = '/';
                $this->_changed['parent'] = 0;
            }
        }


        $result = parent::save($key);

//        if($this->path == '/')
//        {
//       		$this->path = '/'.$this->id().'/';
//       		$result = parent::save($key);
//		}

        $path_pieces = explode('/', $this->path);
        if ($path_pieces[count($path_pieces) - 2] != $this->id()) {
            $this->path = $this->path . $this->id() . '/';
            $result = parent::save($key);
        }

        return $result;
    }

    protected $result = array();
    protected $counter = 0;

    public function get_tree($license_id, $with_cultures = false, $exclude = array(), $items_field = 'items') {
        $this->result = array();
        $this->counter = 0;

        $items_model = $this->meta()->fields($items_field);
        $items_model_name = $items_model->foreign['model'];

        $groups = Jelly::select($this->meta()->model())->with('parent')->where('deleted', '=', false)->order_by('name', 'asc');
        $names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->where('deleted', '=', false)->order_by('name', 'asc');

        // Дополнительная фильтрация
        if ($this->meta()->fields('license') and $this->meta()->fields('farm') and $this->meta()->fields('period')) {
            $farms = Jelly::factory('farm')->get_session_farms();
            if (!count($farms))
                $farms = array(-1);
            $periods = Session::instance()->get('periods');
            if (!count($periods))
                $periods = array(-1);

            $user = Auth::instance()->get_user();

            if ($user) {
                $groups = $groups->where('license', '=', $user->license->id());
                $names = $names->where('license', '=', $user->license->id());
            }

            $groups = $groups->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
            $names = $names->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
        }

        $groups = $groups->execute()->as_array();
        $names = $names->execute()->as_array();

        $this->get_groups($groups, $names, 0, 0, $exclude, $with_cultures, $items_model->foreign['column']);

        if ($with_cultures) {

            $this->result = array_merge(array(array('id' => 'g0', 'level' => -1, 'color' => ($this->counter ? 'BBBBBB' : 'FFFFFF'))), $this->result);
            for ($i = count($this->result) - 1; $i >= 0; $i--) {
                $group_id = (int) mb_substr($this->result[$i]['id'], 1);
                $group_names = array();
                foreach ($names as $name) {
                    if (isset($exclude['names']) && in_array($name['_id'], $exclude['names'])) {
                        continue;
                    }
                    if ($name[':' . $items_model->foreign['column'] . ':_id'] == $group_id) {
                        $group_names[] = array(
                            'id' => 'n' . $name['_id'],
                            'title' => $name['name'],
                            'is_group' => true,
                            'is_group_realy' => false,
                            'level' => $this->result[$i]['level'] + 1,
                            'children_g' => array(),
                            'children_n' => array(),
                            'parent' => $group_id ? 'g' . $group_id : '',
                            'color' => $name['color'],
                            'parent_color' => $this->result[$i]['color']
                        );
                    }
                }
                array_splice($this->result, $i + 1, 0, $group_names);
            }
            if (isset($this->result[0]) && $this->result[0]['id'] == 'g0') {
                array_splice($this->result, 0, 1);
            }
        }

        return $this->result;
    }

    protected function get_groups($groups, $names, $parent, $level, $exclude, $with_cultures, $relation) {
        $items = array();
        foreach ($groups as $group) {
            if ($group[':parent:_id'] == $parent)
                $items[] = $group;
        }


        foreach ($items as $item) {
            if (in_array($item['_id'], isset($exclude['groups']) ? $exclude['groups'] : $exclude)) {
                continue;
            }
            $children_g = $this->get_children_ids($groups, $item['_id'], true, $relation, isset($exclude['groups']) ? $exclude['groups'] : $exclude);
            $children_n = $this->get_children_ids($names, $item['_id'], false, $relation, isset($exclude['names']) ? $exclude['names'] : array());

            $this->result[$this->counter] = array(
                'id' => 'g' . $item['_id'],
                'title' => $item['name'],
                'is_group' => true,
                'is_group_realy' => true,
                'level' => $level,
                'children_g' => $with_cultures ? array_merge($children_g, $children_n) : $children_g,
                'children_n' => $children_n,
                'parent' => $item[':parent:_id'] ? 'g' . $item[':parent:_id'] : '',
                'color' => $item['color'],
                'parent_color' => $item[':parent:color']
            );
            $this->counter++;
            $this->get_groups($groups, $names, $item['_id'], $level + 1, $exclude, $with_cultures, $relation);
        }
    }

    protected function get_children_ids($children, $item_id, $is_group, $relation, $exclude) {
        $res = array();
        foreach ($children as $child) {
            if (in_array($child['_id'], $exclude)) {
                continue;
            }
            if ($is_group && $child[':parent:_id'] == $item_id) {
                $res[] = 'g' . $child['_id'];
            }
            if (!$is_group && $child[':' . $relation . ':_id'] == $item_id) {
                $res[] = 'n' . $child['_id'];
            }
        }
        return $res;
    }

    protected function get_all_children_ids($children, $item_id, $is_group, $relation, $exclude, $all = FALSE) {
        
        $res = array();
        foreach ($children as $child) {
            if (in_array($child['_id'], $exclude)) {
                continue;
            }
            if ($is_group && $child[':parent:_id'] == $item_id) {
                $res[] = 'g' . $child['_id'];
            }
            if (!$is_group) {
                if ($all) {
                    
                    if (strpos($child[':' . $relation . ':path'], '/' . $item_id . '/') !== FALSE) {
                        $res[] = 'n' . $child['_id'];
                    }
                } else if ($child[':' . $relation . ':_id'] == $item_id) {
                    $res[] = 'n' . $child['_id'];
                }
            }
        }
        return $res;
    }

    public function get_parent_path($group_id) {
        $group = Jelly::select($this->meta()->model())->load($group_id);
        if ($group->parent->id())
            return array_merge($this->get_parent_path($group->parent->id()), array($group));
        else
            return array($group);
    }

    public function delete($key = NULL) {
        //wtf? falling back to parent
        if (!is_null($key)) {
            return parent::delete($key);
        }

        $this->deleted = true;
        $this->save();
    }

    public function get_properties() {
        $values = Jelly::select('client_model_values')->where('item_id', '=', $this->id())->execute();
        
        $res = array();
        
        foreach($values as $v){
            
            $property = Jelly::select('client_model_properties', $v->property->id());
            if($property->model==$this->meta()->model()){
                $res[$property->id()] = array('name' => $property->name, 'value' => $v->value, '_id' => $property->id(), 'order' => $v->order);
            }
            
            
            
        }
        
        if(! function_exists('compare_order')) {
            function compare_order($a, $b){
                return ((int)$a['order']) - ((int)$b['order']);
            }
        }
        
        usort($res, 'compare_order');
//        print_r($res);exit;
        
        return $res;
        
    }

    public function set_property($id, $property_name, $property_value = '', $order = '') {
        $property = null;

        if ($id) {
            $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int) $id)->load();

            if (!($property instanceof Jelly_Model) or !$property->loaded()) {
                return;
            }
        }

        if (!$id) {
            $property = Jelly::factory('client_model_properties');
            $property->model = $this->_meta->model();
            $property->name = $property_name;
            $property->save();
        } else {
            $property->name = $property_name;
            $property->save();
        }

        $value = Jelly::select('client_model_values')->where('property', '=', $property->id())->where('item_id', '=', $this->id())->load();
        if (!($value instanceof Jelly_Model) or !$value->loaded()) {
            $value = Jelly::factory('client_model_values');
            $value->property = $property;
            $value->item_id = $this->id();
        }

        $value->value = $property_value;
        $value->order = $order;
        $value->save();
    }

    public function delete_property($id) {
        $property = Jelly::select('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int) $id)->load();

        if (!($property instanceof Jelly_Model) or !$property->loaded())
            return;

        Jelly::delete('client_model_values')
                ->where('property', '=', $property->id())
                ->where('item_id', '=', $this->id())
                ->execute();

//        Jelly::delete('client_model_properties')->where('model', '=', $this->_meta->model())->where('_id', '=', (int) $id)->execute();
    }

}