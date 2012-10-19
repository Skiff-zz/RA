<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_TechMobileGroup extends Controller_Glossary_AbstractGroup
{

	protected $model_name = 'glossary_techmobile';
	protected $model_group_name = 'glossary_techmobilegroup';

	public function inner_update($id){
		$id = (int)($id);

		$model = Jelly::select('glossary_techmobilegroup', (int) $id)->as_array();
		$handbook_siblings = Jelly::select('client_handbook_techniquemobilegroup')->
                        where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
						where('id_in_glossary','=',$id)->
                        execute();

		foreach($handbook_siblings as $sibling){
			$sibling->set(array(
				'name'=>$model['name'],
				'color'=>$model['color'],
			))->save();
		}

	}

	public function action_treeforhandbooktechniquemobile(){

		$user = Auth::instance()->get_user();
		$license_id = $user->license->id();


		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms)) $farms = array(-1);

		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		$exclude = arr::get($_GET, 'exclude', '');
		$exclude = explode(',', $exclude);
		if(!$exclude[0] || !$exclude) { $exclude = array(); }
		for($i=0; $i<count($exclude); $i++){
			//  $exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g' || mb_substr($exclude[$i], 0, 1)=='n' ? mb_substr($exclude[$i], 1) : $exclude[$i];
				$exclude[$i] = mb_substr($exclude[$i], 0, 1)=='g'										? mb_substr($exclude[$i], 1) : $exclude[$i];
		}

		$with_cultures = Arr::get($_GET, 'both_trees', false);

		$data =	Jelly::factory($this->model_group_name)->get_tree('delete_this_shit', $with_cultures, $exclude, $this->items_field);

		$in_handbook = Jelly::select('client_handbook_techniquemobile')->
				with('group')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where_open()->where('group.deleted', '=', 0)->or_where('group.deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id )->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				execute()->
				as_array();

		$unique_glossary_ids = array();
		foreach ($in_handbook as $name) {
			array_push($unique_glossary_ids, $name['id_in_glossary']);
		}
		$unique_glossary_ids = array_unique($unique_glossary_ids);

		for($i=count($data)-1;$i>=0;$i--){
//			$data[$i]['is_group'] = $data[$i]['is_group_realy'];
			if ((!$data[$i]['is_group_realy'])&&(!in_array( substr(   $data[$i]['id'],1   ), $unique_glossary_ids))){

				for($j=count($data)-1;$j>=0;$j--){
					if ($data[$j]['id']==$data[$i]['parent']){
//						unset($data[$j]['children_g'][ array_search($data[$i]['id'], $data[$j]['children_g'])   ]);
//						unset($data[$j]['children_n'][ array_search($data[$i]['id'], $data[$j]['children_n'])   ]);
						array_splice($data[$j]['children_g'], array_search($data[$i]['id'], $data[$j]['children_g']), 1);
						array_splice($data[$j]['children_n'], array_search($data[$i]['id'], $data[$j]['children_n']), 1);
					}
				}
				array_splice($data, $i, 1);
//				unset($data[$i]);
			}

		}

		$in_handbook_groups = Jelly::select('client_handbook_techniquemobilegroup')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $license_id )->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				execute()->
				as_array();

		$unique_glossary_ids_groups = array();
		foreach ($in_handbook_groups as $group) {
			array_push($unique_glossary_ids_groups, $group['id_in_glossary']);
		}
		$unique_glossary_ids_groups = array_unique($unique_glossary_ids_groups);

		for($i=count($data)-1;$i>=0;$i--){
			if (($data[$i]['is_group_realy'])&&(!in_array( substr(   $data[$i]['id'],1   ), $unique_glossary_ids_groups))){
				for($j=count($data)-1;$j>=0;$j--){
					if ($data[$j]['id']==$data[$i]['parent']){
						array_splice($data[$j]['children_g'], array_search($data[$i]['id'], $data[$j]['children_g']), 1);
						array_splice($data[$j]['children_n'], array_search($data[$i]['id'], $data[$j]['children_n']), 1);
					}
				}
				array_splice($data, $i, 1);
			}

		}

		$this->request->response = JSON::Arr($data, count($data));
	}
}
