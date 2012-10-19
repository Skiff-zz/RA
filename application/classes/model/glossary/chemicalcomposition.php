<?php defined('SYSPATH') or die ('No direct script access.');

class Model_Glossary_ChemicalComposition extends Model_Glossary_Abstract
{
	public static function initialize(Jelly_Meta $meta, $table_name  = 'glossary_chemicalcomposition', $group_model = 'glossary_chemicalcompositiongroup')
	{
		parent::initialize($meta, $table_name, $group_model);
        
        $meta->table($table_name)->fields(array(
            'symbol' => Jelly::field('String', array('label' => 'Символ (Лат. буква)')),
            'contents' => Jelly::field('HasMany',array(
                'foreign'	=> 'glossary_chemicalcompositioncontent',
                'label'	=> 'Составы'
            ))
        ));
		
	}
    
    public function get_tree($license_id, $group_field = 'group', $exclude = array())
	{
		$this->result = array();
		$this->counter = 0;
		$res = array();

		$model_name 		= $this->meta()->model();
		$t					= $this->meta()->fields($group_field);
		$group_model_name	= $t->foreign['model'];
		
		$groups = Jelly::select($group_model_name)->with('parent')->where('deleted', '=', false)->order_by('name', 'asc');
		$names 	= Jelly::select($model_name)->with($group_field)->where('deleted', '=', false)->order_by('name', 'asc');
		
		// Дополнительная фильтрация
		if($this->meta()->fields('license') and $this->meta()->fields('farm') and $this->meta()->fields('period'))
		{
			$farms = Jelly::factory('farm')->get_session_farms();
			if(!count($farms)) $farms = array(-1);
			$periods = Session::instance()->get('periods');
			if(!count($periods)) $periods = array(-1);
			
			$user = Auth::instance()->get_user();
			
			if($user)
			{
				$groups = $groups->where('license', '=', $user->license->id());
				$names  = $names->where('license', '=', $user->license->id());
			}
			
			$groups = $groups->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
			$names  = $names->where('farm', 'IN', $farms)->where('period', 'IN', $periods);
		}

		$groups = $groups->execute()->as_array();
		$names = $names->execute()->as_array();

		$this->get_groups($groups, 0);

		$this->result[] = 0;
		foreach($this->result as $group){
			$items = array();
			foreach($names as $name){
				if($name[':'.$group_field.':_id']==$group){ $items[] = $name; }
			}

			foreach($items as $item) 
			{
				if(in_array($item['_id'], $exclude)){ continue; }
                
                // --- logo aka first_photo
                $id = (int) $item['_id'];
                $subdir = floor($id / 2000);
                $first_photo = null;
                
                if (is_dir(DOCROOT . Kohana::config('upload.path') . '/'.$model_name.'/' . $subdir)) {
                    $files = scandir(DOCROOT . Kohana::config('upload.path') . '/'.$model_name.'/' . $subdir);
                    foreach ($files as $i => $file) {
                        if ($i < 2) {
                            continue;
                        }

                        if (
                                    is_file(DOCROOT . Kohana::config('upload.path') . '/'.$model_name.'/' . $subdir . '/' . $file)
                                &&
                                    (
                                            !(strpos($file, 'item_' . $id . '_') === FALSE) 
                                        || 
                                            !(strpos($file, 'item_' . $id . '.') === FALSE)
                                    )
                        ) {
                            $first_photo = Kohana::config('upload.path') . '/'.$model_name.'/' . $subdir . '/' . $file;
                            break;
                        }
                    }
                }
                
                
				$res[] = array(
					'id'	   => 'n'.$item['_id'],
					'title'    => $item['name'].'</div> <div style="color: #666666; height: 28px; padding-top:3px; padding-right:4px;">'.$item['symbol'].'</div><div>',
					'clear_title'    => $item['name'],
					'is_group' => false,
					'is_group_realy' => false,
					'level'	   => 0,
					'children_g' => array(),
					'children_n' => array(),
					'parent'   => $item[':'.$group_field.':_id'] ? 'g'.$item[':'.$group_field.':_id'] : '',
					'color'    => $item['color'],
					'parent_color' => $item[':'.$group_field.':color'] ? $item[':'.$group_field.':color'] : ($this->counter ? 'BBBBBB' : 'FFFFFF'),
                    'first_photo'=>$first_photo
				);
			}
		}
		return $res;
	}

}



