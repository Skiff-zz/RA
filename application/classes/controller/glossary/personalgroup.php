<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_PersonalGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'glossary_personal';
	protected $model_group_name = 'glossary_personalgroup';

	public function inner_update($id){
		$id = (int)($id);
		Jelly::factory('personalpreset')->insert_preset($id, true);
		$model = Jelly::select('glossary_personalgroup', (int) $id)->as_array();
		$handbook_siblings = Jelly::select('client_handbook_personalgroup')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
						where('is_position','=',false)->
						where('id_in_glossary','=',$id)->
                        execute();

		foreach($handbook_siblings as $sibling){
			$sibling->set(array(
				'name'=>$model['name'],
				'color'=>$model['color'],
			))->save();
		}

	}
}

