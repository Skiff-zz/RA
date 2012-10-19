<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Client_PeriodGroup extends Model_Glossary_AbstractGroup
{
	public static function initialize(Jelly_Meta $meta, $table_name = 'client_period_group', $items_model = NULL)
	{


		$meta->table($table_name)
			->fields(array(
				'license' => Jelly::field('BelongsTo',array(
														'foreign'	=> 'license',
														'column'	=> 'license_id',
														'label'		=> 'Лицензия',

                                                        'rules' => array(
                                        						'not_empty' => NULL
                                        				)

													)),
				'start'  => Jelly::field('Integer', array('label' => 'Дата начала')),
				'finish' => Jelly::field('Integer', array('label' => 'Дата окончания')),
				'status' => Jelly::field('Integer', array('label' => 'Статус'))// 1 - архивный 0 - текущий 2 - плановый
		));

		$p = parent::initialize($meta, $table_name,  $items_model);

		return $p;
	}

	protected $result = array();
	protected $counter = 0;
	public function get_tree($license_id, $check = false, $exclude = array(), $items_field = 'items'){
		$this->result = array();
		$this->counter = 0;

		$items_model = $this->meta()->fields($items_field);
		$items_model_name = $items_model->foreign['model'];

		$groups = Jelly::select($this->meta()->model())->with('parent')->where('license', '=', $license_id)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->order_by('name', 'asc')->execute()->as_array();
//		$names = Jelly::select($items_model_name)->with($items_model->foreign['column'])->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->order_by('name', 'asc')->execute()->as_array();
		$names = array();
		$this->get_groups($groups, $names, 0, 0, array(), $check, $items_model->foreign['column']);

		return $this->result;
	}

	protected function get_groups($groups, $names, $parent, $level, $exclude, $check, $relation){
		$items = array();
		foreach($groups as $group){
			if($group[':parent:_id']==$parent) $items[] = $group;
		}

		$session = Session::instance();
		$def_periods = $session->get('periods');
		if(!is_array($def_periods)) $def_periods = array();

		foreach($items as $item){
			$this->result[$this->counter] = array(
				'id'	   => 'g'.$item['_id'],
				'title'    =>    ($item['start']>time() ?'Плановый':($item['finish']<time()?'Архивный':'Текущий') ).' '.date('Y',$item['start']).'-'.date('Y',$item['finish']),
				'is_group' => true,
				'is_group_realy' => true,
				'level'	   => $level,
				'children_g' => array(),
				'children_n' => array(),
				'parent'   => $item[':parent:_id'] ? 'g'.$item[':parent:_id'] : '',
				'color'    => $item['color'],
				'parent_color' => $item[':parent:color'],
				'checked' => $check ? array_search($item['_id'], $def_periods)!==false : false
			);
			$this->counter++;
			$this->get_groups($groups, $names, $item['_id'], $level+1, $exclude, $check, $relation);
		}
	}


	public function copy_fields($from_period, $to_period, $license_id){
		$delete_fields = Jelly::select('field')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		foreach($delete_fields as $delete_field){
			$delete_field->deleted = true;
			$delete_field->save();
		}

		$copy_fields = Jelly::select('field')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->and_where('period', '=', $from_period)->execute()->as_array();
		foreach($copy_fields as $copy_field) {
			$new_one = Jelly::factory('field');
			$new_one->license = $license_id;
			$new_one->farm = $copy_field['farm'];
			$new_one->name = $copy_field['name'];
			$new_one->crop_rotation_number = $copy_field['crop_rotation_number'];
			$new_one->number = $copy_field['number'];
			$new_one->sector_number = $copy_field['sector_number'];
			$new_one->kadastr_area = $copy_field['kadastr_area'];
			$new_one->area = $copy_field['area'];
			$new_one->culture = 0;
			$new_one->culture_before = $copy_field['culture'];
			$new_one->period = $to_period;
			$new_one->coordinates = $copy_field['coordinates'];
			$new_one->save();
		}
	}
	
	
	public function copy_handbook($from_period, $to_period, $license_id){
		//delete handbook
		Jelly::delete('Client_Handbook')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		Jelly::delete('Client_HandbookVersion')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		Jelly::delete('Client_HandbookVersionName')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		
		$this->delete_all_model_photos($license_id, $to_period, 'Client_Handbook_Personal');
		Jelly::delete('Client_Handbook_PersonalGroup')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		Jelly::delete('Client_Handbook_Personal')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		
		$this->delete_all_model_photos($license_id, $to_period, 'Client_Handbook_TechniqueMobile');
		Jelly::delete('Client_Handbook_TechniqueMobileGroup')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		Jelly::delete('Client_Handbook_TechniqueMobile')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		
		$this->delete_all_model_photos($license_id, $to_period, 'Client_Handbook_TechniqueTrailer');
		Jelly::delete('Client_Handbook_TechniqueTrailerGroup')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		Jelly::delete('Client_Handbook_TechniqueTrailer')->where('license', '=', $license_id)->where('period', '=', $to_period)->execute();
		
		//copy
		$copy_handbooks = Jelly::select('Client_Handbook')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->and_where('period', '=', $from_period)->execute()->as_array();
		foreach($copy_handbooks as $copy_handbook) {
			unset($copy_handbook['id']);unset($copy_handbook['_id']);
			$new_one = Jelly::factory('Client_Handbook');
			$new_one->set($copy_handbook);
			$new_one->period = $to_period;
			$new_one->save();
		}
		
		$copy_handbook_versions = Jelly::select('Client_HandbookVersion')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->and_where('period', '=', $from_period)->execute()->as_array();
		foreach($copy_handbook_versions as $copy_handbook_version) {
			unset($copy_handbook_version['id']);unset($copy_handbook_version['_id']);
			if($copy_handbook_version['version_date']==0){
				$copy_handbook_version['amount'] = 0;
				$copy_handbook_version['discount_price'] = 0;
				$copy_handbook_version['planned_price'] = 0;
				$copy_handbook_version['planned_price_manual'] = 0;
			}
			$new_one = Jelly::factory('Client_HandbookVersion');
			$new_one->set($copy_handbook_version);
			$new_one->period = $to_period;
			$new_one->save();
		}
		
		$copy_handbook_version_names = Jelly::select('Client_HandbookVersionName')->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->where('license', '=', $license_id)->and_where('period', '=', $from_period)->execute()->as_array();
		foreach($copy_handbook_version_names as $copy_handbook_version_name) {
			unset($copy_handbook_version_name['id']);unset($copy_handbook_version_name['_id']);
			$new_one = Jelly::factory('Client_HandbookVersionName');
			$new_one->set($copy_handbook_version_name);
			$new_one->period = $to_period;
			$new_one->save();
		}
		
		try{
		
		$this->copy_tree_recursive($license_id, $from_period, $to_period, 'Client_Handbook_PersonalGroup', 'Client_Handbook_Personal', 'group', 0, 0);
		$this->copy_tree_recursive($license_id, $from_period, $to_period, 'Client_Handbook_TechniqueMobileGroup', 'Client_Handbook_TechniqueMobile', 'group', 0, 0);
		$this->copy_tree_recursive($license_id, $from_period, $to_period, 'Client_Handbook_TechniqueTrailerGroup', 'Client_Handbook_TechniqueTrailer', 'group', 0, 0);
		
		}catch(Exception $e){
			print_r($e->getFile().": ".$e->getLine().": ".$e->getMessage());
		}
		
		
	}
	
	
	
	public function copy_tree_recursive($license_id, $from_period, $to_period, $group_model, $item_model, $item_parent_key, $old_parent_group_id, $new_parent_group_id){
		
		
		$copy_groups = Jelly::select($group_model)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
												 ->where('license', '=', $license_id)
												 ->where('period', '=', $from_period);
		
		if($old_parent_group_id==0){
			$copy_groups = $copy_groups->where_open()->where('parent', '=', 0)->or_where('parent', 'IS', null)->where_close();
		}else{
			$copy_groups = $copy_groups->where('parent', '=', $old_parent_group_id);
		}
		
		$copy_groups = $copy_groups->execute()->as_array();
				
		
		foreach($copy_groups as $copy_group) {
			$copy_group_id = $copy_group['_id'];
			unset($copy_group['id']);unset($copy_group['_id']);
			$copy_group['parent'] = $new_parent_group_id;
			$copy_group['period'] = $to_period;
			$new_group = Jelly::factory($group_model);
			$new_group->set($copy_group);
			$new_group->save();
			
			$copy_items = Jelly::select($item_model)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
													->where($item_parent_key, '=', $copy_group_id)
													->where('license', '=', $license_id)
													->where('period', '=', $from_period)->execute()->as_array();
			
			foreach($copy_items as $copy_item) {
				$copy_item_id = $copy_item['_id'];
				unset($copy_item['id']);unset($copy_item['_id']);
				$copy_item['period'] = $to_period;
				$copy_item[$item_parent_key] = $new_group->id();
				if($item_model=='Client_Handbook_Personal') $copy_item['position'] = $new_group->id();
				$new_item = Jelly::factory($item_model);
				$new_item->set($copy_item);
				$new_item->save();
				$this->copy_photo($item_model, $copy_item_id, $new_item->id());
			}
			
			$this->copy_tree_recursive($license_id, $from_period, $to_period, $group_model, $item_model, $item_parent_key, $copy_group_id, $new_group->id());
		}
		
		
		if($old_parent_group_id==0){
			$copy_items = Jelly::select($item_model)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()
													->where_open()->where($item_parent_key, '=', 0)->or_where($item_parent_key, 'IS', null)->where_close()
													->where('license', '=', $license_id)
													->where('period', '=', $from_period)->execute()->as_array();
			
			foreach($copy_items as $copy_item) {
				$copy_item_id = $copy_item['_id'];
				unset($copy_item['id']);unset($copy_item['_id']);
				$copy_item['period'] = $to_period;
				$copy_item[$item_parent_key] = null;
				if($item_model=='Client_Handbook_Personal') $copy_item['position'] = 0;
				$new_item = Jelly::factory($item_model);
				$new_item->set($copy_item);
				$new_item->save();
				$this->copy_photo($item_model, $copy_item_id, $new_item->id());
			}
		}
	}
	
	
	public function copy_photo($model, $old_id, $new_id){
		$old_subdir = floor($old_id / 2000);
		$new_subdir = floor($new_id / 2000);
		
		$old_dir = ''; $new_dir = ''; $prefix = '';
		
		switch($model){
			case 'Client_Handbook_Personal': 
				$old_dir = DOCROOT . Kohana::config('upload.path') . '/users/' . $old_subdir;
				$new_dir = DOCROOT . Kohana::config('upload.path') . '/users/' . $new_subdir;
				$prefix = 'user_';
				break;
			case 'Client_Handbook_TechniqueMobile': 
				$old_dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquemobile/' . $old_subdir;
				$new_dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquemobile/' . $new_subdir;
				$prefix = 'item_';
				break;
			case 'Client_Handbook_TechniqueTrailer': 
				$old_dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquetrailer/' . $old_subdir;
				$new_dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquetrailer/' . $new_subdir;
				$prefix = 'item_';
				break;
			default: return; break;
		}
		
		
		if(!is_dir($old_dir))return;
		$files = scandir($old_dir);
		foreach ($files as $file) {
			if (is_file($old_dir.'/'.$file) && strpos($file, $prefix . $old_id) !== FALSE) {
				
				//copy file
				if (!is_dir($new_dir)) @mkdir($new_dir, 0777);
				
				$newfile = explode('_', $file);
				$newfile[1] = str_replace($old_id, $new_id, $newfile[1]);
				$newfile = implode('_', $newfile);
				
				copy($old_dir.'/'.$file, $new_dir.'/'.$newfile);
                chmod($new_dir.'/'.$newfile, 0777);
				///////////
			}
		}
		
	}
	
	public function delete_all_model_photos($license_id, $period, $model){
		$items = Jelly::select($model)->where('license', '=', $license_id)->where('period', '=', $period)->execute()->as_array();
		foreach($items as $item){
			$this->delete_photo($model, $item['_id']);
		}
	}
	
	public function delete_photo($model, $id){
		$subdir = floor($id / 2000);
		$dir = ''; $prefix = '';
		
		switch($model){
			case 'Client_Handbook_Personal': 
				$dir = DOCROOT . Kohana::config('upload.path') . '/users/' . $subdir;
				$prefix = 'user_';
				break;
			case 'Client_Handbook_TechniqueMobile': 
				$dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquemobile/' . $subdir;
				$prefix = 'item_';
				break;
			case 'Client_Handbook_TechniqueTrailer': 
				$dir = DOCROOT . Kohana::config('upload.path') . '/client_handbook_techniquetrailer/' . $subdir;
				$prefix = 'item_';
				break;
			default: return; break;
		}
		
		
		if(!is_dir($dir))return;
		$files = scandir($dir);
		foreach ($files as $file) {
			if (is_file($dir.'/'.$file) && strpos($file, $prefix . $id) !== FALSE) unlink($dir.'/'.$file);
		}
		
	}
	
	

	public function save($key = NULL)
	{
	    if(array_key_exists('parent', $this->_changed))
        {
			$license_id = null;
            $license_id = array_key_exists('license', $this->_changed) ? $this->_changed['license'] : $this->_original['license'];

            if((int)$this->_changed['parent'])
            {
                $parent = Jelly::select($this->meta()->model())->where('_id', '=', (int)$this->_changed['parent'])->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->load();

                if(!($parent instanceof Jelly_Model) or !$parent->loaded())
                {
                    unset($this->_changed['parent']);
                }
                else
                {
                    $this->_changed['path'] = $parent->path.$parent->id().'/';
                }
            }
            else
            {
                $this->_changed['path']         = '/';
                $this->_changed['parent']       = 0;
            }
        }


        return parent::save($key);
    }
}

