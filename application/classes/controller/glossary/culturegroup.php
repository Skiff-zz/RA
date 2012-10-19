<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_CultureGroup extends Controller_Glossary_AbstractGroup
{
	protected $model_name = 'glossary_culture';
	protected $model_group_name = 'glossary_culturegroup';

    
    public function action_update(){
        $_POST['crop_rotation_interest'] = (float)$_POST['crop_rotation_interest'];
		parent::action_update();
	}
    
}
