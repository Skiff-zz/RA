<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Handbook_Production extends AC_Controller {
	public function action_gettable($id = null) {

		if (is_null($user = $this->auth_user()))
			return;

		$farms = Jelly::factory('farm')->get_session_farms();
		if (!count($farms))
			$farms = array(-1);

		$periods = Session::instance()->get('periods');
		if (!count($periods))
			$periods = array(-1);

		$hbook_info = Jelly::select('client_handbook')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('model', '=', 'glossary_productionclass')->
				where('license', '=', $user->license->id())->
				and_where('farm', 'IN', $farms)->
				and_where('period', 'IN', $periods)->
				order_by('farm')->
				with('farm')->
				order_by('farm.path', 'ASC')->
				execute();

		$farms = array();
		foreach($hbook_info as $hbook_row){
			$name = Jelly::select('glossary_productionclass')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					and_where('_id','=',(int)$hbook_row->item)->
					with('group')->
					limit(1)->
					execute();

			$handbook_version_items = Jelly::select('client_handbookversion')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('version_date', '=', 0)->
				where('nomenclature_model', '=', 'productionclass')->
				where('nomenclature_id', '=', $name->id())->
				where('license', '=', $hbook_row->license->id())->
				and_where('farm', '=', $hbook_row->farm->id())->
				and_where('period', '=', $hbook_row->period->id())->
				execute();

			$prices = array();
			foreach($handbook_version_items as $i => $version){
				$prices[] = array(
					'index'=>$i+1,
					'amount'=>$version->amount,
					'amount_units'=>Model_Client_TransactionNomenclature::$amount_units[$version->amount_units],
					'discount_price'=>$version->discount_price,
					'discount_price_units'=>$version->discount_price_units
				);
			}

			if(!isset($farms[$hbook_row->farm->id()])){
				$farms[$hbook_row->farm->id()] = array(
					'farm'=>$hbook_row->farm,
					'groups'=>array(
						array(
							'group'=>$name->group,
							'items'=>array(
								array(
									'item'=>$name,
									'prices'=>$prices

								)
							)
						)
					)
				);
			} else {
				$found = false;
				foreach( $farms[$hbook_row->farm->id()]['groups'] as &$group ){
					if($group['group']->id()==$name->group->id()){
						$group['items'][] = array(
							'item'=>$name,
							'prices'=>$prices
						);
						$found = true;
					}
				}

				if(!$found){
					$farms[$hbook_row->farm->id()]['groups'][] = array(
						'group'=>$name->group,
						'items'=>array(array(
							'item'=>$name,
							'prices'=>$prices
						))
					);
				}
			}

		}
		$f = array();
		foreach($farms as $farm){
			$f[] = $farm;
		}



		$farms_dict = array();
		foreach($f as $i => &$farm_division){
			$groups_dict = array();
			foreach($farm_division['groups'] as $j => &$group_division){
				$dict = array();

				foreach($group_division['items'] as $k => $name){
					$dict[mb_strtolower($name['item']->name)] = $k;
				}

				ksort($dict);
				$new = array();
				foreach($dict as $name => $index){

					$new[] = $group_division['items'][$index];
				}

				$group_division['items'] = $new;

				$groups_dict[mb_strtolower($group_division['group']->name)] = $j;
			}
			ksort($groups_dict);

			$new = array();
			foreach($groups_dict as $name => $index){
				$new[] = $farm_division['groups'][$index];
			}
			$farm_division['groups'] = $new;

			$farms_dict[mb_strtolower($farm_division['farm']->name)] = $i;
		}

		ksort($farms_dict);
		$new = array();
		foreach($farms_dict as $name => $index){
			$new[] = $f[$index];
		}
		$f = $new;


		$view = Twig::factory('/client/handbook/production/gettable.html');
		$view->model = $f;

		print_r(JSON::reply($view->render()));exit;;
//		$this->request->response = JSON::reply($view->render());

	}

	private function auth_user() {
		if (!(($user = Auth::instance()->get_user()) instanceof Jelly_Model) or !$user->loaded()) {
			$this->request->response = JSON::error(__("User ID is not specified"));
			return NULL;
		}

		return $user;
	}
}