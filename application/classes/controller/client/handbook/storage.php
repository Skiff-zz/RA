<?php defined('SYSPATH') or die ('No direct script access.');

class Controller_Client_Handbook_Storage extends AC_Controller{
	
    public $auto_render  = false;
    
	public function action_index(){}
    
    public function action_nomenclature_tree(){
		$farms = arr::get($_GET, 'farm', false);
		if($farms) $farms = array($farms);
		else	   $farms = Jelly::factory('farm')->get_session_farms();
		
        $license_id = Auth::instance()->get_user()->license->id();
        $data = Jelly::factory('client_handbook')->get_nomenclature_tree($license_id, $farms);
        $this->request->response = Json::arr($data, count($data));
    }
    
    
    public function action_contragents_tree(){
        $farms = arr::get($_GET, 'farm', false);
		if($farms) $farms = array($farms);
		else	   $farms = Jelly::factory('farm')->get_session_farms();
		
        $license_id = Auth::instance()->get_user()->license->id();
        $data = Jelly::factory('client_handbook')->get_contragents_tree($license_id, $farms);
        $this->request->response = Json::arr($data, count($data));
    }
    
}