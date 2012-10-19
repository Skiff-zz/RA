<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Client_HandbookVersionName extends Controller_Glossary_Abstract {

	protected $model_name = 'client_handbookversionname';
	protected $model_group_name = '';
	protected $nomenclature_subtree = array(
		array('materialsr', 'Материалы', 0, true, true, array('seed', 'szr', 'fertilizer', 'gsm', 'mother'), array(), '', '6d277f', '6d277f', 'empty', 'Материалы'),
		array('seed', 'Семена', 1, true, true, array(), array(), 'materialsr', '973aae', 'culture', 'agrocleverglossaryculturesseedsrichtree', 'seed', 'Семена'),
		array('szr', 'СЗР', 1, true, true, array(), array(), 'materialsr', 'b655fa', 'szr', 'agrocleverglossarymetrialsszrrichtree', 'szr', 'СЗР'),
		array('fertilizer', 'Удобрения', 1, true, true, array(), array(), 'materialsr', 'c38ef1', 'fertilizer', 'agrocleverglossarymetrialsfertilizerrichtree', 'fertilizer', 'Удобрения'),
		array('gsm', 'ГСМ', 1, true, true, array(), array(), 'materialsr', 'd8befc', 'gsm', 'agrocleverglossarymetrialsgsmrichtree', 'gsm', 'ГСМ'),
		array('mother', 'Прочее', 1, true, true, array(), array(), 'materialsr', 'e7dcfc', 'e7dcfc', 'empty', 'Прочее'),
		array('productionr', 'Продукция', 0, true, true, array('productionclass', 'pother'), array(), '', '265bf9', 'production', 'empty', 'Продукция'),
		array('productionclass', 'Продукция', 1, true, true, array(), array(), 'productionr', '5f99ef', 'productionclass', 'agrocleverglossaryculturesproductionrichtree', 'productionclass', 'Продукция'),
		array('pother', 'Прочее', 1, true, true, array(), array(), 'productionr', 'c3dcfd', 'c3dcfd', 'empty', 'Прочее'),
		array('service', 'Услуги', 0, true, true, array(), array(), '', 'de2414', 'de2414', 'empty', 'Услуги'),
	);

	public function before() {
		if ($this->model_name == '__abstract') {
			throw new Kohana_Exception('Abstract controller should not be called directly!');
		}
	}

	public function action_edit($id = null, $read = false, $parent_id = false) {

		$model = null;

		if ($id) {
			$model = Jelly::select($this->model_name)->where(':primary_key', '=', (int) $id)->load();

			if (!($model instanceof Jelly_Model) or !$model->loaded()) {
				$this->request->response = JSON::error('Запись не найдена!');
				return;
			}
		}
		$farms = Jelly::factory('farm')->get_session_farms();
		if(count($farms)!=1 && !$model){ $this->request->response = JSON::error('Для создания версии справочника необходимо указать одно хозяйство.'); return; }
		$view = Twig::factory('/client/handbookversionname/handbookversionname.html');

		if ($model) {

			$model->group = null;
			$view->model = array();
			$view->model['_id'] = $model->_id;
			$view->model['group'] = null;
			$view->model['name'] = $model->name;
			$view->model['color'] = $model->color;
			$view->model['farm'] = $model->farm;
		}


		if (!$read) {
			$view->edit = true;

			if ((int) $parent_id) {
				$view->model = array();
			}
		}


		if (!$this->ignore_addons) {
			if (!$model) {
				if (!(int) $parent_id) {
					$view->model = array();
				}
				$view->model['properties'] = Jelly::factory($this->model_name)->get_properties();
			} else {
				$view->model['properties'] = $model->get_properties();
			}
		}

		// Уличная магия -- показываем дополнительные поля из модели, которых нет в абстрактном варианте
		$abstract_fields = Jelly::meta('glossary_abstract')->fields();
		$fields = Jelly::meta($this->model_name)->fields();

		$addon_fields = array();

		if (!$model) {
			$f = Jelly::select('farm')->where(':primary_key', '=', (int)$farms[0])->load();
			if(!$f)$f=Jelly::select('farmgroup')->where(':primary_key', '=', (int)$farms[0])->load();
			$view->model['farm'] = $f;
		}

		$view->model['datetime_literal'] = ($model && $model->datetime) ? date('d.m.Y H:i', $model->datetime) : date('d.m.Y H:i');
		$view->model['datetime'] = ($model && $model->datetime) ? $model->datetime : time();
		$view->model_fields = $addon_fields;
		if($model){
			$view->model['atks'] = Jelly::select('client_planning_atk')->
					with('atk_type')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('handbook_version', '=', (gettype($model)=='array')?$model['id']:$model->id())->
					execute();
			$view->model['plans'] = Jelly::select('client_planning_plan')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('handbook_version', '=', (gettype($model)=='array')?$model['id']:$model->id())->
					execute();
		}


		$this->inner_edit($view);
		$this->request->response = JSON::reply($view->render());
	}

	public function action_update_planprices($versionname_id){
		$time = time();
		$versionname = Jelly::select($this->model_name, $versionname_id);
		$info = json_decode(arr::get($_POST, 'info', array()));
		foreach($info as $record){
			$in_table = Jelly::select('client_handbookversion')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('nomenclature_model','=',$record->nomenclature_model)->
					where('nomenclature_id','=',$record->nomenclature_id)->
					where('amount_units','=',$record->amount_units)->
//					where('discount_price_units','=',$record->discount_price_units)->
					where('version_date','=',$versionname->datetime)->
					where('license', '=', $versionname->license->id())->
					where('farm', '=', $versionname->farm->id())->
					where('period', '=', $versionname->period->id())->
					execute();

			foreach($in_table as $row){
				$row->set(array(
					'planned_price'=>(float)$record->planned_price,
					'planned_price_units'=>(int)$record->planned_price_units,
					'planned_price_manual'=> (int)$record->planned_price_manual ? (int)$record->planned_price_manual : $row->planned_price_manual
				))->save();

				$versionname->set(array('update_datetime'=>$time));
			}
		}
		$versionname->save();
	}

	public function transfer_not_used_from_handbook($versionname_id) {
		$versionname = Jelly::select($this->model_name, $versionname_id);

		$raw = Jelly::select('client_handbookversion')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('version_date', '=', $versionname->datetime)->
				where('license', '=', $versionname->license->id())->
				and_where('farm', '=', $versionname->farm->id())->
				and_where('period', '=', $versionname->period->id())->
				with('farm')->
				execute();

		$hbook_info = Jelly::select('client_handbook')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('model', 'LIKE', 'glossary_%')->
				where('model', 'NOT LIKE', '%group')->
				where('model', 'NOT LIKE', '%culture')->
				where('model', 'NOT LIKE', '%production')->
				where('license', '=', $versionname->license->id())->
				and_where('farm', '=', $versionname->farm->id())->
				and_where('period', '=', $versionname->period->id())->
				with('farm')->
				execute();

		$yardbirds = array();
		foreach($hbook_info as $candidate){
			$found = false;
			foreach($raw as $member){
				if('glossary_'.$member->nomenclature_model == $candidate->model && $member->nomenclature_id == $candidate->item){
					$found = true;
					break;
				}
			}
			if(!$found){
				$yardbirds[] = $candidate;
			}
		}
		$ids = array();
		foreach($yardbirds as $yardbird){
			$new_yardbird = array(
				'deleted'=>0,
				'version_date'=>$versionname->datetime,
				'update_date'=>time(),
				'nomenclature_model'=> str_replace('glossary_','',$yardbird->model),
				'nomenclature_id'=> $yardbird->item,
				'license'=> $yardbird->license->id(),
				'farm'=> $yardbird->farm->id(),
				'period'=> $yardbird->period->id(),
				'amount'=> 0,
				'amount_units'=> 1,
				'discount_price'=> 0,
				'discount_price_units'=> 1,
				'planned_price'=> 0,
				'planned_price_units'=> 1,
				'planned_price_manual'=> 0

			);
			$jelly_obj = Jelly::factory('client_handbookversion')->set($new_yardbird)->save();
			$ids[] = $jelly_obj->id();
		}
		return $ids;
	}

	public function action_gettable($id = null, $read = false, $parent_id = false) {
		setlocale(LC_NUMERIC, 'C');

		// Не забыть сверху наложить условия на лицензиата, ферму и период перед продакшеном!!!
		if (is_null($user = $this->auth_user()))
			return;

		$farms = Jelly::factory('farm')->get_session_farms();
		if (!count($farms))
			$farms = array(-1);

		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);
		// ----
		$model = Jelly::select($this->model_name)->where(':primary_key', '=', (int) $id)->load();
//		$added_ids = $this->transfer_not_used_from_handbook($model->id());


		$raw = Jelly::select('client_handbookversion')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('version_date', '=', $model->datetime)->
				where('license', '=', $user->license->id())->
				and_where('farm', '=', $model->farm->id())->
				and_where('period', '=', $model->period->id())->
				order_by('farm')->
				with('farm')->
				order_by('farm.path', 'ASC')->
				execute();

		$farms = array();
		foreach ($raw as $r) {
			$r->farm->path = $r->farm->path.$r->farm->id().'/';
			$farms[$r->farm->id()] = $r->farm;
		}

		$tmp = array();
		foreach ($farms as $f) {
			$tmp[] = $f;
		}

		$farms = $tmp;

		$tmp = array();

		for ($i = 0; $i < $k = count($farms); $i++) {
			$found = false;

			for ($j = 0; $j < $k = count($farms); $j++) {
				if ($farms[$j]->id() == $farms[$i]->id())
					continue;

				if (strpos($farms[$j]->path, $farms[$i]->path) !== false) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				$tmp[] = $farms[$i];
			}
		}

		$farms = $tmp;

		$farms_path = array();

		foreach ($farms as $f) {
			$farms_path[] = $f->get_parent_path($f->id());
		}

		$farm_branches_to_farmid = array();

		foreach ($farms_path as &$fg) {
			foreach ($fg as &$f) {
				$f = array('groups' => array(), 'items' => array(), 'money' => 0, 'farm' => $f, 'type_groups' => array());
			}
			$farm_branches_to_farmid[] = $f['farm']->id();
		}

		@reset($raw);

		function get_farm($model, &$farms_path) {
			$result = array(-1, -1);

			for ($i = 0; $i < $k = count($farms_path); $i++) {
				for ($j = 0; $j < $m = count($farms_path[$i]); $j++) {
					if ($farms_path[$i][$j]['farm']->id() == $model->farm->id()) {
						return array($i, $j);
					}
				}
			}

			return $result;
		}

		foreach ($raw as $q) {
			$item = Jelly::select('glossary_' . $q->nomenclature_model)->with('group')->load($q->nomenclature_id);

			if (!$item instanceof Jelly_Model or !$item->loaded())
				continue;

			list($chain, $farm_id) = get_farm($q, $farms_path);

			if ($chain == -1)
				continue;

			$found = false;
			foreach ($farms_path[$chain][$farm_id]['groups'] as $group) {
				if ($group->meta()->model() == $item->group->meta()->model() && $group->id() == $item->group->id()) {
					$found = true;
				}
			}


			if (!$found) {
				$item->group->path = $item->group->path . $item->group->id() . '/';
				$farms_path[$chain][$farm_id]['groups'][] = $item->group;
			}

			$farms_path[$chain][$farm_id]['items'][] =
					array(
						'item' => $item,
						'groupmodelname' => $item->group->meta()->model(),
						'units_in_glossary' => $item->units ? $item->units : $item->group->units,
						'amount' => $q->amount,
						'amount_units' => Model_Client_TransactionNomenclature::$amount_units[$q->amount_units],
						'discount_price' => $q->discount_price,
						'discount_price_units' => Model_Client_TransactionNomenclature::$amount_units[$q->discount_price_units],
						'planned_price' => (float)$q->planned_price,
						'planned_price_units' => Model_Client_TransactionNomenclature::$amount_units[$q->planned_price_units],
						'planned_price_manual' => (int)$q->planned_price_manual
			);
		}

		for ($i = 0; $i < $k = count($farms_path); $i++) {
			for ($j = 0; $j < $m = count($farms_path[$i]); $j++) {
				$tmp = array();

				foreach ($farms_path[$i][$j]['groups'] as $key => $value) {
					$tmp[] = $value;
				}

				$farms_path[$i][$j]['groups'] = $tmp;

				$tmp = array();

				for ($a = 0; $a < $kk = count($farms_path[$i][$j]['groups']); $a++) {
					$found = false;

					for ($b = 0; $b < $kk; $b++) {
						if ($farms_path[$i][$j]['groups'][$b]->id() == $farms_path[$i][$j]['groups'][$a]->id() && $farms_path[$i][$j]['groups'][$b]->meta()->model() == $farms_path[$i][$j]['groups'][$a]->meta()->model())
							continue;

						if (strpos($farms_path[$i][$j]['groups'][$b]->path, $farms_path[$i][$j]['groups'][$a]->path) !== false) {
							if ($farms_path[$i][$j]['groups'][$b]->meta()->model() == $farms_path[$i][$j]['groups'][$a]->meta()->model()) {
								$found = true;
								break;
							}
						}
					}

					if (!$found) {
						$tmp[] = $farms_path[$i][$j]['groups'][$a];
					}
				}

				$farms_path[$i][$j]['groups'] = $tmp;

				$groups_path = array();

				foreach ($farms_path[$i][$j]['groups'] as $key => $value) {
					$groups_path[] = $value->get_parent_path($value->id());
				}

				$farms_path[$i][$j]['groups'] = $groups_path;
			}
		}



		// дальше идет кусок кода который сливает в кучу все ветки групп номенклатур и делит потом их по разделам(типам). писал Андрей

		foreach ($farms_path as $z => $farmbranch) {
			foreach ($farmbranch as $k => $farm_item) {
				$model_types = array();
				foreach ($farm_item['groups'] as $i => $groupsbranch) {
					foreach ($groupsbranch as $group) {
						$m_type = $group->meta()->model();
						if ($m_type == 'glossary_culture')
							$m_type = 'glossary_culturegroup';
						if (!isset($model_types[$m_type])) {
							$model_types[$m_type] = array();
						}
						$model_types[$m_type][] = $group;
					}
				}
				$farm_item['groups'] = array();
				foreach ($model_types as $type => $swarm) {
					$uniqueswarm = array();
					foreach ($swarm as $swarmling) {
						$found = false;
						foreach ($uniqueswarm as $swarmling_u) {
							if ($swarmling_u->id() == $swarmling->id() && $swarmling_u->meta()->model() == $swarmling->meta()->model()) {
								$found = true;
								break;
							}
						}
						if (!$found) {
							$uniqueswarm[] = $swarmling;
						}
					}

						$farm_item['groups'][] = $uniqueswarm;
				}
				$farmbranch[$k] = $farm_item;
			}
			$farms_path[$z] = $farmbranch;
		}

		// дальше идет кусок кода который впихивает типы номенклатур "Материалы - Семена" и т.д. писал Андрей



		foreach ($farms_path as $z => $farmbranch) {
			foreach ($farmbranch as $k => $farm_item) {
				foreach ($farm_item['groups'] as $i => $groupsbranch) {
					$max_group_id = 0;
					foreach ($farm_item['groups'] as $branch) {
						foreach ($branch as $group) {

							$group_arr = ((gettype($group) == 'array') ? $group : ($group->generated ? get_object_vars($group) : $group->as_array()));

							$id = isset($group_arr['id']) ? $group_arr['id'] : (isset($group_arr['_id']) ? $group_arr['_id'] : (isset($group_arr['hidden_id'])?$group_arr['hidden_id']:NULL)      );


							if (!isset($group_arr['generated']) && $max_group_id < ( $id )) {
								$max_group_id = $id;
							}
						}
					}



					$model_type = $groupsbranch[0]->meta()->model();
					$model_type_splat = explode('_', $model_type);
					$found_type = null;
					foreach ($this->nomenclature_subtree as $type) {
						foreach ($model_type_splat as $word) {
							if (($type[count($type) - 2] != 'empty' && $type[count($type) - 2] . 'group' == $word) || ($model_type == 'glossary_production' && $type[count($type) - 2] == 'productionclass') || ($model_type == 'glossary_culturegroup' && $type[count($type) - 2] == 'seed')) {
								$found_type = $type;
								break;
							}
							if($model_type=='glossary_culture'){
								$found_type = $this->nomenclature_subtree[1];
								break;
							}
						}
						if ($found_type) {
							break;
						}
					}

					if ($found_type) {

						$y = (object) array(
									'id' => ++$max_group_id,
									'_id' => $max_group_id,
									'hidden_id' => $max_group_id,
									'hidden_type' => $found_type[0],
									'name' => $found_type[1],
									'color' => $found_type[8],
									'modeltype' => $found_type[count($found_type) - 2],
									'parent' => 0,
									'generated' => true,
									'path' => '/',
									'deleted' => 0
						);
						$chain = array($y);

						while ($found_type[7]) {
							foreach ($this->nomenclature_subtree as $t) {
								if ($t[0] == $found_type[7]) {
									$found_type = $t;
									$t_obj = (object) array(
												'id' => ++$max_group_id,
												'_id' => $max_group_id,
												'hidden_id' => $max_group_id,
												'hidden_type' => $t[0],
												'name' => $t[1],
												'color' => $t[8],
												'modeltype' => $t[count($t) - 2],
												'generated' => true,
												'parent' => 0,
												'path' => '/',
												'deleted' => 0
									);
									foreach ($chain as $link) {
										$link->path = '/' . ($t_obj->hidden_id) . $link->path;
									}
									$chain[count($chain) - 1]->parent = $t_obj->hidden_id;
									$chain[] = $t_obj;
								}
							}
						}

						$second_chain = array();
						foreach ($chain as $link) {
							foreach ($groupsbranch as $g) {
								if ($g->generated && ($g->hidden_id == $link->hidden_id)) {
									$link->dont_add = true;
									break;
								}
							}
							if (!isset($link->dont_add)) {
								$second_chain = array_merge(array($link), $second_chain);
							}
						}
						$second_chain[1]->typegroup_id = $second_chain[0]->hidden_id;
						$second_chain[1]->typegroup_type = $second_chain[0]->hidden_type;
						$groupsbranch = array_merge(array($second_chain[1]), $groupsbranch);


						$present = false;
						foreach ($farm_item['type_groups'] as $index => $typegroup) {
							if ((gettype($typegroup) == 'array' ? $typegroup['hidden_type'] : $typegroup->hidden_type) == (gettype($second_chain[0]) == 'array' ? $second_chain[0]['hidden_type'] : $second_chain[0]->hidden_type)) {
								$present = true;
								break;
							}
						}

						if (!$present) {
							$farm_item['type_groups'][] = $second_chain[0];
						}

						$farm_item['groups'][$i] = $groupsbranch;
					}
				}
				$farmbranch[$k] = $farm_item;
			}
			$farms_path[$z] = $farmbranch;
		}

		// конец куска кода который впихивает типы номенклатур "Материалы - Семена" и т.д. писал Андрей

		// дальше идет кусок который удаляет группы номенклатур в которых нету подгрупп и под названий . тоесть пустые

		foreach ($farms_path as $z => &$farmbranch) {
			$tech_arr = array();
			foreach ($farmbranch as $k => &$farm_item) {
				$tech_arr = array();

				foreach ($farm_item['groups'] as $swarm) {
					$uniqueswarm = $swarm;

					for($p=count($uniqueswarm)-1;$p>-1;$p--){
						$swarmling = $uniqueswarm[$p];
						if($swarmling->generated){continue;}
						$found = 0;
						foreach($farm_item['items'] as $item_){
							$item = $item_['item'];
							if((int)$item->group->id()==(int)$swarmling->id() && strcmp($item->group->meta()->model(),$swarmling->meta()->model())==0){
								$found=1;
								break;
							}
						}
						$found_other = 0;
						for($o=count($uniqueswarm)-1;$o>-1;$o--){
							$swarmling2 = $uniqueswarm[$o];
							if($swarmling2->generated){continue;}
							if(($swarmling2->group ? $swarmling2->group->id() : $swarmling2->parent->id())==$swarmling->id() ){
								$found_other=1;
								break;
							}
						}
						if(($found===0) && ($found_other===0)){
							array_splice($uniqueswarm,$p,1);
						}
					}

					$tech_arr[] = $uniqueswarm;
				}

				$farm_item['groups'] = $tech_arr;
				$farmbranch[$k] = $farm_item;
			}
			$farms_path[$z] = $farmbranch;
		}
		///

		// а теперь сортировка названий номенклатур как в справочнике
		foreach($farms_path as &$farmbranch){
			foreach($farmbranch as &$farmitem){
				$dict = array();
				$new_groups = array();
				foreach($farmitem['groups'] as $i => $groupbranch){
					$dict['n'.$i] = $groupbranch[0]->name;
				}
				$new_dict = array();
				foreach($this->nomenclature_subtree as $block){
					foreach($dict as $id => $dictword){
						if($dictword==$block[1]){
							$new_dict[$id] = $dictword;
						}
					}
				}
				$dict = $new_dict;

				foreach($dict as $key => $value){
					$new_groups[] = $farmitem['groups'][substr($key,1)];
				}
				$farmitem['groups'] = $new_groups;
			}
		}


		// а теперь сортировка по алфавиту
		foreach($farms_path as &$farmbranch){
			foreach($farmbranch as &$farmitem){
				foreach($farmitem['groups'] as &$groupbranch){
					$dict = array();
					foreach($groupbranch as $index => $group){
						if($index==0){continue;}

						$parent_names = array();

						$path = $group->path;

						if(!$path){
							if(!$group->group){
								$path = '/';
							} else {
								$path=$group->group->path.$group->group->id().'/';
							}

							$ids = explode('/',$path);
							foreach($ids as $id){
								if($id){
									$p = Jelly::select($group->group->meta()->model(),$id);
									$parent_names[] = $p->name;
								}
							}
							$parent_names[] = $group->name;

						} else {
							$ids = explode('/',$path);
							foreach($ids as $id){
								if($id){
									$p = Jelly::select($group->meta()->model(),$id);
									$parent_names[] = $p->name;
								}

							}
						}
						$dict[] = array(   (count($parent_names)>0 ? '/'.implode('/',$parent_names) : '').'/'.$group->name,$index);
					}
					asort($dict);
					$new_groups = array();
					foreach($dict as $dictitem){
						$new_groups[] = $groupbranch[(int)$dictitem[count($dictitem)-1]];
					}
					$groupbranch = array_merge(array($groupbranch[0]),$new_groups);
				}
				// насильно сортируем названия номенклатур по алфавиту
				$dict = array();
				foreach($farmitem['items'] as $ind => $item){
					$dict[] = array($item['item']->name, $ind);
				}
				asort($dict);
				$new_itms = array();
				foreach($dict as $t_arr){
					$new_itms[] = $farmitem['items'][$t_arr[1]];
				}
				$farmitem['items'] = $new_itms;

				// насильно сортируем группы типов номенклатур : Материалы, а потом Продукция
				$t_groups = array();
				foreach($farmitem['type_groups'] as $ind => $type_group){
					$t_groups[] = array($type_group->name, $ind);
				}
				asort($t_groups);
				$new_type_groups = array();
				foreach($t_groups as $t_arr){
					$new_type_groups[] = $farmitem['type_groups'][$t_arr[1]];
				}
				$farmitem['type_groups'] = $new_type_groups;

			}
		}
		$view = Twig::factory('/client/handbookversionname/handbookversiontable.html');

		$view->model = $farms_path;
		$view->farm_branches_to_farmid = $farm_branches_to_farmid;
		$view->atks = Jelly::select('client_planning_atk')->
				with('atk_type')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('handbook_version', '=', (gettype($model)=='array')?$model['id']:$model->id())->
				execute();
		$view->plans = Jelly::select('client_planning_plan')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('handbook_version', '=', (gettype($model)=='array')?$model['id']:$model->id())->
				execute();


		$this->request->response = JSON::reply($view->render());
	}

	public function inner_update($id) {
		if ($post_id = arr::get($_POST, '_id', NULL)) {
			return;
		}

		$model = Jelly::select($this->model_name, (int) $id);
		$model->datetime = arr::get($_POST, 'datetime', time());
		$model->update_datetime = $model->datetime;
		$model->save();



		if (is_null($user = $this->auth_user()))
			return;

		$farms = Jelly::factory('farm')->get_session_farms();
		if (!count($farms))
			$farms = array(-1);

		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);

		$data = Jelly::select('client_handbookversion')->
				where('version_date', '=', 0)->
				where('license', '=', $user->license->id())->
				and_where('farm', '=', $model->farm->id())->
				and_where('period', '=', $model->period->id())->
				execute();

		foreach ($data as $datum) {
			$new_datum = Jelly::factory('client_handbookversion');
			$arr = $datum->as_array();
			unset($arr['id']);
			unset($arr['_id']);
			$new_datum->set($arr);
			$new_datum->planned_price = $new_datum->discount_price;
			$new_datum->planned_price_units = $new_datum->amount_units;
			$new_datum->planned_price_manual = 0;
			$new_datum->version_date = $model->datetime;
			$new_datum->license = Auth::instance()->get_user()->license->id();
			$new_datum->save();
		}
		$_POST = array(
			'id'=>$model->id(),
			'update_to'=>'n1',
			'update_atks'=>''
		);
		$this->action_update_version($model->id());
	}

	private function auth_user() {
		if (!(($user = Auth::instance()->get_user()) instanceof Jelly_Model) or !$user->loaded()) {
			$this->request->response = JSON::error(__("User ID is not specified"));
			return NULL;
		}

		return $user;
	}

	public function action_update() {


		if ($id = arr::get($_POST, '_id', NULL)) {

			$model = Jelly::select($this->model_name, (int) $id);
			if (!($model instanceof Jelly_Model) or !$model->loaded())
				throw new Kohana_Exception('Record Not Found!');
		}else {
			$model = Jelly::factory($this->model_name);
		}

		$model->update_date = time();


		if (!$id) {
			$check = Jelly::select($this->model_name)->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->
					where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int) $_POST[$this->group_field] : 0)->
					load();
		} else {
			$check = Jelly::select($this->model_name)->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('name', 'LIKE', trim(Arr::get($_POST, 'name', null)))->
					where($this->group_field, '=', isset($_POST[$this->group_field]) ? (int) $_POST[$this->group_field] : 0)->
					where(':primary_key', '!=', (int) $id)->
					load();
		}

		if (($check instanceof Jelly_Model) and $check->loaded()) {
			$this->request->response = JSON::error('Уже есть такая запись в другой группе (' . $check->group->name . ') !');
			return;
		}


		$this->validate_data($_POST);

		$model->set($_POST);

		$model->deleted = 0;
		$model->license = Auth::instance()->get_user()->license->id();

		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);
		$_POST['period'] = (int) $periods[0];
		$period_id = (int) $periods[0];
		$model->period = $period_id;

		$model->save();

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

		$culture_id = $model->id();

		$this->request->response = JSON::success(array('script' => 'Запись сохранена успешно!',
					'url' => null,
					'success' => true,
					'item_id' => $culture_id));
	}

	public function action_update_version($id) {
		setlocale(LC_NUMERIC, 'C');
		if(!$post_id = arr::get($_POST, 'id', NULL) || is_null($user = $this->auth_user())) return;
        

		if(!$id) $id=$post_id;
        if($id=='void'){$id=null;}
        $now = time();


        if(isset($_POST['update_to']) && $_POST['update_to']=='n2'){// обновить до текущей даты
            $to_now = true;
        }else if(isset($_POST['update_to']) && $_POST['update_to']=='n1'){// обновить до даты создания Версии
            $to_now = false;
        }else{

			if(isset($_POST['update_atks']) && $_POST['update_atks'] ){
				$atk_ids = arr::get($_POST, 'update_atks', '');
				if($atk_ids){
					$atk_ids = explode(',',$atk_ids);
				}else{
					$atk_ids = array();
				}
				foreach($atk_ids as $atk_id){
					Jelly::factory('Client_Planning_Atk')->get_atk_finances(mb_substr($atk_id,1),$id,true);
				}
			}
            
            if(isset($_POST['update_operations']) && $_POST['update_operations'] ){
				$op_ids = arr::get($_POST, 'update_operations', '');
                $atk_id = arr::get($_POST, 'atk_id', '');
				if($op_ids){
					$op_ids = explode(',',$op_ids);
				}else{
					$op_ids = array();
				}
				foreach($op_ids as $op_id){
					Jelly::factory('Client_Planning_Atk2Operation')->upd_operation(mb_substr($op_id,1),$atk_id);
				}
			}
            
            if(isset($_POST['update_atkoperations']) && $_POST['update_atkoperations'] ){
				$atk_ids = arr::get($_POST, 'update_atkoperations', '');
                $op_id = arr::get($_POST, 'operation_id', '');
				if($atk_ids){
					$atk_ids = explode(',',$atk_ids);
				}else{
					$atk_ids = array();
				}
				foreach($atk_ids as $atk_id){
					Jelly::factory('Client_Planning_Atk2Operation')->upd_operation($op_id,mb_substr($atk_id,1));
				}
			}

			if(isset($_POST['update_plans']) && $_POST['update_plans'] ){
				$plan_ids = arr::get($_POST, 'update_plans', '');
				if($plan_ids) $plan_ids = explode(',', $plan_ids);
				else		  $plan_ids = array();

				foreach($plan_ids as $plan_id){
                    $plan = Jelly::select('client_planning_plan',(int)$plan_id);
					Jelly::factory('client_planning_plan')->update_plan_finances(mb_substr($plan_id,1), $id ? $id : $plan->handbook_version->id() );
				}
			}
            
            return;
        }
        // ------------------------------------------------
        
		$version_name = Jelly::select($this->model_name, (int) $id);
		if(!$version_name instanceof Jelly_Model || !$version_name->loaded())return;

		$license_id = $version_name->license->id();
		$farm = $version_name->farm->id();
		$period = $version_name->period->id();

//        $last_remains = $this->get_last_remains($license_id, $period, $farm, $to_now ? $now : $version_name->datetime);

        $where_arr = array();
        $where_arr[] = 'transactions.deleted = 0';
        $where_arr[] = 'transactions.license_id = '.$license_id;
        $where_arr[] = 'transactions.farm_id = '.$farm;
        $where_arr[] = 'transactions.period_id = '.$period;
        $where_arr[] = 'transaction_nomenclatures.deleted = 0';
        $where_arr[] = 'transaction_nomenclatures.client_transaction_id = transactions._id';
        $where_arr[] = 'transactions.transaction_date <= '.($to_now ? $now : $version_name->datetime);
//        if(isset($last_remains['transaction_date'])) {
//			$where_arr[] = 'transactions.transaction_date >= '.$last_remains['transaction_date'];
//		}
        $WHERE = 'WHERE '.implode(' AND ', $where_arr);

        $query = "SELECT *
					FROM transactions, transaction_nomenclatures
				  $WHERE";

		$db = Database::instance();
		$result = $db->query(DATABASE::SELECT, $query, true);
		$data = array();
		foreach($result as $row){
		   $data[] = (array)$row;
		}

        //убираем транзакции, которые были в тот же день с останками, но созданы раньше их
//        if(isset($last_remains['transaction_date'])){
//            for($i=count($data)-1; $i>=0; $i--){
//                if(($data[$i]['transaction_date']==$last_remains['transaction_date'] && $data[$i]['create_date']<$last_remains['create_date']) || $data[$i]['type']=='5'){
//					array_splice($data, $i, 1);
//                }
//            }
//            $data[] = $last_remains;
//        }
		$touched_ids = array();
		foreach($data as &$datum){

			if(isset($datum['used'])){continue;}

			$max_remains_date = 0;
			foreach($data as &$d){
				if(
						$d['nomenclature_model']==$datum['nomenclature_model'] &&
						$d['nomenclature_id']==$datum['nomenclature_id'] &&
						(
						  (
								   in_array($datum['amount_units'],array(1,2,3,4))
								&& in_array($d['amount_units'],    array(1,2,3,4))
						  )
						   ||
						  (
								   !in_array($datum['amount_units'],array(1,2,3,4))
								&& ($d['amount_units']==$datum['amount_units'])
						  )
						)
						&&
						$d['type']==5 &&
						$d['price_without_nds_units']==$datum['price_without_nds_units']
				){
					if((int)$d['transaction_date']>$max_remains_date){
						$max_remains_date = (int)$d['transaction_date'];
					}
				}
			}

			if(!  (  ($max_remains_date<=$datum['transaction_date']) && ($datum['transaction_date']<=($to_now ? $now : $version_name->datetime))  )   ){continue;}


			$operate_with = array();


			foreach($data as &$d){
				if(
						$d['nomenclature_model']==$datum['nomenclature_model'] &&
						$d['nomenclature_id']==$datum['nomenclature_id'] &&
						(
							(
								in_array($datum['amount_units'],array(1,2,3,4)) &&
								in_array($d['amount_units'],array(1,2,3,4))
							)
							 ||
							(
									 !in_array($datum['amount_units'],array(1,2,3,4))
								  && ($d['amount_units']==$datum['amount_units'])
							)
						)
						&& $d['price_without_nds_units']==$datum['price_without_nds_units']
						&& (  ($max_remains_date<=$d['transaction_date']) && ($d['transaction_date']<=($to_now ? $now : $version_name->datetime))  )
				){
					$operate_with[] = $d;
					$d['used'] = true;
				}
			}

			$amount = 0;
			$discount_price = 0;
			$chislitel = 0;
			$znamenatiel = 0;

			foreach($operate_with as &$item){

				if($item['amount_units']==2) $item['amount'] *= 0.1;
				if($item['amount_units']==3) $item['amount'] *= 0.001;
				if($item['amount_units']==4) $item['amount'] *= 0.000001;

				if($item['amount_units']==2) $item['price_without_nds'] *= 10;
				if($item['amount_units']==3) $item['price_without_nds'] *= 1000;
				if($item['amount_units']==4) $item['price_without_nds'] *= 1000000;

				$item['amount_units'] = ($item['amount_units']==2 || $item['amount_units']==3 || $item['amount_units']==4) ? 1 : $item['amount_units'];

				$amount += $item['amount'] * (($item['type']==3 || $item['type']==4) ? -1:1);
				if(!($item['type']==3 || $item['type']==4)){
					$chislitel += $item['amount']*$item['price_without_nds'];
					$znamenatiel += $item['amount'];
				}
			}
			$discount_price = $znamenatiel>0 ? $chislitel/$znamenatiel : 0;
			$am_units = in_array($datum['amount_units'],array(1,2,3,4)) ? 1 : $datum['amount_units'];

			$handbook_version = Jelly::select('client_handbookversion')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
																	   ->and_where('license', '=', $license_id)
																	   ->and_where('farm', '=', $farm)
																	   ->and_where('period', '=', $period)
																	   ->and_where('nomenclature_model', '=', $datum['nomenclature_model'])
																	   ->and_where('nomenclature_id', '=', $datum['nomenclature_id'])
																	   ->and_where('amount_units', '=', $am_units)
																	   ->and_where('version_date', '=', $version_name->datetime)
																	   ->and_where('discount_price_units', '=', $datum['price_without_nds_units'])
																	   ->limit(1)->execute();
			if(!$handbook_version instanceof Jelly_Model || !$handbook_version->loaded()){
				$handbook_version = Jelly::factory('client_handbookversion');
				$handbook_version->nomenclature_model = $datum['nomenclature_model'];
				$handbook_version->nomenclature_id = $datum['nomenclature_id'];
				$handbook_version->amount_units = $datum['amount_units'];
				$handbook_version->discount_price_units = $datum['price_without_nds_units'];
				$handbook_version->planned_price = $discount_price;
				$handbook_version->planned_price_units = ($datum['amount_units']==2 || $datum['amount_units']==3 || $datum['amount_units']==4) ? 1 : $datum['amount_units'];
				$handbook_version->planned_price_manual = 0;
				$handbook_version->version_date = $to_now ? $now : $version_name->datetime;
				$handbook_version->license = $license_id;
				$handbook_version->farm = $farm;
				$handbook_version->period = $period;
			}
			$handbook_version->amount = $amount;
			$handbook_version->update_date = $now;
			$handbook_version->discount_price = $discount_price;
			if(false){
//            if(!$handbook_version->planned_price_manual){
				$handbook_version->planned_price = $discount_price;
				$handbook_version->planned_price_units = ($datum['amount_units']==2 || $datum['amount_units']==3 || $datum['amount_units']==4) ? 1 : $datum['amount_units'];
			}
			$handbook_version->version_date = $to_now ? $now : $version_name->datetime;
			$handbook_version->amount_units = in_array($handbook_version->amount_units,array(1,2,3,4))?1:$handbook_version->amount_units;
			$handbook_version->save();
			$touched_ids[] = (int)($handbook_version->id());
		}

		if(count($touched_ids)==0){
			$to_delete = Jelly::select('client_handbookversion')
			->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
			->and_where('license', '=', $license_id)
			->and_where('farm', '=', $farm)
			->and_where('period', '=', $period)
			->and_where('version_date', '=', $version_name->datetime)
			->execute();
		} else {
			$to_delete = Jelly::select('client_handbookversion')
			->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
			->and_where('license', '=', $license_id)
			->and_where('farm', '=', $farm)
			->and_where('period', '=', $period)
			->and_where('version_date', '=', $version_name->datetime)
			->and_where('_id', 'NOT IN', $touched_ids)
			->execute();
		}


		foreach($to_delete as $del){
			$del->amount = 0;
			$del->amount_units = 1;
			$del->discount_price = 0;
			$del->discount_price_units = 1;

			if(false){
//            if(!$del->planned_price_manual){
				$del->planned_price = 0;
				$del->planned_price_units = 1;
			}

			$del->version_date = $to_now ? $now : $version_name->datetime;
			$del->save();
		}

		$version_name->update_datetime = $now;
		$version_name->datetime = $to_now ? $now : $version_name->datetime;
		$version_name->outdated = 0;
		$version_name->save();


		if(isset($_POST['update_operations'])){
            $op_ids = arr::get($_POST, 'update_operations', '');
            $atk_id = arr::get($_POST, 'atk_id', '');
            if($op_ids){
                $op_ids = explode(',',$op_ids);
            }else{
                $op_ids = array();
            }
            foreach($op_ids as $op_id){
                Jelly::factory('Client_Planning_Atk2Operation')->upd_operation(mb_substr($op_id,1),$atk_id);
            }
        }
        
        if(isset($_POST['update_atks'])){
			$atk_ids = arr::get($_POST, 'update_atks', '');
			if($atk_ids){
				$atk_ids = explode(',',$atk_ids);
			}else{
				$atk_ids = array();
			}

			foreach($atk_ids as $atk_id){
				Jelly::factory('Client_Planning_Atk')->get_atk_finances(mb_substr($atk_id,1),$id,true);
			}
		}

		if(isset($_POST['update_plans'])){
			$plan_ids = arr::get($_POST, 'update_plans', '');
			if($plan_ids) $plan_ids = explode(',', $plan_ids);
			else		  $plan_ids = array();

			foreach($plan_ids as $plan_id){
				Jelly::factory('client_planning_plan')->update_plan_finances(mb_substr($plan_id,1), $id);
			}
		}


        $this->request->response = JSON::success(array(
			'script' => 'Запись сохранена успешно!',
			'url' => null,
			'success' => true,
			'item_id' => $id,
			'new_datetime' => $version_name->datetime,
		));

    }

	private function get_last_remains($license_id, $period, $farm, $datetime){
        $where_arr = array();
        $where_arr[] = 'transactions.deleted = 0';
        $where_arr[] = 'transactions.type = 5';
        $where_arr[] = 'transactions.license_id = '.$license_id;
        $where_arr[] = 'transactions.farm_id = '.$farm;
        $where_arr[] = 'transactions.period_id = '.$period;
        $where_arr[] = 'transaction_nomenclatures.deleted = 0';
        $where_arr[] = 'transaction_nomenclatures.client_transaction_id = transactions._id';
        $where_arr[] = 'transactions.transaction_date = (select max(transaction_date) from transactions)';
		$where_arr[] = 'transactions.transaction_date < '.$datetime;
        $WHERE = 'WHERE '.implode(' AND ', $where_arr);

        $query = "SELECT transactions.type,
                         transactions.transaction_date,
                         transactions.create_date,
                         transaction_nomenclatures.amount,
						 transaction_nomenclatures.amount_units,
						 transaction_nomenclatures.nomenclature_model,
						 transaction_nomenclatures.nomenclature_id,
                         transaction_nomenclatures.price_without_nds,
						 transaction_nomenclatures.price_without_nds_units
					FROM transactions, transaction_nomenclatures
				  $WHERE
                ORDER BY transactions.create_date DESC";

		$db = Database::instance();
		$result = $db->query(DATABASE::SELECT, $query, true);
		$data = array();
		foreach($result as $row){
		   $data = (array)$row; break;
		}

        return $data;
    }

	public function action_copy_books() {
		$ids = arr::get($_POST,'checked','');
		if($ids){
			$ids_arr = explode(',',$ids);
		}else{
			$ids_arr = array();
		}

		foreach($ids_arr as $id){
			$clear_id = (int)( substr($id,1) );
			$versionname_obj = Jelly::select($this->model_name,$clear_id);
			$versionname = array(
				'name'=>$versionname_obj->name,
				'datetime'=>$versionname_obj->datetime,
				'license'=>$versionname_obj->license->id(),
				'farm'=>$versionname_obj->farm->id(),
				'period'=>$versionname_obj->period->id(),
				'outdated'=>$versionname_obj->outdated,
				'update_datetime'=>$versionname_obj->update_datetime,
				'color'=>$versionname_obj->color,


			);

//			$time = time();
			$time = $versionname_obj->datetime + 1;

			$handbook_versions = Jelly::select('client_handbookversion')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('version_date', '=', $versionname['datetime'])->
					where('license', '=', $versionname['license'])->
					and_where('farm', '=', $versionname['farm'])->
					and_where('period', '=', $versionname['period'])->
					execute();

			foreach($handbook_versions as $version){
				$arr = $version->as_array();
				unset($arr['id']);
				unset($arr['_id']);
				$arr['version_date'] = $time;
				$version_copy = Jelly::factory('client_handbookversion')->set($arr)->save();
			}



			unset($versionname['id']);
			unset($versionname['_id']);
			$versionname['name'] = $versionname['name'].' копия';
			$versionname['datetime'] = $time;
			$versionname_copy = Jelly::factory($this->model_name)->set($versionname)->save();
		}
	}

	public function action_tree() {
		$extras = Arr::get($_GET, 'extras', true);
        $truncate = Arr::get($_GET, 'truncate', true);
		$user = Auth::instance()->get_user();

		$data = Jelly::factory('client_handbookversionname')->get_tree($user->license->id(), $this->group_field, array(), $extras, $truncate);
		$this->request->response = Json::arr($data, count($data));
	}

	public function action_delete($id = null){

		$del_ids = arr::get($_POST, 'del_ids', '');
		if($del_ids){
			$del_ids = explode(',', $del_ids);
		} else {
			$del_ids = array();
		}


		for($i=0; $i<count($del_ids); $i++){

			$id = mb_substr($del_ids[$i], 0, 1)=='n' ? mb_substr($del_ids[$i], 1) : $del_ids[$i];

			$model = Jelly::select($this->model_name, (int)$id);

			if(!($model instanceof Jelly_Model) or !$model->loaded())	{
				$this->request->response = JSON::error('Записи не найдены.');
				return;
			}

			$linked_atks = Jelly::select('client_planning_atk')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('handbook_version', '=', $model->id() )->
					execute();

			if( ( $linked_atks->count() )>0 ){

				$atk_names = array();
				foreach($linked_atks as $atk){
					$atk_names[] = $atk->name;
				}
				$atk_string = implode(', ', $atk_names);

				$this->request->response = JSON::error('Нельзя удалить Плановый справочник, т.к. есть связанные АТК: '.$atk_string.' .');
				throw new Kohana_Exception('Нельзя удалить Плановый справочник, т.к. есть связанные АТК: '.$atk_string.' .');

				return;

			}else{
				$model->delete();
			}


		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

    public function action_getnomenclature($id = NULL) {
        $model = Arr::get($_POST, 'model', '');
		$user = Auth::instance()->get_user();

        $versname = Jelly::select('client_handbookversionname',(int)$id);
        $rows = Jelly::select('Client_HandbookVersion')->
                where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                where('version_date','=',$versname->datetime)->
                where('farm','=',$versname->farm->id())->
                where('period','=',$versname->period->id())->
                where('license','=',$user->license->id())->
                where('nomenclature_model','LIKE','%'.$model.'%')->
                execute();

        $red_checks = array();
        $black_checks = array();
        foreach($rows as $row){
            if(!$row->amount){
                $black_checks[] = $row->nomenclature_id;
            }else{
                $red_checks[] = $row->nomenclature_id;
            }
        }

        $this->request->response = JSON::success(
                array(
                    'data' => JSON::arr(
                        array('red_checks' => $red_checks, 'black_checks' => $black_checks),
                        count($red_checks)+count($black_checks)
                    ),
                    'url' => null,
                    'success' => true)

        );
    }

    public function action_addnomenclature($version_id = NULL) {

        $model = Arr::get($_POST, 'model', '');
        $ids = Arr::get($_POST, 'checked', '');
        $ids = explode(',', $ids);
        foreach($ids as &$id){
            $id = (int)substr($id,1);
        }

		$user = Auth::instance()->get_user();

        $versname = Jelly::select('client_handbookversionname',(int)$version_id);
        $rows = Jelly::select('Client_HandbookVersion')->
                where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
                where('version_date','=',$versname->datetime)->
                where('farm','=',$versname->farm->id())->
                where('period','=',$versname->period->id())->
                where('license','=',$user->license->id())->
                where('nomenclature_model','LIKE','%'.$model.'%')->
//                where('amount','=',false)->
//                where('nomenclature_model_id','=',false)->
                execute();

        $deleted_ids = array();
		$rows_ids = array();
        foreach($rows as $row){
            $rows_ids[] = (int)$row->nomenclature_id;
            if(!in_array((int)$row->nomenclature_id,$ids )){
                if( (!$row->amount || !$row->discount_price) ){   // && !$row->planned_price_manual

					$row->set(array('deleted'=>1))->save();
					$deleted_ids[] = $row->id();
                }
            }
        }

		$added_ids = array();
        for($j=0;$j<count($ids);$j++){
            $aaa = $ids[$j];
            if(!in_array($aaa,$rows_ids )){
                $added_ids[] = $this->addone_nomenclature($version_id, $model, $aaa);
            }
        }


		$this->request->response = JSON::success(
                array(
                    'url' => null,
                    'success' => true,
					'added_ids'=> implode(',',$added_ids),
					'deleted_ids'=> implode(',',$deleted_ids)
				)
        );

    }

    public function addone_nomenclature($version_id, $model, $id) {
		$versionname = Jelly::select($this->model_name, $version_id);

        // AGC-1438
        $in_glo = Jelly::select('glossary_'.$model,$id);
        if($model=='productionclass'){
            $units = $in_glo->group->units;
        }else{
            $units = $in_glo->units;
        }

        if(gettype($units)=='object'){$units = $units->id();}

        if($units==4 || $units==9){
            $units = 5;
        }else if($units==28){
            $units = 6;
        }else{
            $units = 1;
        }
        // AGC-1438

        $new_yardbird = array(
            'deleted'=>0,
            'version_date'=>$versionname->datetime,
            'update_date'=>time(),
            'nomenclature_model'=> $model,
            'nomenclature_id'=> $id,
            'license'=> $versionname->license->id(),
            'farm'=> $versionname->farm->id(),
            'period'=> $versionname->period->id(),
            'amount'=> 0,
            'amount_units'=> $units,
            'discount_price'=> 0,
            'discount_price_units'=> $units,
            'planned_price'=> 0,
            'planned_price_units'=> $units,
            'planned_price_manual'=> 0

        );
        $jelly_obj = Jelly::factory('client_handbookversion')->set($new_yardbird)->save();
		return $jelly_obj->id();
	}


	public function action_cancel_addnomenclature($version_id = NULL) {

        $added_ids = Arr::get($_POST, 'added_ids', '');
		$deleted_ids = Arr::get($_POST, 'deleted_ids', '');

		$added_ids = $added_ids ? explode(',',$added_ids) : array();
		$deleted_ids = $deleted_ids ? explode(',',$deleted_ids) : array();
		foreach($deleted_ids as $id){
			$row = Jelly::select('client_handbookversion',(int)$id);
			$row->set(array('deleted'=>0))->save();

		}

		foreach($added_ids as $id){
			$row = Jelly::select('client_handbookversion',(int)$id);
			$row->set(array('deleted'=>1))->save();

		}


    }
}