<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_ChemicalComposition extends Controller_Glossary_Abstract {

    protected $model_name = 'glossary_chemicalcomposition';
    protected $model_group_name = 'glossary_chemicalcompositiongroup';
    
    public function inner_edit(&$view){
        
        $view->units = Jelly::factory('glossary_units')->getUnits('chemical_composition');
        
        if(!isset($view->model['_id'])){
            $view->model['contents'] = array();
            if(isset($view->model['group'])){
                $group = Jelly::select('glossary_chemicalcompositiongroup', $view->model['group']);
                $group_properties = $group->get_properties();
                foreach($group_properties as $i => $property){
                    $view->model['contents'][] = array(
                        '_id' => $i+1,
                        'color' => $property['name'],
                        'text' => $property['value'],
                        'first_lower' => '',
                        'first_upper' => '',
                        'first_units' => $view->units[0]['_id'],
                        'first_units_name' => $view->units[0]['name'],
                        'second_lower' => '',
                        'second_upper' => '',
                        'second_units' => $view->units[1]['_id'],
                        'second_units_name' => $view->units[1]['name']
                    );
                }
            }
        }else{
            
            $old_contents = Jelly::select('Glossary_ChemicalCompositionContent')->
                where('chemicalcomposition','=',$view->model['_id'])->
                execute();
            
            $view->model['contents'] = array();
            foreach($old_contents as $i => $content){
                $view->model['contents'][] = array(
                    '_id' => $i+1,
                    'color' => $content->color,
                    'text' => $content->text,
                    'first_lower' => $content->first_lower,
                    'first_upper' => $content->first_upper,
                    'first_units' => $content->first_units->id(),
                    'first_units_name' => $content->first_units->name,
                    'second_lower' => $content->second_lower,
                    'second_upper' => $content->second_upper,
                    'second_units' => $content->second_units->id(),
                    'second_units_name' => $content->second_units->name,
                    'order' => (int)$content->order ? (int)$content->order : ($i+1)
                );
            }
            
            if(! function_exists('compare_order')) {
                function compare_order($a, $b){
                    return $a['order'] - $b['order'];
                }
            }
            
            usort($view->model['contents'], 'compare_order');
            
        }
        
        if(isset($view->model['group'])){
            $group = Jelly::select('glossary_chemicalcompositiongroup',$view->model['group']);
            $view->model['group_name'] = $group->name;
            $view->model['group_color'] = $group->color;
        }else{
            $view->model['group_name'] = 'Без группы';
            $view->model['group_color'] = 'bebebe';
        }
        
        
	}
    
    public function inner_update($id){
        
        
        $old_contents = Jelly::delete('Glossary_ChemicalCompositionContent')->
                where('chemicalcomposition','=',$id)->
                execute();
        
        
        $nums = array();
        foreach($_POST as $key => $value){
            $pieces = explode('_', $key);
            
            if(isset($pieces[1]) && is_numeric($pieces[1]) ){
                $nums[] = (int)$pieces[1];
            }
        }
        
        $nums = array_unique($nums);
        
        $orders = array();
        foreach($nums as $num){
            $orders[$num] = Arr::get($_POST,"property_{$num}_order",0);
        }
        asort($orders);
        
        
        
        foreach($orders as $num => $order){
            Jelly::factory('Glossary_ChemicalCompositionContent')->set(array(
                'chemicalcomposition'	=> $id,	
				
				'color' => Arr::get($_POST,"property_{$num}_label",'bebebe'),
                'text' => Arr::get($_POST,"property_{$num}",''),
                'first_lower' => Arr::get($_POST,"property_{$num}_first_lower",'0'),
                'first_upper' => Arr::get($_POST,"property_{$num}_first_upper",'0'),
                'first_units' => Arr::get($_POST,"property_{$num}_first_units",1),
                'second_lower' => Arr::get($_POST,"property_{$num}_second_lower",'0'),
                'second_upper' => Arr::get($_POST,"property_{$num}_second_upper",'0'),
                'second_units' => Arr::get($_POST,"property_{$num}_second_units",1),
                'order' => Arr::get($_POST,"property_{$num}_order",0)
                        
            ))->save();
        }
        
        
    }
}