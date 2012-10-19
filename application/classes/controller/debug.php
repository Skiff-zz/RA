<?php
class Controller_Debug extends Controller
{
	public function action_merger($path = null)
	{
		if(!$path)
		{
			$path = APPPATH.'/classes/';
		}
		
		// файлы
		foreach(glob($path . '/*.php') as $file)
		{
			if(is_file($file))
			{
				$fp = fopen(APPPATH.'/logs/merged.txt', 'a+b');
				
				fwrite($fp, "\n".$file."\n");
				
				fwrite($fp, file_get_contents($file));
				
				fclose($fp);
			}
		}
		
		// подкаталоги
		foreach(glob($path . '/*', GLOB_ONLYDIR ) as $dir)
		{
			$this->action_merger($dir);
		}
		
		//
	}
	
	public function action_merger_js($path = null)
	{
		if(!$path)
		{
			$path = 'G:/AC4/media/js/';
		}
		
		// файлы
		if($path !=  'G:/AC4/media/js/')
		{
			foreach(glob($path . '/*.js') as $file)
			{
				if(is_file($file))
				{
					$fp = fopen(APPPATH.'/logs/merged.txt', 'a+b');
					
					fwrite($fp, "\n".$file."\n");
					
					fwrite($fp, file_get_contents($file));
					
					fclose($fp);
				}
			}
		}
		
		// подкаталоги
		foreach(glob($path . '/*', GLOB_ONLYDIR ) as $dir)
		{
			if($dir != 'G:/AC4/media/js//touch')
			{
				$this->action_merger_js($dir);
			}
		}
		
		//
	}
	
