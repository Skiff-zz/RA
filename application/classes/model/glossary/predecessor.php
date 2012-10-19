<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_Predecessor extends Jelly_Model
{

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('glossary_predecessors')
			->fields(array(
					'_id'			=> Jelly::field('Primary'),
				
					'deleted' 		=> Jelly::field('Boolean', array('label' => 'Удален')),
					
					'culture'		=> Jelly::field('BelongsTo',array(
						'foreign'	=> 'glossary_culture',
						'column'	=> 'culture_id',
						'label'		=> 'Культура',
					)),

					'predecessor' => Jelly::field('BelongsTo',array(
						'foreign'	=> 'glossary_culture',
						'column'	=> 'predecessor_id',
						'label'		=> 'Предшественник',
					)),

					'outer_mark'	=>  Jelly::field('Integer', array('label' => 'Оценка',   //1-отлично 2-хорошо 3-плохо
						'rules' => array(
							'not_empty' => NULL,
							'range' => array(1, 3)
						))
					),

					'inner_mark'	=>  Jelly::field('Integer', array('label' => 'Оценка',
						'rules' => array(
							'not_empty' => NULL,
							'range' => array(1, 5)
						))
					)
			));
	}


	public function get_list($cultures, $group = false, $edit = true){
		if(!$cultures || !is_array($cultures)){return array('result'=>array(), 'conflicted'=>array());}
		$result = array();
		$conflicted = array();

		$cultures_list = Jelly::factory('glossary_culture')->get_tree('delete_this_shit');

		$marks = Jelly::select('glossary_predecessor')->with('predecessor')->with('culture')->where('deleted', '=', false)->and_where('culture', 'IN', $cultures)->order_by('outer_mark', 'asc')->order_by('predecessor.name', 'asc')->execute()->as_array();
		$types = Jelly::select('glossary_culturetype')->execute()->as_array();

		$marks = $this->orderMarks($marks, $cultures_list);

		foreach ($marks as $mark) {
			if(!$this->is_overall_mark($marks, $mark, $cultures)) { 
				$conflicted[]=$mark[':predecessor:_id'];
				if(!$edit) continue;
			}
			$result[] = array(
				'id' => $mark['_id'],
				'title' => $mark[':predecessor:title'],
				'type'     => $this->getTypeName($mark[':predecessor:type'], $types),
				'left_id' => $mark[':culture:_id'],
				'left_name' => $mark[':culture:title'],
				'right_id' => $mark[':predecessor:_id'],
				'tab' => $mark['outer_mark'],
				'mark' => $mark['inner_mark'],
				'is_new' => false
			);
		}

		if($group){
			$result = $this->group_result($result);
		}

		return array('result'      => $edit ? $result : $this->unique_result($result),
						  'conflicted' => array_merge(array_unique($conflicted), array())//$this->prepareConflicted(array_unique($conflicted), $marks, $types)
		);
	}


	//является ли эта оценка общей для всех
	private function is_overall_mark($marks, $mark_, $cultures){
		$is_overall = true;
		foreach ($cultures as $culture) {
			$culture_has_mark = false;
			foreach($marks as $mark){
				if($mark[':predecessor:_id']==$mark_[':predecessor:_id'] && $mark[':culture:_id']==$culture && $mark['outer_mark']==$mark_['outer_mark'] && $mark['inner_mark']==$mark_['inner_mark']) $culture_has_mark = true;
			}

			if(!$culture_has_mark){ $is_overall = false; }
		}

		return $is_overall;
	}
//
//
//	//запись с таким предшественником уже включена в список
//	private function item_alredy_included($result, $mark){
//		for($i=0; $i<count($result); $i++){
//			if($result[$i]['right_id']==$mark[':predecessor:_id']) return $i;
//		}
//		return false;
//	}
//
//
//	//формирование списка конфликтных
//	private function prepareConflicted($conflicted, $marks, $types){
//		$res = array(); $it = 0;
//		foreach($conflicted as $conflict) {
//			$res[$it] = array('id' => $conflict, 'related' => array());
//			foreach ($marks as $mark) {
//				if($mark[':predecessor:_id']==$conflict){
//                                    $res[$it]['related'][] = array(
//                                        'name'=>$mark[':culture:name'].' '.$this->getTypeName($mark[':culture:type'], $types),
//                                        'outer_mark'=>$mark['outer_mark'],
//                                        'inner_mark'=>$mark['inner_mark']);
//                                }
//                        }
//                        $it++;
//                }
//                return $res;
//        }
                
                
	//имя типа по айдишнику
	private function getTypeName($id, $types){
		foreach($types as $type) {
			if($type['_id']==$id){ return $type['name']; }
		}
		return '';
	}



	private function group_result($records){
		$groups = array();

		for($i=0; $i<count($records); $i++){
			$groupStr = $records[$i]['left_name'];

			if(!isset($groups[$groupStr])) $groups[$groupStr] = array('name' => $groupStr, 'children' => array());

			$groups[$groupStr]['children'][] = $records[$i];
		}

		return $groups;
	}


	private function unique_result($records){
		$res = array();
		$ids = array();

		foreach($records as $record){
			if(array_search($record['right_id'], $ids)===false){
				$res[] = $record;
				$ids[] = $record['right_id'];
			}
		}

		return $res;
	}



	private function orderMarks($marks, $cultures){
		$result = array();
		foreach($cultures as $culture){
			foreach ($marks as $mark) {
				if('n'.$mark[':culture:_id']==$culture['id']) $result[] = $mark;
			}
		}
		return $result;
	}

}

