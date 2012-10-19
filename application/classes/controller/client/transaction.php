<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Client_Transaction extends AC_Controller
{

	public $auto_render  = false;


	public function action_index(){}


	public function action_list(){
        setlocale(LC_NUMERIC, 'C');
		$mode = Arr::get($_GET, 'mode', 'nomenclature');
        $farm_id = Arr::get($_GET, 'farm', 0);
        $mode_id = Arr::get($_GET, 'modeId', '');

		$license_id = Auth::instance()->get_user()->license->id();

		$data = Jelly::factory('client_transaction')->getFilteredData($license_id, $mode, $mode_id, $farm_id);
        $data = Jelly::factory('client_transaction')->group_data($license_id, $data, $mode);
        //print_r($data); exit;
		$view = Twig::factory('client/transaction/list');
        $view->mode = $mode;
        $view->data = $data;


		$this->request->response = JSON::success(array('script' => $view->render(), 'success' => true));
	}

    public function action_list_summary(){
		$user = Auth::instance()->get_user();
        setlocale(LC_NUMERIC, 'C');
		$data = Jelly::factory('client_transaction')->get_summary_data($user->license->id());

		$view = Twig::factory('client/transaction/list_summary');
		$view->items = $data['blocks'];
		$view->farms = $data['farms'];

		$this->request->response = JSON::success(array('script' => $view->render(), 'success' => true));
	}

    public function action_number(){

		while(true){
			$number = rand(10000, 99999);
			$test	 = Jelly::select('client_transaction')->where('number', '=', $number)->limit(1)->execute();
			if($test instanceof Jell_Model and $test->loaded()) continue;
			else break;
		}

		$this->request->response = JSON::success(array('number' => $number, 'success' => true));
	}







	public function action_read($id = null){
		return $this->action_edit($id, null, true);
	}

	public function action_edit($id = null, $transaction_type = null, $read = false, $farm_id = 0){

		$transaction = null;

		if($id){
            $transaction = Jelly::select('client_transaction')->with('nomenclature')->load((int)$id);
            if(!($transaction instanceof Jelly_Model) or !$transaction->loaded()){
                $this->request->response = JSON::error('Транзакция не найдена!'); return;
			}
        }

		$view = Twig::factory('client/transaction/edit');
		$view->edit = !$read;
		$view->transaction = array();
		$view->ammount_units = Model_Client_TransactionNomenclature::$amount_units;
		$view->money_units = Model_Client_TransactionNomenclature::$money_units;

		$user = Auth::instance()->get_user();
		$farms = Jelly::factory('farm')->get_session_farms();
		if(!count($farms))$farms = array(-1);
		$periods = Session::instance()->get('periods');
		if(!count($periods)) $periods = array(-1);

		if($transaction){
            $view->transaction = $transaction->as_array();
			$view->transaction_date = array('day' => date('d', (int)$transaction->transaction_date), 'month' => date('m', (int)$transaction->transaction_date), 'year' => date('Y', (int)$transaction->transaction_date) );
			$view->contract_date = array('day' => date('d', (int)$transaction->contract_date), 'month' => date('m', (int)$transaction->contract_date), 'year' => date('Y', (int)$transaction->contract_date) );

			if($transaction->type==1){
				$contragent = Jelly::select('client_contragent')->load($transaction->contragent_id);
				$view->transaction['supplier'] = array('_id'=>$contragent->id(), 'name'=>$contragent->name);
				$view->transaction['customer'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
			}
			if($transaction->type==3){
				$contragent = Jelly::select('client_contragent')->load($transaction->contragent_id);
				$view->transaction['supplier'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
				$view->transaction['customer'] = array('_id'=>$contragent->id(), 'name'=>$contragent->name);
			}
			if($transaction->type==2){
				$contragent = Jelly::select('farm')->load($transaction->contragent_id);
				$view->transaction['supplier'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
				$view->transaction['customer'] = array('_id'=>$contragent->id(), 'name'=>$contragent->name);
			}
			if($transaction->type==4){
				$contragent = Jelly::select('farm')->load($transaction->contragent_id);
				$view->transaction['customer'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
				$view->transaction['supplier'] = array('_id'=>$contragent->id(), 'name'=>$contragent->name);
			}
			if($transaction->type==5){
				$view->transaction['supplier'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
				$view->transaction['customer'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
			}

			$view->transaction['nomenclature'] = $view->transaction['nomenclature']->as_array();
			if($transaction->type==3 || $transaction->type==4) $handbook_versions = Jelly::select('client_handbookversion')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->and_where('farm', '=', $view->transaction['supplier']['_id'])->and_where('period', '=', $periods[0])->and_where('version_date', '=', 0)->execute()->as_array();
			for($i=count($view->transaction['nomenclature'])-1; $i>=0; $i--){
				if($view->transaction['nomenclature'][$i]['deleted']){
					array_splice($view->transaction['nomenclature'], $i, 1); continue;
				}
				$n = Jelly::select('glossary_'.$view->transaction['nomenclature'][$i]['nomenclature_model'])->load($view->transaction['nomenclature'][$i]['nomenclature_id']);
				$n_name = $n->name;
				if($view->transaction['nomenclature'][$i]['nomenclature_model']=='productionclass'){
					$production = Jelly::select('glossary_production')->load((int)$n->group->id());
					$n_name = $production->name.' '.$n_name;
				}
				$view->transaction['nomenclature'][$i]['name'] = $n_name;
				if($transaction->type==3 || $transaction->type==4)$view->transaction['nomenclature'][$i]['handbook_price'] = Jelly::factory('client_transaction')->get_handbook_price($handbook_versions, $view->transaction['nomenclature'][$i]['nomenclature_model'], $view->transaction['nomenclature'][$i]['nomenclature_id'], $view->transaction['nomenclature'][$i]['amount_units']);
				$view->transaction['nomenclature'][$i]['amount_units'] = Model_Client_TransactionNomenclature::$amount_units[$view->transaction['nomenclature'][$i]['amount_units']];
				$view->transaction['nomenclature'][$i]['price_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$view->transaction['nomenclature'][$i]['price_without_nds_units']];
				$view->transaction['nomenclature'][$i]['sum_without_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$view->transaction['nomenclature'][$i]['sum_without_nds_units']];
				$view->transaction['nomenclature'][$i]['sum_with_nds_units'] = Model_Client_TransactionNomenclature::$money_units[$view->transaction['nomenclature'][$i]['sum_with_nds_units']];
				$view->transaction['nomenclature'][$i]['nds_units'] = Model_Client_TransactionNomenclature::$money_units[$view->transaction['nomenclature'][$i]['nds_units']];
				$view->transaction['nomenclature'][$i]['tooltip'] = Jelly::factory('client_transaction')->get_nomenclature_tooltip($view->transaction['nomenclature'][$i]['nomenclature_model'], $view->transaction['nomenclature'][$i]['nomenclature_id']);
			}
			$view->transaction['nomenclature'] = array_merge($view->transaction['nomenclature'], array());

			$view->transaction['license'] = $transaction->license->id();
			$view->transaction['farm'] = array('_id'=>$transaction->farm->id(), 'name'=>$transaction->farm->name);
        }else{
			$view->transaction['type'] = $transaction_type;
			$view->transaction_date = array('day' => date('d', time()), 'month' => date('m', time()), 'year' => date('Y', time()) );
			$view->contract_date = array('day' => date('d', time()), 'month' => date('m', time()), 'year' => date('Y', time()) );

            if(count($farms)){
                if(!$farm_id) $farm_id = arr::get($farms, 0, false);

				$farm = Jelly::select('farm')->load((int)$farm_id);
				if(($farm instanceof Jelly_Model) && $farm->loaded()){
					$view->transaction['supplier'] = ($transaction_type==1) ? '' : array('_id'=>$farm->id(), 'name'=>$farm->name);
					$view->transaction['customer'] = ($transaction_type==3) ? '' : array('_id'=>$farm->id(), 'name'=>$farm->name);
				}
			}
		}

		//print_r($view->transaction); exit;
		$this->request->response = JSON::reply($view->render());
	}


	public function action_create($transaction_type){
        $farm_id = (int)Arr::get($_GET, 'farm_id', 0);
        if(array_key_exists('_id', $_POST)) unset($_POST['_id']);
        return $this->action_edit(null, $transaction_type, false, $farm_id);
    }


	public function action_update(){
		$values = array('number', 'transaction_date', 'type', 'contract_number', 'contract_date', 'total_without_nds', 'total_with_nds', 'nds', 'shipper', 'recipient', 'contragent_id', 'farm');
		$transaction_id = arr::get($_POST, '_id', NULL);
		$_POST['transaction_date'] = strtotime(ACDate::convertMonth($_POST['transaction_date']));

		$dmY = (int)date('Ymd',$_POST['transaction_date']);
		$dmy_NOW = (int)date('Ymd'); // 20101231  например
		if($dmy_NOW == $dmY){
			if(!$transaction_id){
				$_POST['transaction_date'] = time();
			}else{
				$_POST['transaction_date'] = $_POST['transaction_date'];
			}
		} else {
			$_POST['transaction_date'] = strtotime($dmY);
		}

		$_POST['contract_date'] = strtotime(ACDate::convertMonth($_POST['contract_date']));
		$_POST['total_without_nds'] = number_format((float)trim($_POST['total_without_nds'], " грн"), 12, '.', '');
		$_POST['total_with_nds'] = number_format((float)trim($_POST['total_with_nds'], " грн"), 12, '.', '');
		$_POST['nds'] = number_format((float)trim($_POST['nds'], " грн"), 12, '.', '');
		$_POST['contragent_id'] = ($_POST['type']==1 || $_POST['type']==2) ? $_POST['supplier'] : (($_POST['type']==3 || $_POST['type']==4) ? $_POST['customer'] : '');
		$_POST['farm'] = ($_POST['type']==1 || $_POST['type']==2) ? $_POST['customer'] : $_POST['supplier'];

        if($transaction_id){
			$transaction = Jelly::select('client_transaction', (int)$transaction_id);

			$same_number = Jelly::select('client_transaction')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
															  ->where('number', '=', arr::get($_POST, 'number', ''))
															  ->and_where('type', '=', $_POST['type'])
															  ->and_where('_id', '<>', $transaction_id)
															  ->and_where('farm', '=', (int)arr::get($_POST, 'farm', 0))->execute()->count();
			if($same_number){
				$this->request->response = JSON::error('В данном хозяйстве уже существует транзакция с таким типом и номером.'); return;
			}
		}else{
			$transaction = Jelly::factory('client_transaction');

			$same_number = Jelly::select('client_transaction')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
															  ->where('number', '=', arr::get($_POST, 'number', ''))
															  ->and_where('type', '=', $_POST['type'])
															  ->and_where('farm', '=', (int)arr::get($_POST, 'farm', 0))->execute()->count();
			if($same_number){
				$this->request->response = JSON::error('В данном хозяйстве уже существует транзакция с таким типом и номером.'); return;
			}
		}

        if(!$_POST['contragent_id'] && $_POST['type']!=5){
            $this->request->response = JSON::error('Укажите значение поля "'.(($_POST['type']==3 || $_POST['type']==4) ? 'Покупатель':'Поставщик').'".'); return;
        }

		$periods = Session::instance()->get('periods');
		if(!count($periods)){ $this->request->response = JSON::error('Не выбран период.'); return; }

		$user = Auth::instance()->get_user();
        $nomenclature_list = @json_decode(arr::get($_POST, 'nomenclature_list', ''), true);

        if($_POST['type']==3 || $_POST['type']==4){
            $nomenclature_err = $this->check_nomenclature($user->license->id(), $periods[0], arr::get($_POST, 'farm', 0), $nomenclature_list);
            if($nomenclature_err){
                $this->request->response = JSON::error($nomenclature_err); return;
            }
        }

		$transaction->set(Arr::extract($_POST, $values));
		$transaction->license = $user->license->id();
		$transaction->update_date = time();
        if(!$transaction_id) $transaction->create_date = time();
		$transaction->period = $periods[0];
		$transaction_id = $transaction->save();


		Jelly::factory('client_transactionnomenclature')->clear_transaction_nomenclature($transaction->id());

		foreach($nomenclature_list as $nomenclature){
			if($nomenclature_id = arr::get($nomenclature['name'], 'db_id', NULL)){
				$nomenclature_model = Jelly::select('client_transactionnomenclature', (int)$nomenclature_id);
			}else{
				$nomenclature_model = Jelly::factory('client_transactionnomenclature');
			}

			$nomenclature_model->deleted = false;
			$nomenclature_model->update_date = time();
			$nomenclature_model->transaction = $transaction_id;

			$nomenclature_model->amount = number_format((float)trim($nomenclature['count']['value'], " тлкгрцпе."), 12, '.', '');
			$nomenclature_model->amount_units = (int)$nomenclature['count']['selectedUnits'];

			$nomenclature_model->price_without_nds = number_format((float)trim($nomenclature['price']['value'], " грн/тлкгрцпе."), 12, '.', '');
			$nomenclature_model->price_without_nds_units = (int)$nomenclature['price']['selectedUnits'];

			$nomenclature_model->sum_without_nds = number_format((float)trim($nomenclature['total']['value'], " грн"), 12, '.', '');
			$nomenclature_model->sum_without_nds_units = (int)$nomenclature['total']['selectedUnits'];

			$nomenclature_model->sum_with_nds = number_format((float)trim($nomenclature['total_with_nds']['value'], " грн"), 12, '.', '');
			$nomenclature_model->sum_with_nds_units = (int)$nomenclature['total_with_nds']['selectedUnits'];

			$nomenclature_model->nds = number_format((float)trim($nomenclature['nds']['value'], " грн"), 12, '.', '');
			$nomenclature_model->nds_units = (int)$nomenclature['nds']['selectedUnits'];


			$nomenclature_model->nomenclature_model = $nomenclature['name']['model'];
			$nomenclature_model->nomenclature_id = $nomenclature['name']['id'];

			$date1 = $_POST['transaction_date'];
			$remains = Jelly::factory('client_handbookversion')->get_last_remains($user->license->id(), $periods[0], $transaction->farm->id(), $nomenclature['name']['model'], $nomenclature['name']['id'], (int)$nomenclature['count']['selectedUnits'], (int)$nomenclature['price']['selectedUnits']);
			$date2 = empty($remains) ? time() : $remains['transaction_date'];

			$handbook_versionnames = Jelly::select('client_handbookversionname')->
				where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
				where('license', '=', $user->license->id() )->
				and_where('farm', '=', $transaction->farm->id())->
				and_where('period', '=', $periods[0])->
				execute();
			foreach($handbook_versionnames as $handbook_versionname){
				if($date1 < $handbook_versionname->update_datetime && $handbook_versionname->update_datetime < $date2){
					$handbook_versionname->outdated = 1;
					$handbook_versionname->save();
				}
			}

			$nomenclature_model->save();

            Jelly::factory('client_handbookversion')->updateCurrentHandbook($user->license->id(), $periods[0], $transaction->farm->id(), $nomenclature['name']['model'], $nomenclature['name']['id'], (int)$nomenclature['count']['selectedUnits'], (int)$nomenclature['price']['selectedUnits']);
		}

		$this->request->response = JSON::success(array('script' => 'Транзакция сохранена.', 'url' => null, 'success' => true, 'transaction_id' => $transaction->id(), 'edit' => array_key_exists('_id', $_POST), 'farm' => (int)arr::get($_POST, 'farm', 0)));
	}


	public function action_tree(){
		$is_group = filter_var(Arr::get($_GET, 'is_group', true), FILTER_VALIDATE_BOOLEAN);
		$filter = Arr::get($_GET, 'filter', false);
		$sel_farm = Arr::get($_GET, 'selFarm', false);
		$both_trees = Arr::get($_GET, 'both_trees', false);
        $with_extras = Arr::get($_GET, 'with_extras', false);
        $handbook_version = Arr::get($_GET, 'handbook_version', -1);

        if(UTF8::strpos($filter, 'technique') !== false){
            $this->get_technic_tree($filter, $is_group, $both_trees);
            return;
        }

        if(UTF8::strpos($filter, 'handbookpersonal') !== false){
            $this->get_personal_tree($is_group, $both_trees);
            return;
        }

		$prefix = UTF8::strpos($filter, 'contragent') !== false ? 'client_' : (UTF8::strpos($filter, 'glossary')===false ? 'glossary_' : '');
		$model = $is_group ? $prefix.$filter.'group' : $model = $prefix.$filter;
		if(UTF8::strpos($model, 'contragent') !== false){
			$clear_model = UTF8::str_ireplace('_customer', '', UTF8::str_ireplace('_supplier', '', $model));
		}else{
			$clear_model = $model;
		}

		if(UTF8::strpos($model, 'production') !== false){
			$model = $clear_model = $is_group ? 'glossary_production' : 'glossary_productionclass';
		}

		$user = Auth::instance()->get_user();

		if($is_group){
			$exclude_groups = Jelly::factory('client_handbook')->get_excludes($model, $user->license->id());
			$exclude_names = Jelly::factory('client_handbook')->get_excludes($prefix.$filter, $user->license->id());
			$exclude = array('groups' => $exclude_groups, 'names' => $exclude_names);
			$data =	Jelly::factory($clear_model)->get_tree($user->license->id(), $both_trees, $exclude, 'items', true);
		}else{
			$exclude = Jelly::factory('client_handbook')->get_excludes($model, $user->license->id());
			$data =	Jelly::factory($clear_model)->get_tree($user->license->id(), 'group', $exclude, true);
		}

        //добавляем единицы измерения если надо (для складского справочника единицы количества)
        if($clear_model=='glossary_seed' || $clear_model=='glossary_szr' || $clear_model=='glossary_fertilizer' || $clear_model=='glossary_gsm' || $clear_model=='glossary_productionclass'){
//			$farms = Jelly::factory('farm')->get_session_farms();
//            if(!count($farms))$farms = array(-1);
			$periods = Session::instance()->get('periods');
			if(!count($periods)) $periods = array(-1);
			$handbook_versions = Jelly::select('client_handbookversion')->where('deleted', '=', false)->and_where('license', '=', $user->license->id())->and_where('farm', '=', $sel_farm)->and_where('period', '=', $periods[0])->and_where('version_date', '=', 0)->execute()->as_array();
            foreach($data as &$record){
				$record['units'] = Jelly::factory('client_transaction')->get_units($clear_model, substr($record['id'], 1));
				$record['amount'] = Jelly::factory('client_transaction')->get_amount($handbook_versions, UTF8::str_ireplace('glossary_', '', $clear_model), substr($record['id'], 1));
				if(!empty($record['amount'])){
					$amounts = array();
					foreach($record['amount'] as $am) $amounts[] = $am['value'].''.$am['units']['name'];
					$record['title'] = (isset($record['clear_title']) ? $record['clear_title'] : $record['title']).'</div>  <div style="color: #666666; height: 28px; font-size:14px; max-width:100px; text-align:right; display:-webkit-box; -webkit-box-align:center;">'.implode(', ', $amounts).'</div><div>';
				}
			}
		}

        //добавляем единицы измерения если надо (для операций нормы и единицы норм)
        if($with_extras)
            foreach($data as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras($clear_model, substr($record['id'], 1));

        if($handbook_version>=0)
            foreach($data as &$record) $record['handbook_version_values'] = Jelly::factory('client_transaction')->get_handbook_version_values($user->license->id(), $filter, substr($record['id'], 1), $handbook_version);

		$this->request->response = Json::arr($data, count($data));
	}


	public function action_checkedglossarytree(){
		$is_group = filter_var(Arr::get($_GET, 'is_group', true), FILTER_VALIDATE_BOOLEAN);
        $dont_check = filter_var(Arr::get($_GET, 'dont_check', false), FILTER_VALIDATE_BOOLEAN);
		$model = Arr::get($_GET, 'model', false);
		$both_trees = Arr::get($_GET, 'both_trees', false);

		$prefix = UTF8::strpos($model, 'contragent') !== false ? 'client_' : (UTF8::strpos($model, 'glossary')===false ? 'glossary_' : '');
		$model = $is_group ? $prefix.$model.'group' : $prefix.$model;

		if(UTF8::strpos($model, 'contragent') !== false){
			$clear_model = UTF8::str_ireplace('_customer', '', UTF8::str_ireplace('_supplier', '', $model));
		}else{
			$clear_model = $model;
		}

		if(UTF8::strpos($model, 'production') !== false){
			$model = $clear_model = $is_group ? 'glossary_production' : 'glossary_productionclass';
		}

        if(UTF8::strpos($model, 'handbookpersonal') !== false){
			$model = $clear_model = $is_group ? 'glossary_personalgroup' : 'glossary_personal';
		}

		$user = Auth::instance()->get_user();

		if($is_group){
			$data =	Jelly::factory($clear_model)->get_tree($user->license->id(), $both_trees);
		}else{
			$data =	Jelly::factory($clear_model)->get_tree($user->license->id());
		}

		if(!$dont_check){
            Jelly::factory('client_handbook')->set_checked_records($user->license->id(), $data, $model, $is_group, $both_trees);
        }
		$this->request->response = Json::arr($data, count($data));
	}


	public function action_addnomenclature(){

		$model = Arr::get($_POST, 'model', false);
		$model_ids = Arr::get($_POST, 'ids', '');
		$model_ids = explode(',', $model_ids);
		$model_to = Arr::get($_POST, 'model_to', 'client_handbook');

		$sel_farm = (int)Arr::get($_POST, 'selFarm', 0);

		if(isset($model_ids[0]) && !trim($model_ids[0])) $model_ids = array();

		if(!$model){
			$this->request->response = JSON::error('Номенклатура не найдена.'); return;
		}

		$prefix = UTF8::strpos($model, 'contragent') !== false ? 'client_' : (UTF8::strpos($model, 'glossary')===false ? 'glossary_' : '');
		$model = $prefix.$model;

		if(UTF8::strpos($model, 'production') !== false){
			$model = 'glossary_productionclass';
		}

        $user = Auth::instance()->get_user();

        if(UTF8::strpos($model, 'personal') !== false)
		{
            foreach($model_ids as &$id) $id = 'n'.$id;


            $farms = Jelly::factory('farm')->get_session_farms();

			foreach($farms as $farm)
			{
                $_POST = array();

          		$_POST['farm_id'] = $farm;
            	$_POST['ids'] = implode(',', $model_ids);

				$request = Request::factory('/clientpc/handbook_personalgroup/addnomenclature/')->execute();
            }

            return;
		}
		else
		{
            Jelly::factory($model_to)->add_nomenclature($model, $model_ids, $user->license->id(), $sel_farm);
        }

		$this->request->response = JSON::success(array('script' => "Added", 'url' => null, 'success' => true));
	}


	public function action_list_dates(){
        $date = Arr::get($_GET, 'date', false);

		$farms = arr::get($_GET, 'farm', false);
		if($farms) $farms = array($farms);
		else	   $farms = Jelly::factory('farm')->get_session_farms();

		$user = Auth::instance()->get_user();
		$data = Jelly::factory('client_transaction')->get_dates_tree($user->license->id(), $farms, $date);
		$this->request->response = Json::arr($data, count($data));
	}


	public function action_delete($id = null){

        $user = Auth::instance()->get_user();

		$del_ids = arr::get($_POST, 'del_ids', '');
		$del_ids = explode(',', $del_ids);
		if(isset($del_ids[0]) && !trim($del_ids[0])) $del_ids = array();

		for($i=0; $i<count($del_ids); $i++){

			$transaction = Jelly::select('client_transaction', (int)$del_ids[$i]);

			if(!($transaction instanceof Jelly_Model) or !$transaction->loaded())	{
				$this->request->response = JSON::error('Записи не найдены.');
				return;
			}

            if($user->license->id()!=$transaction->license->id()){
                $this->request->response = JSON::error('У вас нет прав для удаления данной транзакции.');
				return;
            }

			$date1 = $transaction->transaction_date;
			foreach($transaction->nomenclature as $nomenclature){

				$remains = Jelly::factory('client_handbookversion')->get_last_remains($user->license->id(), $transaction->period->id(), $transaction->farm->id(),
					$nomenclature->meta()->model(), $nomenclature->id(), (int)$nomenclature->amount_units, (int)$nomenclature->price_without_nds_units);

				if(count ($remains) == 0 ){
					$date2 = time();
				} else {							//  в остатках брать какую из дат ???????
					$maxdate = $remains[0]['transaction_date'];
					foreach($remains as $remain){
						if($remain['transaction_date'] > $maxdate){$maxdate = $remain['transaction_date'];}
					}
					$date2 = $maxdate;
				}

				$handbook_versionnames = Jelly::select('client_handbookversionname')->
					where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->
					where('license', '=', $user->license->id() )->
					and_where('farm', '=', $transaction->farm->id())->
					and_where('period', '=', $transaction->period->id())->
					execute();
				foreach($handbook_versionnames as $handbook_versionname){
					if($date1 < $handbook_versionname->update_datetime && $handbook_versionname->update_datetime < $date2){
						$handbook_versionname->outdated = 1;
						$handbook_versionname->save();
					}
				}

			}

			$nomenclatures = $transaction->nomenclature->as_array();

			Jelly::factory('client_transactionnomenclature')->clear_transaction_nomenclature($transaction->id());

			$transaction_arr = $transaction->as_array();
//			$nomenclatures = Jelly::select('client_transactionnomenclature')->where('deleted', '=', false)->and_where('transaction', '=', $transaction_arr['_id'])->execute()->as_array();
			foreach($nomenclatures as $nomenclature){
				Jelly::factory('client_handbookversion')->updateCurrentHandbook($transaction_arr['license']->id(), $transaction_arr['period']->id(), $transaction_arr['farm']->id(), $nomenclature['nomenclature_model'], $nomenclature['nomenclature_id'], $nomenclature['amount_units'], $nomenclature['price_without_nds_units'], true);
			}

			$transaction->deleted = true;
			$transaction->save();

            $this->update_transaction_version($transaction->as_array());
		}

		$this->request->response = JSON::success(array('script' => 'Deleted', 'url' => null, 'success' => true));
	}

	public function action_fix_versions(){
		$res = Jelly::factory('client_handbookversion')->fix_transactions_version();
		print_r($res ? 'Success' : 'Fail'); exit;
	}


    private function update_transaction_version($transaction){
        $nomenclatures = Jelly::select('client_transactionnomenclature')->where('deleted', '=', false)->and_where('transaction', '=', $transaction['_id'])->execute()->as_array();
        foreach($nomenclatures as $nomenclature){
            Jelly::factory('client_handbookversion')->updateCurrentHandbook($transaction['license']->id(), $transaction['period']->id(), $transaction['farm']->id(), $nomenclature['nomenclature_model'], $nomenclature['nomenclature_id'], $nomenclature['amount_units'], $nomenclature['price_without_nds_units']);
        }
    }


    private function check_nomenclature($license_id, $period_id, $farm_id, $nomenclature_list){
        foreach($nomenclature_list as $nomenclature){
            $amount = (float)trim($nomenclature['count']['value'], " тлкгрцпе.");
			$koef = $nomenclature['count']['selectedUnits']==2 ? 10 :($nomenclature['count']['selectedUnits']==3 ? 1000 : ($nomenclature['count']['selectedUnits']==4 ? 1000000 : 1));
			$nomenclature['count']['selectedUnits'] = ($nomenclature['count']['selectedUnits']==2 || $nomenclature['count']['selectedUnits']==3 || $nomenclature['count']['selectedUnits']==4) ? 1 : $nomenclature['count']['selectedUnits'];
            $amount_units = Model_Client_TransactionNomenclature::$amount_units[(int)$nomenclature['count']['selectedUnits']];

			if($nomenclature_id = arr::get($nomenclature['name'], 'db_id', NULL)){
				$nomenclature_model = Jelly::select('client_transactionnomenclature', (int)$nomenclature_id);
                $delta = $amount - $nomenclature_model->amount;
			}else{
				$nomenclature_model = Jelly::factory('client_transactionnomenclature');
                $delta = $amount;
			}


            $handbook_version = Jelly::select('client_handbookversion')->where('deleted', '=', false)
                                                                       ->and_where('license', '=', $license_id)
                                                                       ->and_where('farm', '=', $farm_id)
                                                                       ->and_where('period', '=', $period_id)
                                                                       ->and_where('nomenclature_model', '=', $nomenclature['name']['model'])
                                                                       ->and_where('nomenclature_id', '=', $nomenclature['name']['id'])
                                                                       ->and_where('amount_units', '=', (int)$nomenclature['count']['selectedUnits'])
                                                                       ->and_where('version_date', '=', 0)
                                                                       ->and_where('discount_price_units', '=', (int)$nomenclature['price']['selectedUnits'])->limit(1)->execute();

            if(!$handbook_version instanceof Jelly_Model || !$handbook_version->loaded()){
                return 'Вы не можете списать номенклатуру "'.$nomenclature['name']['value'].'", так как её нет на складе.';
            }

			$ha = $koef*$handbook_version->amount;
            if($ha-$delta<0){
                return 'Вы не можете списать '.$amount.' '.$amount_units['name'].' номенклатуры "'.$nomenclature['name']['value'].'", так как на складе имеется '.($ha - ($nomenclature_id ? $nomenclature_model->amount:0)).' '.$amount_units['name'].'.';
            }

        }

        return false;
    }



    private function get_technic_tree($filter, $is_group, $both_trees){
        $model = 'client_handbook_'.$filter.($is_group ? 'group' : '');
        $user = Auth::instance()->get_user();

        if($is_group){
            $data =	Jelly::factory($model)->get_tree($user->license->id(), $both_trees);
        }else{
            $data =	Jelly::factory($model)->get_tree($user->license->id());
        }

        foreach($data as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras($model, substr($record['id'], 1));

        $this->request->response = Json::arr($data, count($data));
    }


    private function get_personal_tree($is_group, $both_trees){

        $selected_farm_groups = Session::instance()->get('farm_groups');
		$selected_farms_only  = Session::instance()->get('farms');

		$selected_farms = is_array($selected_farm_groups) ? $selected_farm_groups : array();

		$selected_farms =  array_merge($selected_farms, is_array($selected_farms_only) ? $selected_farms_only : array());

		$farm_id = $selected_farms[0];

		$_GET = array('groups' => $is_group, 'farm_id' => $farm_id);

        $request = Request::factory('/clientpc/handbook_personalgroup/farm_tree/')->execute();

		$data = json_decode($request->response, true);
		foreach($data['data'] as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras('client_handbook_personalgroup', substr($record['id'], 1));
		$request->response = json_encode($data);

        $this->request->response = $request;
        return;

		/*
		$user = Auth::instance()->get_user();
		$data =	Jelly::factory('client_handbook_personalgroup')->get_tree($user->license->id(), $both_trees);

        if($is_group){
            $last_was_child = false;
            for($i=count($data)-1; $i>=0; $i--){
                if(!$data[$i]['is_group_realy']){
                    array_splice($data, $i, 1);
                    $last_was_child = true;
                }else{
                    if($last_was_child){
                        $data[$i]['children_n'] = $data[$i]['children_g'];
                        foreach($data[$i]['children_n'] as &$ch){
                            $ch = str_replace('g', 'n', $ch);
                        }
                        $data[$i]['children_g'] = array();
                    }
                    $last_was_child = false;
                }
            }
        }else{
            for($i=count($data)-1; $i>=0; $i--){
                if($data[$i]['is_group_realy']){
                    array_splice($data, $i, 1);
                }else{
                    $data[$i]['level'] = 0;
                    $data[$i]['id'] = str_replace('g', 'n', $data[$i]['id']);
                }
            }
        }*/

//        foreach($data as &$record) $record['extras'] = Jelly::factory('client_transaction')->get_extras('client_handbook_personalgroup', substr($record['id'], 1));
//
//        $this->request->response = Json::arr($data, count($data));
    }


    public function action_get_material_price(){
		setlocale(LC_NUMERIC, 'C');
        $model = arr::get($_POST, 'model', false);
        $id = arr::get($_POST, 'id', false);
        $units = arr::get($_POST, 'units', false);
		$proper_units = arr::get($_POST, 'proper_units', false);
        $farm = arr::get($_POST, 'farm', false);
        $period = arr::get($_POST, 'period', false);
        $handbook_version = arr::get($_POST, 'handbookVersion', 0);
		$all_items = arr::get($_POST, 'allItems', 0);
		$planned = arr::get($_POST, 'planned', 0);
        $user = Auth::instance()->get_user();
		$result = $all_items ? array() : 0;

        if(!$model || !$units || (!$id && !$all_items)){
            $this->request->response = JSON::success(array('price' => $result, 'success' => true));
            return;
        }

        $date = 0;

        if($handbook_version>0){
            $handbook_version_name = Jelly::select('client_handbookversionname', (int)$handbook_version);
            if(!$handbook_version_name instanceof Jelly_Model || !$handbook_version_name->loaded()) return $result;
            $date = $handbook_version_name->datetime;
        }

        if(!$period){
            $period = Session::instance()->get('periods');
            $period = (int)$period[0];
        }

        if(!$farm){
            $farm = Jelly::factory('farm')->get_session_farms();
            $farm = (int)$farm[0];
        }


        if(!$proper_units){
			switch((int)$units){
				case 1:  $old_units = 1; break;
				case 2:  $old_units = 2; break;
				case 3:  $old_units = 3; break;
				case 27: $old_units = 4; break;
				case 4:  $old_units = 5; break;
				case 28: $old_units = 6; break;
				case 21: $old_units = 5; break;

				case 44: $old_units = 1; break;
				case 45: $old_units = 2; break;
				case 46: $old_units = 3; break;
				case 47: $old_units = 6; break;

				case 63: $old_units = 1; break;
				case 66: $old_units = 2; break;
				case 59: $old_units = 3; break;
				case 64: $old_units = 4; break;
				case 58: $old_units = 5; break;

				default: $old_units = 1; break;
			}
			$units = $this->get_units($model, $units);
		}else{
			$old_units = $units;
			$units = ($units==2 || $units==3 || $units==4) ? 1 : $units;
		}

	
        $record = Jelly::select('client_handbookversion')->where('deleted', '=', false)
														 ->where('license', '=', $user->license->id())
														 ->where('farm', '=', $farm)
														 ->where('period', '=', $period)
														 ->where('nomenclature_model', '=', $model)
														 ->where('planned_price_units', '=', $old_units)
														 ->where('version_date', '=', $date);

		if(!$all_items) $record = $record->where('nomenclature_id', '=', $id)->limit(1);
		$record = $record->execute();

		if(($all_items && count($record)==0 ) || ( !$all_items && !$record->id() )){
			$record = Jelly::select('client_handbookversion')->where('deleted', '=', false)
															 ->where('license', '=', $user->license->id())
															 ->where('farm', '=', $farm)
															 ->where('period', '=', $period)
															 ->where('nomenclature_model', '=', $model)
															 ->where_open()->where('amount_units', '=', $units)->or_where('planned_price_units', '=', $units)->where_close()
															 ->where('version_date', '=', $date);

			if(!$all_items) $record = $record->and_where('nomenclature_id', '=', $id)->limit(1);
			$record = $record->execute();
			if(!$all_items){
				$units = $old_units;
				$old_units = $record->planned_price_units;
			}
		}else{
			$units = $old_units;
		}



		if($all_items){
			$result = $record->as_array();
			foreach($result as &$res){
				$price = $planned && $date ? $res['planned_price'] : $res['discount_price'];

				if($old_units != $units){
					if($old_units==1) {
						if($units==2) $price *= 0.1;
						if($units==3) $price *= 0.001;
						if($units==4) $price *= 0.000001;
					}

					if($old_units==2) {
						if($units==1) $price *= 10;
						if($units==3) $price *= 0.01;
						if($units==4) $price *= 0.00001;
					}
					if($old_units==3) {
						if($units==1) $price *= 1000;
						if($units==2) $price *= 100;
						if($units==4) $price *= 0.001;
					}
					if($old_units==4) {
						if($units==1) $price *= 1000000;
						if($units==2) $price *= 100000;
						if($units==3) $price *= 1000;
					}
				}

				$res = array(
					'nomenclature_id' => $res['nomenclature_id'],
					'nomenclature_model' => $res['nomenclature_model'],
					'price' => $price
				);
			}
		}else{
			if(!$record instanceof Jelly_Model || !$record->loaded()){
				$this->request->response = JSON::success(array('price' => 0, 'success' => true));
				return;
			}
			$result = $planned && $date ? $record->planned_price : $record->discount_price;

			if($old_units != $units){

				if($old_units==1) {
					if($units==2) $result *= 0.1;
					if($units==3) $result *= 0.001;
					if($units==4) $result *= 0.000001;
				}

				if($old_units==2) {
					if($units==1) $result *= 10;
					if($units==3) $result *= 0.01;
					if($units==4) $result *= 0.00001;
				}
				if($old_units==3) {
					if($units==1) $result *= 1000;
					if($units==2) $result *= 100;
					if($units==4) $result *= 0.001;
				}
				if($old_units==4) {
					if($units==1) $result *= 1000000;
					if($units==2) $result *= 100000;
					if($units==3) $result *= 1000;
				}
			}
		}


        $this->request->response = JSON::success(array('price' => $result, 'success' => true));
    }


    private function get_units($model, $units){
        switch($model){
            case 'gsm':
                $res = Model_Client_TransactionNomenclature::$amount_units[5];
                break;
            default:
                switch($units){
                    case 1: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 2: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 3: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 27: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 4: $res = Model_Client_TransactionNomenclature::$amount_units[5]; break;
                    case 28: $res = Model_Client_TransactionNomenclature::$amount_units[6]; break;

					case 44: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 45: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 46: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 47: $res = Model_Client_TransactionNomenclature::$amount_units[6]; break;

					case 63: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 66: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 59: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                    case 64: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
					case 58: $res = Model_Client_TransactionNomenclature::$amount_units[5]; break;

                    default: $res = Model_Client_TransactionNomenclature::$amount_units[1]; break;
                }
                break;
        }

        return $res['_id'];
    }

}