	public function action_path()
	{
		$path = '/22/24/23/234/';
		
		echo count(explode('/', $path));
		print_r(explode('/', $path));
		
		
	}
	
	
	public function action_query()
	{
		// Не забыть сверху наложить условия на лицензиата, ферму и перод перед продакшеном!!!
		
		$raw = Jelly::select('client_handbookversion')->where('update_date', '=', 1317896207)->order_by('farm')->with('farm')->order_by('farm.path','ASC')->execute();
		
		$farms = array();
		foreach($raw as $r)
		{
			$r->farm->path =  $r->farm->path.$r->farm->id().'/';
			$farms[$r->farm->id()] = $r->farm;
		}
		
		$tmp = array();
		foreach($farms as $f)
		{
			$tmp[] = $f;
		}
		
		$farms = $tmp;
		
		$tmp = array();
		
		for($i = 0; $i < $k = count($farms); $i++)
		{
			$found = false;
			
			for($j = 0; $j < $k = count($farms); $j++)
			{
		 		if($farms[$j]->id() == $farms[$i]->id())
		 			continue;
				
				if(strpos($farms[$j]->path, $farms[$i]->path) !== false)
				{
					$found = true;
					break;
				}
			}
			
			if(!$found)
			{
				$tmp[] = $farms[$i];
			}
		}
		
		$farms = $tmp;
		
		$farms_path = array();

		foreach($farms as $f)
		{
			$farms_path[] = $f->get_parent_path($f->id());
		}
	
		
		foreach($farms_path as &$fg)
		{
			foreach($fg as &$f)
			{
				$f = array('groups' => array(), 'items' => array(), 'money' => 0, 'farm' => $f);
			}
		}
		
		@reset($raw);
		
		function get_farm($model, &$farms_path)
		{
			$result = array(-1, -1);
			
			for($i = 0; $i < $k = count($farms_path); $i++)
			{
				for($j = 0; $j < $m = count($farms_path[$i]); $j++)
				{
					if($farms_path[$i][$j]['farm']->id() == $model->farm->id())
					{
						return array($i, $j);
					}
				}
			}
			
			return $result;
		}
		
		foreach($raw as $q)
		{
			$item = Jelly::select('glossary_'.$q->nomenclature_model)->with('group')->load($q->nomenclature_id);
			
			if(!$item instanceof Jelly_Model or !$item->loaded())
				continue;
			
			list($chain, $farm_id) =  get_farm($q, $farms_path);
			
			if($chain == -1)
				continue;
			
			if(!array_key_exists($item->group->id(), $farms_path[$chain][$farm_id]['groups']))
			{
				$item->group->path =  $item->group->path.$item->group->id().'/';
				$farms_path[$chain][$farm_id]['groups'][$item->group->id()] = $item->group;
			}
			
			$farms_path[$chain][$farm_id]['items'][] = 
										
										array(
													 'item' => $item,
													 'amount' => $q->amount,
													 'amount_units' => Model_Client_TransactionNomenclature::$amount_units[$q->amount_units],
													 'discount_price' => $q->discount_price,
													 'discount_price_units' =>  Model_Client_TransactionNomenclature::$amount_units[$q->discount_price_units]
											 );
		}
		
		for($i = 0; $i < $k = count($farms_path); $i++)
		{
			for($j = 0; $j < $m  = count($farms_path[$i]); $j++)
			{
				$tmp = array();
				
				foreach($farms_path[$i][$j]['groups'] as $key => $value)
				{
						$tmp[] = $value;
				}
				
				$farms_path[$i][$j]['groups'] = $tmp;
				
				$tmp = array();
				
				for($a = 0; $a < $kk = count($farms_path[$i][$j]['groups']); $a++)
				{
					$found = false;
					
					for($b = 0; $b < $kk ; $b++)
					{
				 		if($farms_path[$i][$j]['groups'][$b]->id() == $farms_path[$i][$j]['groups'][$a]->id())
				 			continue;
						
						if(strpos($farms_path[$i][$j]['groups'][$b]->path, $farms_path[$i][$j]['groups'][$a]->path) !== false)
						{
							$found = true;
							break;
						}
					}
					
					if(!$found)
					{
						$tmp[] = $farms_path[$i][$j]['groups'][$a];
					}
				}
				
				$farms_path[$i][$j]['groups'] = $tmp;
				
				$groups_path = array();
		
				foreach($farms_path[$i][$j]['groups'] as $key => $value)
				{
					$groups_path[] = $value->get_parent_path($value->id());
				}
				
				$farms_path[$i][$j]['groups'] = $groups_path; 			
			}
		}
		
		/*
		foreach($farms_path as &$fg)
		{
			foreach($fg as &$farm)
			{
				$tmp = array();
				
				foreach($farm['groups'] as $f)
				{
						$tmp[] = $f;
				}
				
				$farm['groups'] = $tmp;
				
				$tmp = array();
				
				for($i = 0; $i < $k = count($farm['groups']); $i++)
				{
					$found = false;
					
					for($j = 0; $j < $k = count($farm['groups']); $j++)
					{
				 		if($farm['groups'][$j]->id() == $farm['groups'][$i]->id())
				 			continue;
						
						if(strpos($farm['groups'][$j]->path, $farm['groups'][$i]->path) !== false)
						{
							$found = true;
							break;
						}
					}
					
					if(!$found)
					{
						$tmp[] = $farm['groups'][$i];
					}
				}
				
				$farm['groups'] = $tmp;
				
				$groups_path = array();
		
				foreach($farm['groups'] as $f)
				{
					$groups_path[] = $f->get_parent_path($f->id());
				}
				
				$farm['groups'] = $groups_path; 
			}	
		}
		*/
		var_dump($farms_path);
		exit;
		
		/*
		
		
		$query = Jelly::select('client_handbookversion')->where('update_date', '=', 1317890111)->order_by('farm')->order_by('nomenclature_model')->order_by('nomenclature_id')->order_by('amount_units')->execute();
		
		$items = array();
		
		$farms = array();
		
		foreach($query as $q)
		{
			$item = Jelly::select('glossary_'.$q->nomenclature_model)->with('group')->load($q->nomenclature_id);
			
			if(!$item instanceof Jelly_Model or !$item->loaded())
				continue;
			
			$farm_id = $group_id = $item_id = 0;
			
			if(!array_key_exists($q->farm->id(), $farms))
			{
				$farm = Jelly::select('farm', $q->farm->id());
				$farms[$q->farm->id()] = array('farm' => $farm, 'groups' => array(), 'money' => $q->amount * $q->discount_price);
			}
			else
			{
				$farms[$q->farm->id()]['money'] += 	$q->amount * $q->discount_price;
			}
			
			if(!array_key_exists($item->group->id(), $farms[$q->farm->id()]['groups']))
			{
				$farms[$q->farm->id()]['groups'][$item->group->id()] = array('group' => $item->group, 'money' => $q->amount * $q->discount_price, 'nomenklatura' => array());
			}
			else
			{
				$farms[$q->farm->id()]['groups'][$item->group->id()]['money'] += 	$q->amount * $q->discount_price;
			}
			
			
			if(!array_key_exists($item->id(), $farms[$q->farm->id()]['groups'][$item->group->id()]['nomenklatura']))
			{
				$farms[$q->farm->id()]['groups'][$item->group->id()]['nomenklatura'][$item->id()] = array('money' => $q->amount * $q->discount_price, 'nomenklatura' => $item, 'items' => array());
			}
			else
			{
				$farms[$q->farm->id()]['groups'][$item->group->id()]['nomenklatura'][$item->id()]['money'] += 	$q->amount * $q->discount_price;
			}
			
			$farms[$q->farm->id()]['groups'][$item->group->id()]['nomenklatura'][$item->id()]['items'][] = 
										
										array(
													 'amount' => $q->amount,
													 'amount_units' => Model_Client_TransactionNomenclature::$amount_units[$q->amount_units],
													 'discount_price' => $q->discount_price,
													 'discount_price_units' =>  Model_Client_TransactionNomenclature::$amount_units[$q->discount_price_units]
											 );
		}
		
		//var_dump($farms);
		*/
	}
}
