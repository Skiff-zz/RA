<?php
class Controller_System extends Controller_Template_Twig
{

  /***/
	private static $models = array(
	
	'user', 'user_token', 'licenseproperty','licensepropertyvalue','farm', 'license', 'extraproperty', 'station', 'blacklist', 'stat', 'farm_field', 'farm_value', 'station', 'stat', 'attempts', 'field', 'fieldnote', 'fieldwork', 'fieldworkpersonal', 'client_fieldshare', 'period',
								'client_format', //'client_culturetype', //FIXME: 'client_culturefield',
								'glossary_culturegroup', 'glossary_culture', 'glossary_seed', 'glossary_culturetype', 'glossary_predecessor',
								'glossary_gsm', 'glossary_gsmgroup', 'glossary_deploymenttype', 'glossary_fertilizer_deploymenttype', 'glossary_szr_deploymenttype', 'client_model_properties',
								'client_chemicalelement', 'client_chemicalelementgroup',
								'client_contragent', 'client_contragentgroup',
								'client_country', 'client_countrygroup',
								'client_periodgroup',
								'client_producer', 'client_producergroup',
								'client_transaction', 'client_transactionnomenclature',
								'client_handbook',
								'client_handbook_personal','client_handbook_personalgroup',
								'client_handbook_techniquemobile','client_handbook_techniquemobilegroup',
								'client_handbook_techniquetrailer','client_handbook_techniquetrailergroup',
                                'client_operationstagegroup', 'client_operationstage', 'client_operationgroup', 'client_operation', 'client_operations2materials', 'client_operations2personal',
								'client_operations2technics', 'client_operationtechnicmobileblock', 'client_operationtechnictrailerblock', 'client_operationtechnicaggregateblock',
								'glossary_fertilizergroup', 'glossary_fertilizer', 'glossary_szr', 'glossary_szrgroup', 'client_model_values',
								'glossary_szr_dv', 'glossary_fertilizer_dv',
                                'glossary_szr_target', 'glossary_fertilizer_target',
								'glossary_szr_szrdv', 'glossary_szr_szrculture', 'glossary_fertilizer_fertilizerdv', 'glossary_fertilizerculture', 'szr2cult_target', 'frt2cult_target',
								'glossary_preparativeform',
								'glossary_production', 'glossary_productionclass', 'glossary_production_production2cultures', 'glossary_production_prodclass2seed',
								'glossary_techmobilegroup', 'glossary_techmobile',
								'glossary_techtrailergroup', 'glossary_techtrailer',
								'glossary_units','glossary_unitsgroup',
								'glossary_personal', 'glossary_personalgroup',
								'subuser', 'rule', 'preset', 'role', 'farmpreset', 'personalpreset', 'glossary_acidity', 'glossary_groundtype',
								'UserPreset',
                                'client_handbookversion','client_handbookversionname',

								'client_planning_atk', 'client_planning_atk2seed', 'client_planning_atkseed2production', 'client_planning_atkstatus', 'client_planning_atktype', 'client_planning_atk2operation', 'client_planning_atkoperation2material',
                                'client_planning_atkoperation2personal', 'client_planning_atkoperation2technic', 'client_planning_atkoperationtechnicmobileblock', 'client_planning_atkoperationtechnictrailerblock', 'client_planning_atkoperationtechnicaggregateblock',
                                
								'client_planning_plan', 'client_planning_plan2culture', 'client_planning_planculture2field', 'client_planning_planstatus',
								'client_planning_atkclone_atk', 'client_planning_atkclone_atk2seed', 'client_planning_atkclone_atkseed2production', 'client_planning_atkclone_atk2operation', 'client_planning_atkclone_atkoperation2material',
                                'client_planning_atkclone_atkoperation2personal', 'client_planning_atkclone_atkoperation2technic', 'client_planning_atkclone_atkoperationtechnicmobileblock', 'client_planning_atkclone_atkoperationtechnictrailerblock', 'client_planning_atkclone_atkoperationtechnicaggregateblock',
		
								'client_work_plannedorder', 'client_work_plannedorder2field', 'client_work_plannedorderfield2material', 'client_work_plannedorderfield2personal', 'client_work_plannedorderfield2technic',
								'client_work_plannedorderfieldtechnicmobileblock', 'client_work_plannedorderfieldtechnictrailerblock', 'client_work_plannedorderfieldtechnicaggregateblock', 'client_work_orderstatus', 
								
								'client_work_handorder', 'client_work_handorder2field', 'client_work_handorderfield2material', 'client_work_handorderfield2personal', 'client_work_handorderfield2technic',
								'client_work_handorderfieldtechnicmobileblock', 'client_work_handorderfieldtechnictrailerblock', 'client_work_handorderfieldtechnicaggregateblock',
		
	
								'client_share', 'client_shareholdergroup', 'client_shareholder', 'client_sharepayment', 'client_sharestatus', 'client_sharealertperiod',
		
								'client_work_undevelopmentorder', 'client_work_undevelopmentordergroup',
								'client_work_order',
        
                                'glossary_chemicalcomposition','glossary_chemicalcompositiongroup','glossary_chemicalcompositioncontent'

				/*'station', 'stat', 'blacklist', 'attempts', 'subuser', 'version', 'object', 'type',
				'useremail', 'licenseeemaillabel', 'emaillabel',
				'userdate', 'licenseedatelabel', 'datelabel',
				'useraddress', 'licenseeaddresslabel', 'addresslabel',
				'userphone', 'licenseephonelabel', 'phonelabel',
				'usernote', 'licenseenotelabel', 'notelabel',
				'area', 'plan', 'task', 'product', 'culture', 'atk', 'culturetype'*/

                       );


	private static $root_user = array('username'	     => 'root@agroclever.com',
                					  'name'	     => 'Root user',
                					  'is_active'	     => true,
                					  'is_root'	     => true,
                					  'email'	     => 'root@agroclever.com',
                					  'password'	     => 'agroclever',
                					  'password_confirm' => 'agroclever',
                					  'password_text'    => 'agroclever',

    );

	private static $object_types = array(
	    array('slug' => 'ACUser',	     'model' => 'subuser',     'name' => 'Пользователи у Хозяйств'),
	    array('slug' => 'ACArea',	     'model' => 'area',	       'name' => 'Поля'),
	    array('slug' => 'ACPlan',	     'model' => 'plan',	       'name' => 'Планы'),
	    array('slug' => 'ACTask',	     'model' => 'task',	       'name' => 'Планы по полям'),
	    array('slug' => 'ACProduct',     'model' => 'product',     'name' => 'Продукты'),
	    array('slug' => 'ACCulture',     'model' => 'culture',     'name' => 'Культуры'),
	    array('slug' => 'ACAtk',	     'model' => 'atk',	       'name' => 'Агротехнологическая карта'),
	    array('slug' => 'ACCultureType', 'model' => 'culturetype', 'name' => 'Тип культуры'),

	    array('slug' => 'ACUserEmail',   'model' => 'useremail',   'name' => 'E-mailы пользователей'),
	    array('slug' => 'ACUserDate',    'model' => 'userdate',    'name' => 'Даты пользователей'),
	    array('slug' => 'ACUserAddress', 'model' => 'useraddress', 'name' => 'Адреса пользователей'),
	    array('slug' => 'ACUserPhone',   'model' => 'userphone',   'name' => 'Телефоны пользователей'),
	    array('slug' => 'ACUserNote',    'model' => 'usernote',    'name' => 'Заметки пользователей'),

	    array('slug' => 'ACEmailLabel',   'model' => 'emaillabel',   'name' => 'Словарь E-mailов'),
	    array('slug' => 'ACDateLabel',    'model' => 'datelabel',    'name' => 'Словарь Дат'),
	    array('slug' => 'ACAddressLabel', 'model' => 'addresslabel', 'name' => 'Словарь Адресов'),
	    array('slug' => 'ACPhoneLabel',   'model' => 'phonelabel',   'name' => 'Словарь Телефонов'),
	    array('slug' => 'ACNoteLabel',    'model' => 'notelabel',    'name' => 'Словарь Заметок'),

	    array('slug' => 'ACLicenseeEmailLabel',   'model' => 'licenseeemaillabel',   'name' => 'Словарь E-mailов хозяйства',),
	    array('slug' => 'ACLicenseeDateLabel',    'model' => 'licenseedatelabel',    'name' => 'Словарь Дат хозяйства'),
	    array('slug' => 'ACLicenseeAddressLabel', 'model' => 'licenseeaddresslabel', 'name' => 'Словарь Адресов хозяйства'),
	    array('slug' => 'ACLicenseePhoneLabel',   'model' => 'licenseephonelabel',   'name' => 'Словарь Телефонов хозяйства'),
	    array('slug' => 'ACLicenseeNoteLabel',    'model' => 'licenseenotelabel',    'name' => 'Словарь Заметок хозяйства')
	);

	public function action_index(){

	}

	public function action_fix_farm_presets(){
		@set_time_limit(0);
		$res = Jelly::factory('farmpreset')->fix_farm_presets();
		print_r($res ? 'Success':'Fail'); exit;
	}

	public function action_fix_personal_presets(){
		@set_time_limit(0);
		$res = Jelly::factory('personalpreset')->fix_personal_presets();
		print_r($res ? 'Success':'Fail'); exit;
	}

	public function action_fix_user_presets(){
		@set_time_limit(0);
		$res = Jelly::factory('userpreset')->fix_user_presets();
		print_r($res ? 'Success':'Fail'); exit;
	}
	
	public function action_fix_presets_menu_order(){
		@set_time_limit(0);
		$res = Jelly::factory('userpreset')->fix_presets_menu_order();
		print_r($res ? 'Success':'Fail'); exit;
	}

	public function action_sync(){
        @set_time_limit(0);
        try{
           foreach(Controller_System::$models as $model){
	        Migration::factory($model, 'jelly')->sync();
            }
            Request::instance()->redirect(
                Request::instance()->uri(array('action'=>''))
            );
        }catch(Exception $e){
            print_r($e); exit;
        }
	}
    
    public function action_fixpath(){
        @set_time_limit(0);
        try{
            
           foreach(Controller_System::$models as $model){
               if(strpos($model, 'group')!==FALSE){
                   $all = Jelly::select($model)->with('parent')->execute();
                   foreach($all as $record){
                       if(!isset($record->deleted)){continue;}
                       $old_path = $record->path;
                       $path = '/'.$record->id().'/';
                       $parent = $record->parent;
                       while($parent->id()>0){
                           $path = '/'.$parent->id().$path;
                           $parent = $parent->parent;
                       }
                       if(strcmp($old_path,$path)!==0){
                           $record->set(array('path'=>$path))->save();
                       }
                       
                       
                       
                   }
               }
            }
            Request::instance()->redirect(
                Request::instance()->uri(array('action'=>''))
            );
        }catch(Exception $e){
            print_r($e); exit;
        }
	}

	public function action_newdb(){
		foreach(Controller_System::$models as $model){
	        Migration::factory($model, 'jelly')->remove();
	    }

		foreach(Controller_System::$models as $model){
	        Migration::factory($model, 'jelly')->sync();
	    }
		
		$this->clear_photo_folders();

	    //корневой пользователь
	    Controller_System::$root_user['update_date'] = time();
	    $root = Jelly::factory('user')->set(Controller_System::$root_user);
	    $root->save();

		Jelly::factory('glossary_culturetype')->set(array('_id' => 1, 'name' => 'Яр.' ))->save();
		Jelly::factory('glossary_culturetype')->set(array('_id' => 2, 'name' => 'Оз.' ))->save();

	    //типы объектов
	    /*foreach(Controller_System::$object_types as $object_type){
	        Jelly::factory('type')->set($object_type)->save();
	    }*/

	    Request::instance()->redirect(
	    	Request::instance()->uri(array('action'=>''))
		);
	}

	public function action_newtestdb(){
	    foreach(Controller_System::$models as $model){
	        Migration::factory($model, 'jelly')->remove();
	    }

	    foreach(Controller_System::$models as $model){
	        Migration::factory($model, 'jelly')->sync();
	    }
		
		$this->clear_photo_folders();

	    //корневой пользователь
	    Controller_System::$root_user['update_date'] = time();
	    $root = Jelly::factory('user')->set(Controller_System::$root_user);
	    $root->save();

	    //типы объектов
	    /*foreach(Controller_System::$object_types as $object_type){
	        Jelly::factory('type')->set($object_type)->save();
	    }*/

		Jelly::factory('glossary_culturetype')->set(array('_id' => 1, 'name' => 'Яр.' ))->save();
		Jelly::factory('glossary_culturetype')->set(array('_id' => 2, 'name' => 'Оз.' ))->save();

	    //тестовые хозяйства
	    Jelly::factory('user')->set(array('_id'		 => 54,
					      'username'	 => 'phpunit@agroclever.com',
					      'name'		 => '[PHPUNIT]_root',
					      'update_date'	 => time(),
						  'logins'	 => 1,
						  'last_login'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 0,
					      'status'		 => 1,
					      'manual'		 => 0,
					      'path'		 => '',
					      'email'		 => 'phpunit@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '667883300',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'first_name'	 => 'PHPUNIT',
					      'middle_name'	 => '',
					      'last_name'	 => '',
					      'position'         => 'Root Administrator',
					      'notes'		 => 'Root Notes',
					      'activate_date'    => time()-100,
					      'license'	 => 1))->save();
		Jelly::factory('license')->set(array('_id'		 => 1,
											  'update_date'	 => time(),
											  'status'		 => 1,
											  'is_active'	 => 1,
											  'manual'		 => 0,
											  'name'		 => 'PHPUNIT_LICENCE',
											  'number'		 => '667883300',
											  'user'		 => 54,
											  'max_ms'		 => 99999,
											  'max_users'	 => 99999,
											  'square'		 => 99999,
											  'max_fields'	 => 99999,
											  'last_login'	 => time(),
											  'activate_date'=> time()-100,
											  'expire_date'	 => time()+200000000))->save();
	    Jelly::factory('license')->set(array('_id'		 => 55,
					      'username'	 => 'achhfulK@agroclever.com',
					      'name'		 => '[PHPUNIT]_A',
					      'update_date'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 54,
					      'status'		 => 2,
					      'manual'		 => 0,
					      'path'		 => '/54/',
					      'email'		 => 'achhfulK@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '802296524',
					      'max_ms'		 => 1,// надо 0
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-100,
					      'expire_date'	 => time()+200000000))->save();
	    Jelly::factory('license')->set(array('_id'		 => 56,
					      'username'	 => 'acASXszy@agroclever.com',
					      'name'		 => '[PHPUNIT]_B',
					      'update_date'	 => time(),
					      'is_active'	 => false,
					      'is_root'		 => false,
					      'parent'           => 54,
					      'status'		 => 3,
					      'manual'		 => 0,
					      'path'		 => '/54/',
					      'email'		 => 'acASXszy@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '161029052',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()-20))->save();
	    Jelly::factory('license')->set(array('_id'		 => 57,
					      'username'	 => 'acMyuIxZ@agroclever.com',
					      'name'		 => '[PHPUNIT]_A_A',
					      'update_date'	 => time(),
					      'is_active'	 => false,
					      'is_root'		 => false,
					      'parent'           => 55,
					      'status'		 => 3,
					      'manual'		 => 1,
					      'path'		 => '/54//55/',
					      'email'		 => 'acMyuIxZ@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '154382324',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()+200000000))->save();
	    Jelly::factory('license')->set(array('_id'		 => 58,
					      'username'	 => 'acdZeIpG@agroclever.com',
					      'name'		 => '[PHPUNIT]_A_B',
					      'update_date'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 55,
					      'status'		 => 1,
					      'manual'		 => 1,
					      'path'		 => '/54//55/',
					      'email'		 => 'acdZeIpG@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '880111694',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()-200))->save();
	    Jelly::factory('license')->set(array('_id'		 => 59,
					      'username'	 => 'acHNoQWg@agroclever.com',
					      'name'		 => '[PHPUNIT]_C',
					      'update_date'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 54,
					      'status'		 => 3,
					      'manual'		 => 1,
					      'deleted'		 => 1,
					      'path'		 => '/54/',
					      'email'		 => 'acHNoQWg@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '184619328',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()+200000000))->save();
	    Jelly::factory('license')->set(array('_id'		 => 60,
					      'username'	 => 'shlobikwokbbgrns@agroclever.com',
					      'name'		 => '[PHPUNIT]_D',
					      'update_date'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 54,
					      'status'		 => 1,
					      'manual'		 => 0,
					      'path'		 => '/54/',
					      'email'		 => 'shlobikwokbbgrns@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '609864035',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()+200000000))->save();
	    Jelly::factory('license')->set(array('_id'		 => 92,
					      'username'	 => 'acFgTCBu@agroclever.com',
					      'name'		 => '[PHPUNIT]_E',
					      'update_date'	 => time(),
					      'is_active'	 => true,
					      'is_root'		 => false,
					      'parent'           => 54,
					      'status'		 => 1,
					      'manual'		 => 0,
					      'path'		 => '/54/',
					      'email'		 => 'acFgTCBu@agroclever.com',
					      'password'	 => 'PHPUNIT',
					      'password_confirm' => 'PHPUNIT',
					      'password_text'    => 'PHPUNIT',
					      'number'		 => '198700993',
					      'max_ms'		 => 10,
					      'max_users'		 => 10,
					      'square'		 => 10,
					      'activate_date'    => time()-1000,
					      'expire_date'	 => time()+200000000))->save();

	    //тестовые станции
	    Jelly::factory('station')->set(array('_id'	       => 115,
						 'name'	       => 'MC1_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_1'))->save();
	    Jelly::factory('station')->set(array('_id'	       => 116,
						 'name'	       => 'MC2_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_2',
						 'farms' =>array(54)))->save();
	    Jelly::factory('station')->set(array('_id'	       => 117,
						 'name'	       => 'MC3_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_3'))->save();
	    Jelly::factory('station')->set(array('_id'	       => 118,
						 'name'	       => 'MC4_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_4',
						 'farms' =>array(60)))->save();
	    Jelly::factory('station')->set(array('_id'	       => 119,
						 'name'	       => 'MC_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test',
						 'farms' =>array(54)))->save();
	    Jelly::factory('station')->set(array('_id'	       => 134,
						 'name'	       => 'MC5_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_5'))->save();
	    Jelly::factory('station')->set(array('_id'	       => 151,
						 'name'	       => 'MC6_[PHPUNIT]',
						 'hardware_id' => 'phpunit_test_6',
						 'farms' =>array(92)))->save();

	    //блэклист
	    Jelly::factory('blacklist')->set(array('hardware_id'  => 'phpunit_test_3',
						   'unblock_code' => '893202717',
						   'attempts'	  => 0,
						   'station'	  => 117,
						   'create_date'  => time()))->save();

	    //словари лэйблов для данных пользователей
	    /*Jelly::factory('emaillabel')->set(array('_id' => 1, 'label' => 'PHPUNIT_email_label_1'))->save();
	    Jelly::factory('emaillabel')->set(array('_id' => 2, 'label' => 'PHPUNIT_email_label_2'))->save();
	    Jelly::factory('emaillabel')->set(array('_id' => 3, 'label' => 'PHPUNIT_email_label_3', 'deleted' => 1))->save();
	    Jelly::factory('datelabel')->set(array('_id' => 1, 'label' => 'PHPUNIT_date_label_1'))->save();
	    Jelly::factory('datelabel')->set(array('_id' => 2, 'label' => 'PHPUNIT_date_label_2'))->save();
	    Jelly::factory('addresslabel')->set(array('_id' => 1, 'label' => 'PHPUNIT_address_label_1'))->save();
	    Jelly::factory('addresslabel')->set(array('_id' => 2, 'label' => 'PHPUNIT_address_label_2'))->save();
	    Jelly::factory('phonelabel')->set(array('_id' => 1, 'label' => 'PHPUNIT_phone_label_1'))->save();
	    Jelly::factory('phonelabel')->set(array('_id' => 2, 'label' => 'PHPUNIT_phone_label_2'))->save();
	    Jelly::factory('notelabel')->set(array('_id' => 1, 'label' => 'PHPUNIT_note_label_1'))->save();
	    Jelly::factory('notelabel')->set(array('_id' => 2, 'label' => 'PHPUNIT_note_label_2'))->save();

	    Jelly::factory('licenseeemaillabel')->set(array('_id' => 1, 'farm' => 54, 'email_label_id' => 1))->save();
	    Jelly::factory('licenseeemaillabel')->set(array('_id' => 2, 'farm' => 54, 'email_label_id' => 2))->save();
	    Jelly::factory('licenseedatelabel')->set(array('_id' => 1, 'farm' => 54, 'date_label_id' => 1))->save();
	    Jelly::factory('licenseedatelabel')->set(array('_id' => 2, 'farm' => 54, 'date_label_id' => 2))->save();
	    Jelly::factory('licenseeaddresslabel')->set(array('_id' => 1, 'farm' => 54, 'address_label_id' => 1))->save();
	    Jelly::factory('licenseeaddresslabel')->set(array('_id' => 2, 'farm' => 54, 'address_label_id' => 2))->save();
	    Jelly::factory('licenseephonelabel')->set(array('_id' => 1, 'farm' => 54, 'phone_label_id' => 1))->save();
	    Jelly::factory('licenseephonelabel')->set(array('_id' => 2, 'farm' => 54, 'phone_label_id' => 2))->save();
	    Jelly::factory('licenseenotelabel')->set(array('_id' => 1, 'farm' => 54, 'note_label_id' => 1))->save();
	    Jelly::factory('licenseenotelabel')->set(array('_id' => 2, 'farm' => 54, 'note_label_id' => 2))->save();*/

	    //тестовые пользователи
	    /*Jelly::factory('subuser')->set(array('_id'		=> 1,
						  'is_active'	=> true,
						  'is_root'	=> false,
						  'farm'        => 54,
						  'first_name'	=> 'Иван',
						  'middle_name'	=> 'Иванович',
						  'last_name'	=> 'Иванов',
						  'password'	=> 'PHPUNIT',
						  'position'	=> 'PHPUNIT',
						  'update_date'	=> 0,
						  'deleted'	=> 1,
						  'login'	=> 'PHPUNIT_1'))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_ivan_1@gmail.com', 'subuser' => 1, 'licensee_email_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_ivan_2@gmail.com', 'subuser' => 1, 'licensee_email_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-15', 'subuser' => 1, 'licensee_date_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-16', 'subuser' => 1, 'licensee_date_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_1', 'subuser' => 1, 'licensee_address_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_2', 'subuser' => 1, 'licensee_address_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_11-22-33-44', 'subuser' => 1, 'licensee_phone_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_55-66-77-88', 'subuser' => 1, 'licensee_phone_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_1', 'subuser' => 1, 'licensee_note_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_2', 'subuser' => 1, 'licensee_note_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();


	    Jelly::factory('subuser')->set(array('_id'		=> 2,
						  'is_active'	=> true,
						  'is_root'	=> false,
						  'farm'        => 54,
						  'first_name'	=> 'Петр',
						  'middle_name'	=> 'Петрович',
						  'last_name'	=> 'Петров',
						  'password'	=> 'PHPUNIT',
						  'position'	=> 'PHPUNIT',
						  'update_date'	=> 1000,
						  'deleted'	=> 0,
						  'notes'	=> 'Заметки Петровича',
						  'login'	=> 'PHPUNIT_2'))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_petr_1@gmail.com', 'subuser' => 2, 'licensee_email_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_petr_2@gmail.com', 'subuser' => 2, 'licensee_email_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-15', 'subuser' => 2, 'licensee_date_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-16', 'subuser' => 2, 'licensee_date_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_1', 'subuser' => 2, 'licensee_address_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_2', 'subuser' => 2, 'licensee_address_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_11-22-33-44', 'subuser' => 2, 'licensee_phone_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_55-66-77-88', 'subuser' => 2, 'licensee_phone_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_1', 'subuser' => 2, 'licensee_note_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_2', 'subuser' => 2, 'licensee_note_label_id' => 2, 'farm' => 54))->save();


	    Jelly::factory('subuser')->set(array('_id'		=> 3,
						 'temporary_id' => 'tmp_3',
						  'is_active'	=> true,
						  'is_root'	=> false,
						  'farm'        => 54,
						  'first_name'	=> 'Остап',
						  'middle_name'	=> 'Остапович',
						  'last_name'	=> 'Остапов',
						  'password'	=> 'PHPUNIT',
						  'position'	=> 'PHPUNIT',
						  'update_date'	=> 0,
						  'deleted'	=> 0,
						  'notes'	=> 'Заметки Остапыча',
						  'login'	=> 'PHPUNIT_3'))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_ostap_1@gmail.com', 'subuser' => 3, 'licensee_email_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_ostap_2@gmail.com', 'subuser' => 3, 'licensee_email_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-15', 'subuser' => 3, 'licensee_date_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-16', 'subuser' => 3, 'licensee_date_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_1', 'subuser' => 3, 'licensee_address_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_2', 'subuser' => 3, 'licensee_address_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_11-22-33-44', 'subuser' => 3, 'licensee_phone_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_55-66-77-88', 'subuser' => 3, 'licensee_phone_label_id' => 2, 'farm' => 54))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_1', 'subuser' => 3, 'licensee_note_label_id' => 1, 'farm' => 54))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_2', 'subuser' => 3, 'licensee_note_label_id' => 2, 'farm' => 54))->save();


	    Jelly::factory('subuser')->set(array('_id'		=> 4,
						  'is_active'	=> true,
						  'is_root'	=> false,
						  'farm'        => 54,
						  'first_name'	=> 'Михаил',
						  'middle_name'	=> 'Михайлович',
						  'last_name'	=> 'Михайлов',
						  'password'	=> 'PHPUNIT',
						  'position'	=> 'PHPUNIT',
						  'update_date'	=> 1000,
						  'deleted'	=> 1,
						  'notes'	=> 'Заметки Михалыча',
						  'login'	=> 'PHPUNIT_4'))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_mihail_1@gmail.com', 'subuser' => 4, 'licensee_email_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useremail')->set(array('email' => 'PHPUNIT_mihail_2@gmail.com', 'subuser' => 4, 'licensee_email_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-15', 'subuser' => 4, 'licensee_date_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userdate')->set(array('date' => '2010-12-16', 'subuser' => 4, 'licensee_date_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_1', 'subuser' => 4, 'licensee_address_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('useraddress')->set(array('address' => 'PHPUNIT_Vyborgskaya_2', 'subuser' => 4, 'licensee_address_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_11-22-33-44', 'subuser' => 4, 'licensee_phone_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('userphone')->set(array('phone' => 'PHPUNIT_55-66-77-88', 'subuser' => 4, 'licensee_phone_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_1', 'subuser' => 4, 'licensee_note_label_id' => 1, 'farm' => 54, 'deleted' => 1))->save();
	    Jelly::factory('usernote')->set(array('note' => 'PHPUNIT_note_2', 'subuser' => 4, 'licensee_note_label_id' => 2, 'farm' => 54, 'deleted' => 1))->save();


	    Jelly::factory('subuser')->set(array('_id'		=> 5,
						  'is_active'	=> true,
						  'is_root'	=> false,
						  'farm'        => 55,
						  'first_name'	=> 'PHPUNIT',
						  'middle_name'	=> 'PHPUNIT',
						  'last_name'	=> 'PHPUNIT',
						  'password'	=> 'PHPUNIT',
						  'position'	=> 'PHPUNIT',
						  'update_date'	=> time(),
						  'deleted'	=> 0,
						  'notes'	=> 'PHPUNIT',
						  'login'	=> 'PHPUNIT_5'))->save();*/

	    //поля
	    /*Jelly::factory('area')->set(array('_id'	     => 1,
					      'temporary_id' => 'tmp_1',
					      'name'	     => 'PHPUNIT_1',
					      'number'	     => 11,
					      'kadastr_size' => 1.1,
					      'size'	     => 2.2,
					      'perimeter'    => 10,
					      'farm'	     => 54,
					      'update_date'  => 0,
					      'deleted'	     => 0))->save();
	    Jelly::factory('area')->set(array('_id'	     => 2,
					      'temporary_id' => 'tmp_2',
					      'name'	     => 'PHPUNIT_2',
					      'number'	     => 22,
					      'kadastr_size' => 1.1,
					      'size'	     => 2.2,
					      'perimeter'    => 10,
					      'farm'	     => 55,
					      'update_date'  => 1000,
					      'deleted'	     => 1))->save();
	    Jelly::factory('area')->set(array('_id'	     => 3,
					      'name'	     => 'PHPUNIT_3',
					      'number'	     => 33,
					      'kadastr_size' => 1.1,
					      'size'	     => 2.2,
					      'perimeter'    => 10,
					      'farm'	     => 55,
					      'update_date'  => 0,
					      'deleted'	     => 0))->save();
	    Jelly::factory('area')->set(array('_id'	     => 4,
					      'name'	     => 'PHPUNIT_4',
					      'number'	     => 44,
					      'kadastr_size' => 1.1,
					      'size'	     => 2.2,
					      'perimeter'    => 10,
					      'farm'	     => 55,
					      'update_date'  => 1000,
					      'deleted'	     => 1))->save();*/

	    //планы
	    /*Jelly::factory('plan')->set(array('_id'	     => 1,
					      'temporary_id' => 'tmp_1',
					      'name'	     => 'PHPUNIT_1',
					      'type'	     => 1,
					      'desc'	     => 'PHPUNIT_1',
					      'date_from'    => '2011-12-14',
					      'date_to'	     => '2012-12-14',
					      'status'	     => 1,
					      'farm'	     => 54,
					      'update_date'  => 0,
					      'deleted'	     => 0))->save();
	    Jelly::factory('plan')->set(array('_id'	     => 2,
					      'name'	     => 'PHPUNIT_2',
					      'type'	     => 2,
					      'desc'	     => 'PHPUNIT_2',
					      'date_from'    => '2011-11-14',
					      'date_to'	     => '2012-11-14',
					      'status'	     => 0,
					      'farm'	     => 55,
					      'update_date'  => 1000,
					      'deleted'	     => 0))->save();*/

	    //планы по полю
	    /*Jelly::factory('task')->set(array('_id'	     => 1,
					      'temporary_id' => 'tmp_1',
					      'name'	     => 'PHPUNIT_p1_a1',
					      'type'	     => 1,
					      'plan'	     => 1,
					      'area'         => 1,
					      'atk'	     => 1,
					      'status'	     => 1,
					      'farm'	     => 54,
					      'update_date'  => 0,
					      'deleted'	     => 0))->save();
	    Jelly::factory('task')->set(array('_id'	     => 2,
					      'name'	     => 'PHPUNIT_p1_a2',
					      'type'	     => 1,
					      'plan'	     => 1,
					      'area'         => 2,
					      'products'     => array(3),
					      'cultures'     => array(1, 3),
					      'status'	     => 1,
					      'farm'	     => 55,
					      'update_date'  => 1000,
					      'deleted'	     => 0))->save*/

	    //типы культур
	    /*Jelly::factory('culturetype')->set(array('_id' => 1, 'name' => 'PHPUNIT_злаки', 'parent' => 0))->save();
	    Jelly::factory('culturetype')->set(array('_id' => 2, 'temporary_id' => 'tmp_2', 'name' => 'PHPUNIT_фрукты', 'parent' => 0))->save();
	    Jelly::factory('culturetype')->set(array('_id' => 3, 'name' => 'PHPUNIT_злаки_вкусные', 'parent' => 1))->save();
	    Jelly::factory('culturetype')->set(array('_id' => 4, 'name' => 'PHPUNIT_злаки_невкусные', 'parent' => 1))->save();
	    Jelly::factory('culturetype')->set(array('_id' => 5, 'name' => 'PHPUNIT_злаки_удаленные', 'parent' => 1, 'deleted' => 1))->save();

	    //культуры
	    Jelly::factory('culture')->set(array('_id' => 1, 'name' => 'PHPUNIT_гречка', 'type' => 1))->save();
	    Jelly::factory('culture')->set(array('_id' => 2, 'name' => 'PHPUNIT_рис', 'type' => 3))->save();
	    Jelly::factory('culture')->set(array('_id' => 3, 'name' => 'PHPUNIT_овсянка', 'type' => 3))->save();
	    Jelly::factory('culture')->set(array('_id' => 4, 'name' => 'PHPUNIT_пшено', 'type' => 4, 'deleted' => 1))->save();
	    Jelly::factory('culture')->set(array('_id' => 5, 'temporary_id' => 'tmp_5', 'name' => 'PHPUNIT_ячмень', 'type' => 4))->save();

	    //продукты
	    Jelly::factory('product')->set(array('_id' => 1, 'name' => 'PHPUNIT_каша'))->save();
	    Jelly::factory('product')->set(array('_id' => 2, 'name' => 'PHPUNIT_каша_с_молоком'))->save();
	    Jelly::factory('product')->set(array('_id' => 3, 'temporary_id' => 'tmp_3', 'name' => 'PHPUNIT_хлопья'))->save();
	    Jelly::factory('product')->set(array('_id' => 4, 'name' => 'PHPUNIT_мука', 'deleted' => 1))->save();

	    //АТК
	    Jelly::factory('atk')->set(array('_id' => 1, 'temporary_id' => 'tmp_1', 'name' => 'PHPUNIT_каши', 'products' => array(1, 2), 'cultures' => array(1,3,5)))->save();
	    Jelly::factory('atk')->set(array('_id' => 2, 'name' => 'PHPUNIT_хлопья', 'products' => array(3), 'cultures' => array(1,3)))->save();
*/

		//группы хозяйств
		Jelly::factory('farm')->set(array('_id'			=> 1,
										  'name'		=> 'Группа 1',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 0,
										  'is_active'   => true,
										  'is_group'	=> true,
                                          'admin'     => 54,
										  'color'		=> '123456',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 2,
										  'name'		=> 'Группа 2',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 0,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> true,
										  'color'		=> '1DF456',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 3,
										  'name'		=> 'Группа 3',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 0,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> true,
										  'color'		=> '123376',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 4,
										  'name'		=> 'Группа 4',
										  'update_date' => time(),
										  'path'		=> '/2/',
										  'parent'		=> 2,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> true,
										  'color'		=> '539456',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 5,
										  'name'		=> 'Группа 5',
										  'update_date' => time(),
										  'path'		=> '/2/',
										  'parent'		=> 2,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> true,
										  'color'		=> 'D2345C',
										  'license'		=> 1))->save();

		//names
		Jelly::factory('farm')->set(array('_id'			=> 6,
										  'name'		=> 'Хозяйство 1 --- гр.1',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 1,
										  'is_active'   => true,
                                          'admin'     => 54,
										  'is_group'	=> false,
										  'color'		=> 'D2715C',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 7,
										  'name'		=> 'Хозяйство 2 --- гр.1',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 1,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> false,
										  'color'		=> 'D27347',
										  'license'		=> 1))->save();
		Jelly::factory('farm')->set(array('_id'			=> 8,
										  'name'		=> 'Хозяйство 3 --- гр.5',
										  'update_date' => time(),
										  'path'		=> '',
										  'parent'		=> 5,
                                          'admin'     => 54,
										  'is_active'   => true,
										  'is_group'	=> false,
										  'color'		=> '15C347',
										  'license'		=> 1))->save();


		//группы культур
		Jelly::factory('glossary_culturegroup')->set(array('_id'						  => 1,
																		    'name'						 => 'Зерновые-колосовые',
																			'crop_rotation_interest' => 10,
																			'deleted'					  => false,
																		    'update_date'			   => time(),
																		    'path'						  => '',
																		    'parent'					 => 0,
																		    'color'						  => 'ff8201'))->save();

		Jelly::factory('glossary_culturegroup')->set(array('_id'						  => 2,
																		    'name'						 => 'Зерновые',
																			'crop_rotation_interest' => 35,
																			'deleted'					  => false,
																		    'update_date'			   => time(),
																		    'path'						  => '/1/',
																		    'parent'					 => 1,
																		    'color'						  => 'ff992b'))->save();

		Jelly::factory('glossary_culturegroup')->set(array('_id'						  => 3,
																		    'name'						 => 'Колосовые',
																			'crop_rotation_interest' => 55,
																			'deleted'					  => false,
																		    'update_date'			   => time(),
																		    'path'						  => '/1/',
																		    'parent'					 => 1,
																		    'color'						  => 'ffcb9c'))->save();

		Jelly::factory('glossary_culturegroup')->set(array('_id'						  => 4,
																		    'name'						 => 'Бобовые',
																			'crop_rotation_interest' => 16,
																			'deleted'					  => false,
																		    'update_date'			   => time(),
																		    'path'						  => '',
																		    'parent'					 => 0,
																		    'color'						  => '00c800'))->save();

		//названия культур
		Jelly::factory('glossary_culture')->set(array('_id'							 => 1,
																	'name'						 => 'Пшеница',
																	'title'						    => 'Пшеница Оз.',
																	'crop_rotation_interest' => 18,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 2,
																	'type'						   => 2,
																	'color'						   => 'fef471'))->save();

		Jelly::factory('glossary_culture')->set(array('_id'							 => 2,
																	'name'						 => 'Пшеница',
																	'title'						    => 'Пшеница Яр.',
																	'crop_rotation_interest' => 5,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 2,
																	'type'						   => 1,
																	'color'						   => 'fef492'))->save();

		Jelly::factory('glossary_culture')->set(array('_id'							 => 3,
																	'name'						 => 'Ячмень',
																	'title'						    => 'Ячмень Оз.',
																	'crop_rotation_interest' => 78,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 3,
																	'type'						   => 2,
																	'color'						   => '46fcd6'))->save();

		Jelly::factory('glossary_culture')->set(array('_id'							 => 4,
																	'name'						 => 'Ячмень',
																	'title'						    => 'Ячмень Яр.',
																	'crop_rotation_interest' => 90,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 3,
																	'type'						   => 1,
																	'color'						   => '79fcd6'))->save();

		Jelly::factory('glossary_culture')->set(array('_id'							 => 5,
																	'name'						 => 'Горох',
																	'title'						    => 'Горох',
																	'crop_rotation_interest' => 99,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 4,
																	'type'						   => 1,
																	'color'						   => 'c385f4'))->save();

		Jelly::factory('glossary_culture')->set(array('_id'							 => 6,
																	'name'						 => 'Соя',
																	'title'						    => 'Соя',
																	'crop_rotation_interest' => 12,
																	'deleted'					  => false,
																	'update_date'			   => time(),
																	'group'						  => 4,
																	'type'						   => 1,
																	'color'						   => 'c354f4'))->save();

		//предшественники
		Jelly::factory('glossary_predecessor')->set(array( '_id'			=> 1,
																			'deleted'		=> false,
																			'culture'		=> 5,
																			'predecessor' => 3,
																			'outer_mark' => 1,
																			'inner_mark' => 3,
																			'license'		=> 1))->save();
		Jelly::factory('glossary_predecessor')->set(array( '_id'			=> 2,
																			'deleted'		=> false,
																			'culture'		=> 1,
																			'predecessor' => 4,
																			'outer_mark' => 1,
																			'inner_mark' => 5,
																			'license'		=> 1))->save();
		Jelly::factory('glossary_predecessor')->set(array( '_id'			=> 3,
																			'deleted'		=> false,
																			'culture'		=> 5,
																			'predecessor' => 1,
																			'outer_mark' => 2,
																			'inner_mark' => 3,
																			'license'		=> 1))->save();
		Jelly::factory('glossary_predecessor')->set(array( '_id'			=> 4,
																			'deleted'		=> false,
																			'culture'		=> 6,
																			'predecessor' => 3,
																			'outer_mark' => 1,
																			'inner_mark' => 3,
																			'license'		=> 1))->save();
		Jelly::factory('glossary_predecessor')->set(array( '_id'			=> 5,
																			'deleted'		=> false,
																			'culture'		=> 4,
																			'predecessor' => 1,
																			'outer_mark' => 3,
																			'inner_mark' => 1,
																			'license'		=> 1))->save();

		//семена
		Jelly::factory('glossary_seed')->set(array('_id'					=> 1,
																  'name'				=> 'Гарант',
																  'bio_crop'			=>  54,
																  'crop_norm_min'	=>  10,
																  'crop_norm_mid'	=>  16,
																  'crop_norm_max'  =>  22,
																  'crop_norm_units'  =>  42,
																  'deleted'				 => false,
																  'update_date'		  => time(),
																  'group'				 => 2,
																  'color'				  => 'c33324',
																  'license'				  => 1))->save();
		Jelly::factory('glossary_seed')->set(array('_id'					=> 2,
																  'name'				=> 'Национальная',
																  'bio_crop'			=>  84,
																  'crop_norm_min'	=>  11,
																  'crop_norm_mid'	=>  18,
																  'crop_norm_max'  =>  52,
																  'crop_norm_units'  =>  43,
																  'deleted'				 => false,
																  'update_date'		  => time(),
																  'group'				 => 1,
																  'color'				  => 'cd3311',
																  'license'				  => 1))->save();
		Jelly::factory('glossary_seed')->set(array('_id'					=> 3,
																  'name'				=> 'Ажурная',
																  'bio_crop'			=>  34,
																  'crop_norm_min'	=>  20,
																  'crop_norm_mid'	=>  25,
																  'crop_norm_max'  =>  62,
																  'crop_norm_units'  =>  44,
																  'deleted'				 => false,
																  'update_date'		  => time(),
																  'group'				 => 2,
																  'color'				  => 'cf3564',
																  'license'				  => 1))->save();
		Jelly::factory('glossary_seed')->set(array('_id'					=> 4,
																  'name'				=> 'Пивоваренный украинский',
																  'bio_crop'			=>  44,
																  'crop_norm_min'	=>  20,
																  'crop_norm_mid'	=>  25,
																  'crop_norm_max'  =>  62,
																  'crop_norm_units'  =>  45,
																  'deleted'				 => false,
																  'update_date'		  => time(),
																  'group'				 => 3,
																  'color'				  => 'd23564',
																  'license'				  => 1))->save();
		Jelly::factory('glossary_seed')->set(array('_id'					=> 5,
																  'name'				=> 'Пивоваренный селект',
																  'bio_crop'			=>  104,
																  'crop_norm_min'	=>  30,
																  'crop_norm_mid'	=>  55,
																  'crop_norm_max'  =>  72,
																  'deleted'				 => false,
																  'update_date'		  => time(),
																  'group'				 => 4,
																  'color'				  => 'cf3d24',
																  'license'				  => 1))->save();

	    //правки, которые не пустили модели
	    /*$update_date_0 = array('subusers' => '1,3', 'areas' => '2,3', 'plans' => '1', 'areas_plans' => '1', 'farmers' => '54', 'atks' => '1',
				   'cultures' => '1,2,3', 'products' => '1,3', 'culture_types' => '1,2,3', 'user_emails' => '1,2,5,6',
				   'user_dates' => '1,2,5,6', 'user_addresses' => '1,2,5,6', 'user_phones' => '1,2,5,6', 'user_notes' => '1,2,5,6',
				   'email_labels' => '2', 'date_labels' => '2', 'address_labels' => '2', 'phone_labels' => '2', 'note_labels' => '2',
				   'licensee_email_labels' => '2', 'licensee_date_labels' => '2', 'licensee_address_labels' => '2', 'licensee_phone_labels' => '2', 'licensee_note_labels' => '2');

	    $update_date_1000 = array('subusers' => '2,4', 'areas' => '1,4', 'plans' => '2', 'areas_plans' => '2', 'atks' => '2',
				      'cultures' => '4,5', 'products' => '2,4', 'culture_types' => '4,5', 'user_emails' => '3,4,7,8',
				      'user_dates' => '3,4,7,8', 'user_addresses' => '3,4,7,8', 'user_phones' => '3,4,7,8', 'user_notes' => '3,4,7,8',
				      'email_labels' => '1,3', 'date_labels' => '1', 'address_labels' => '1', 'phone_labels' => '1', 'note_labels' => '1',
				      'licensee_email_labels' => '1', 'licensee_date_labels' => '1', 'licensee_address_labels' => '1', 'licensee_phone_labels' => '1', 'licensee_note_labels' => '1');

	    $db = Database::instance();
	    foreach($update_date_0 as $table => $val){
		$db->query(DATABASE::UPDATE, 'UPDATE '.$table.' SET update_date = 0 WHERE _id IN ('.$val.')', true);
	    }
	    foreach($update_date_1000 as $table => $val){
		$db->query(DATABASE::UPDATE, 'UPDATE '.$table.' SET update_date = 1000 WHERE _id IN ('.$val.')', true);
	    }

	    $db->query(DATABASE::UPDATE, 'UPDATE farmers SET max_ms = 0 WHERE _id IN (55)', true);

		$db->query(DATABASE::UPDATE, 'UPDATE groups SET parent_id = 2 WHERE _id IN (4,5)', true);*/

	    Request::instance()->redirect(Request::instance()->uri(array('action'=>'')));
	}

	public function action_update_common_data(){
		$common_models = array('glossary_preparativeform', 'glossary_units', 'glossary_culturetype', 'glossary_acidity', 'glossary_groundtype', 'client_planning_atkstatus', 'client_planning_planstatus', 'client_planning_atktype', 'client_work_orderstatus', 'client_sharestatus', 'client_sharealertperiod');

		foreach($common_models as $model){
	        Migration::factory($model, 'jelly')->remove();
	    }

	    foreach($common_models as $model){
	        Migration::factory($model, 'jelly')->sync();
	    }

		//ТИПЫ КУЛЬТУР
		Jelly::factory('glossary_culturetype')->set(array('_id' => 1, 'name' => 'Яр.' ))->save();
		Jelly::factory('glossary_culturetype')->set(array('_id' => 2, 'name' => 'Оз.' ))->save();

		//ЕДИНИЦЫ ИЗМЕРЕНИЯ
		Jelly::factory('glossary_units')->set(array('_id' => 1, 'name' => 'т', 'block' => 'amount', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 2, 'name' => 'ц', 'block' => 'amount', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 3, 'name' => 'кг', 'block' => 'amount', 'order' =>3))->save();
        Jelly::factory('glossary_units')->set(array('_id' => 27,'name' => 'г', 'block' => 'amount', 'order' =>4))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 4, 'name' => 'л', 'block' => 'amount', 'order' =>5))->save();
        Jelly::factory('glossary_units')->set(array('_id' => 28,'name' => 'п.е', 'block' => 'amount_seed', 'order' =>6))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 5, 'name' => 'час', 'block' => 'personal_time', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 6, 'name' => 'га/час', 'block' => 'personal_productivity', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 7, 'name' => 'т/час', 'block' => 'personal_productivity', 'order' =>2))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 8, 'name' => 'грн/мес', 'block' => 'personal_payment', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 10, 'name' => 'т', 'block' => 'gsm', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 11, 'name' => 'ц', 'block' => 'gsm', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 12, 'name' => 'кг', 'block' => 'gsm', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 9,   'name' => 'л', 'block' => 'gsm', 'order' =>4))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 13, 'name' => 'т/га', 'block' => 'seed_bio_crop', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 14, 'name' => 'кг/га', 'block' => 'seed_bio_crop', 'order' =>3))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 15, 'name' => 'м', 'block' => 'tech_grasp', 'order' =>1))->save();
//		Jelly::factory('glossary_units')->set(array('_id' => 16, 'name' => 'км', 'block' => 'tech_grasp', 'order' =>2))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 17, 'name' => 'га/час', 'block' => 'tech_productivity', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 18, 'name' => 'га/смену', 'block' => 'tech_productivity', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 19, 'name' => 'га/сутки', 'block' => 'tech_productivity', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 20, 'name' => 'км/час', 'block' => 'tech_productivity', 'order' =>4))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 21, 'name' => 'л/га', 'block' => 'tech_fuel_work', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 22, 'name' => 'л/км', 'block' => 'tech_fuel_work', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 23, 'name' => 'л/100км', 'block' => 'tech_fuel_work', 'order' =>3))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 24, 'name' => 'л/га', 'block' => 'tech_fuel_idle', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 25, 'name' => 'л/км', 'block' => 'tech_fuel_idle', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 26, 'name' => 'л/100км', 'block' => 'tech_fuel_idle', 'order' =>3))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 31, 'name' => 'т/га', 'block' => 'szr_fertilizer_norm', 'order' =>1))->save();
//		Jelly::factory('glossary_units')->set(array('_id' => 32, 'name' => 'ц/га', 'block' => 'szr_fertilizer_norm', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 33, 'name' => 'кг/га', 'block' => 'szr_fertilizer_norm', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 34, 'name' => 'гр/га', 'block' => 'szr_fertilizer_norm', 'order' =>4))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 35, 'name' => 'л/га', 'block' => 'szr_fertilizer_norm', 'order' =>5))->save();


		Jelly::factory('glossary_units')->set(array('_id' => 36, 'name' => 'л.с.', 'block' => 'max_power', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 37, 'name' => 'л', 'block' => 'work_volume', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 38, 'name' => 'кг/л.с.ч.', 'block' => 'fuel_cosumption', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 39, 'name' => 'л', 'block' => 'tank_capacity', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 40, 'name' => 'ч', 'block' => 'oil_change_period', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 41, 'name' => 'д', 'block' => 'oil_change_period', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 42, 'name' => 'мес.', 'block' => 'oil_change_period', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 43, 'name' => 'г', 'block' => 'oil_change_period', 'order' =>4))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 44, 'name' => 'т/га', 'block' => 'seed_crop_norm', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 45, 'name' => 'ц/га', 'block' => 'seed_crop_norm', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 46, 'name' => 'кг/га', 'block' => 'seed_crop_norm', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 47, 'name' => 'п.е./га', 'block' => 'seed_crop_norm', 'order' =>4))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 48, 'name' => 'см', 'block' => 'soil_depth', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 49, 'name' => 'т', 'block' => 'lift_capacity', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 50, 'name' => 'т', 'block' => 'weight', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 51, 'name' => 'м', 'block' => 'length', 'order' =>1))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 52, 'name' => 'грн/га', 'block' => 'personal_payment', 'order' =>2))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 53, 'name' => 'грн', 'block' => 'work_hour_cost', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 54, 'name' => 'ц/га', 'block' => 'seed_bio_crop', 'order' =>2))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 63, 'name' => 'т/га', 'block' => 'szr_norm_amount', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 66, 'name' => 'ц/га', 'block' => 'szr_norm_amount', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 59, 'name' => 'кг/га', 'block' => 'szr_norm_amount', 'order' =>3))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 64, 'name' => 'г/га', 'block' => 'szr_norm_amount', 'order' =>4))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 58, 'name' => 'л/га', 'block' => 'szr_norm_amount', 'order' =>5))->save();

		Jelly::factory('glossary_units')->set(array('_id' => 61, 'name' => 'гр/кг', 'block' => 'szr_dv_amount', 'order' =>1))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 62, 'name' => 'гр/л', 'block' => 'szr_dv_amount', 'order' =>2))->save();
		Jelly::factory('glossary_units')->set(array('_id' => 65, 'name' => 'грн', 'block' => 'personal_payment', 'order' =>3))->save();
        
        Jelly::factory('glossary_units')->set(array('_id' => 67, 'name' => 'мг/кг', 'block' => 'chemical_composition', 'order' =>1))->save();
        Jelly::factory('glossary_units')->set(array('_id' => 68, 'name' => 'кг/га', 'block' => 'chemical_composition', 'order' =>2))->save();


        //СПОСОБЫ ВНЕСЕНИЯ
//		Jelly::factory('glossary_deploymenttype')->set(array('_id' => 1, 'name' => 'Опрыскивание', 'color' => 'e87372'))->save();
//		Jelly::factory('glossary_deploymenttype')->set(array('_id' => 2, 'name' => 'Авиаметод', 'color' => 'fc7d7b'))->save();


		//ПРЕПАРАТИВНАЯ ФОРМА
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 1,  'order' =>1,   'color' => '612a89', 'short_name' => '(в.д.г.)', 'name' => 'Воднодисперсные гранулы'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 2,  'order' =>2,   'color' => '7232a3', 'short_name' => '(в.г., в.р.г.)', 'name' => 'Водорастворимые гранулы'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 3,  'order' =>3,   'color' => '8d41c5', 'short_name' => '(в.э.)', 'name' => 'Водная эмульсия'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 4,  'order' =>4,   'color' => 'a14ae1', 'short_name' => '(в.к.)', 'name' => 'Водорастворимый концентрат'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 5,  'order' =>5,   'color' => 'b051f9', 'short_name' => '(в.р.)', 'name' => 'Водный раствор'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 6,  'order' =>6,   'color' => 'b785e5', 'short_name' => '(в.с.р.)', 'name' => 'Водно-спиртовый раствор'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 7,  'order' =>7,   'color' => 'c992fb',  'short_name' => '(в.с.к.)', 'name' => 'Водно-суспензионный концентрат'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 8,  'order' =>8,   'color' => 'fd8ad5',  'short_name' => '(гр.)', 'name' => 'Гранулы'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 9,  'order' =>9,   'color' => 'fe76f7',  'short_name' => '(ж.)',  'name' => 'Жидкость'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 10, 'order' =>10, 'color' => '087317', 'short_name' => '(к.э.)', 'name' => 'Концентрат эмульсии'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 11, 'order' =>11, 'color' => '0e8e1e', 'short_name' => '(к.п.)', 'name' => 'Кристаллический порошок'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 12, 'order' =>12, 'color' => '13b329', 'short_name' => '(к.с.)', 'name' => 'Концентрат суспензии'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 13, 'order' =>13, 'color' => '1ad433', 'short_name' => '(к.н.э.)', 'name' => 'Концентрат наноэмульсии'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 14, 'order' =>14, 'color' => '1ce738', 'short_name' => '(кр.)', 'name' => 'Кристаллы'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 15, 'order' =>15, 'color' => '41458a', 'short_name' => '(мв.э.)', 'name' => 'Масляно-водная эмульсия'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 16, 'order' =>16, 'color' => '5c63c0', 'short_name' => '(мк.с.)', 'name' => 'Микрокапсулированная водная суспензия'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 17, 'order' =>17, 'color' => '6c76e2', 'short_name' => '(мк.э.)', 'name' => 'Микрокапсулированная эмульсия'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 18, 'order' =>18, 'color' => '7581fa', 'short_name' => '(м.с.)', 'name' => 'Масляная суспензия'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 19, 'order' =>19, 'color' => 'c91911', 'short_name' => '(пс.)', 'name' => 'Паста'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 20, 'order' =>20, 'color' => 'fb2217', 'short_name' => '(п.)', 'name' => 'Порошок'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 21, 'order' =>21, 'color' => 'fc9229', 'short_name' => '(р.)', 'name' => 'Раствор'))->save();
		Jelly::factory('glossary_preparativeform')->set(array('_id' => 22, 'order' =>22, 'color' => 'fdd381', 'short_name' => '(с.)', 'name' => 'Смесь'))->save();

		//КИСЛОТНОСТЬ
		Jelly::factory('glossary_acidity')->set(array('_id' => 1, 'order' =>1, 'color' => 'f24469', 'acidity_from' => '3.5', 'acidity_to' => '4.0', 'name' => 'Сильнокислые почвы'))->save();
		Jelly::factory('glossary_acidity')->set(array('_id' => 2, 'order' =>2, 'color' => 'f24469', 'acidity_from' => '4.0', 'acidity_to' => '5.0', 'name' => 'Кислые почвы'))->save();
		Jelly::factory('glossary_acidity')->set(array('_id' => 3, 'order' =>3, 'color' => 'f24469', 'acidity_from' => '5.0', 'acidity_to' => '6.0', 'name' => 'Слабокислые почвы'))->save();
		Jelly::factory('glossary_acidity')->set(array('_id' => 4, 'order' =>4, 'color' => 'f24469', 'acidity_from' => '6.0', 'acidity_to' => '7.0', 'name' => 'Нейтральный почвы'))->save();
		Jelly::factory('glossary_acidity')->set(array('_id' => 5, 'order' =>5, 'color' => 'f24469', 'acidity_from' => '7.0', 'acidity_to' => '8.0', 'name' => 'Щелочные почвы'))->save();
		Jelly::factory('glossary_acidity')->set(array('_id' => 6, 'order' =>6, 'color' => 'f24469', 'acidity_from' => '8.0', 'acidity_to' => '8.5', 'name' => 'Сильнощелочные почвы'))->save();

		//ТИП ПОЧВЫ
		Jelly::factory('glossary_groundtype')->set(array('_id' => 1, 'order' =>1, 'color' => 'f24469', 'name' => 'Глина'))->save();
		Jelly::factory('glossary_groundtype')->set(array('_id' => 2, 'order' =>2, 'color' => 'f24469', 'name' => 'Песок'))->save();
		Jelly::factory('glossary_groundtype')->set(array('_id' => 3, 'order' =>3, 'color' => 'f24469', 'name' => 'Известняк'))->save();
		Jelly::factory('glossary_groundtype')->set(array('_id' => 4, 'order' =>4, 'color' => 'f24469', 'name' => 'Торф'))->save();
		Jelly::factory('glossary_groundtype')->set(array('_id' => 5, 'order' =>5, 'color' => 'f24469', 'name' => 'Суглинок'))->save();
		Jelly::factory('glossary_groundtype')->set(array('_id' => 6, 'order' =>6, 'color' => 'f24469', 'name' => 'Супесь'))->save();

		//СТАТУСЫ АТК
		Jelly::factory('client_planning_atkstatus')->set(array('_id' => 1, 'order' =>1, 'color' => '612a89', 'name' => 'Черновик'))->save();
		Jelly::factory('client_planning_atkstatus')->set(array('_id' => 2, 'order' =>2, 'color' => '7232a3', 'name' => 'Рабочий'))->save();
		Jelly::factory('client_planning_atkstatus')->set(array('_id' => 3, 'order' =>3, 'color' => '8d41c5', 'name' => 'Утверждённый'))->save();

		//ТИПЫ АТК
		Jelly::factory('client_planning_atktype')->set(array('_id' => 1, 'order' =>1, 'color' => '612a89', 'name' => 'Интенсивная'))->save();
		Jelly::factory('client_planning_atktype')->set(array('_id' => 2, 'order' =>2, 'color' => '7232a3', 'name' => 'Экстенсивная'))->save();
		Jelly::factory('client_planning_atktype')->set(array('_id' => 3, 'order' =>3, 'color' => '8d41c5', 'name' => 'Оптимальная'))->save();

        //СТАТУСЫ ПЛАНА
		Jelly::factory('client_planning_planstatus')->set(array('_id' => 1, 'order' =>1, 'color' => '612a89', 'name' => 'Черновик'))->save();
        Jelly::factory('client_planning_planstatus')->set(array('_id' => 2, 'order' =>2, 'color' => '7232a3', 'name' => 'Рабочий'))->save();
        Jelly::factory('client_planning_planstatus')->set(array('_id' => 3, 'order' =>3, 'color' => '8d41c5', 'name' => 'Утверждённый'))->save();
		
		
		//СТАТУСЫ НАРЯДА
		Jelly::factory('client_work_orderstatus')->set(array('_id' => 1, 'order' =>1, 'color' => '612a89', 'name' => 'Выдан'))->save();
        Jelly::factory('client_work_orderstatus')->set(array('_id' => 2, 'order' =>2, 'color' => '7232a3', 'name' => 'Новый наряд'))->save();
        Jelly::factory('client_work_orderstatus')->set(array('_id' => 3, 'order' =>3, 'color' => '8d41c5', 'name' => 'Закрыт'))->save();
		Jelly::factory('client_work_orderstatus')->set(array('_id' => 4, 'order' =>4, 'color' => '8d3289', 'name' => 'Отменён'))->save();
		
		//СТАТУСЫ ПАЯ
		Jelly::factory('client_sharestatus')->set(array('_id' => 1, 'color' => '98cc6b', 'name' => 'Активный'))->save();
		Jelly::factory('client_sharestatus')->set(array('_id' => 2, 'color' => 'f3bf6d', 'name' => 'Срок аренды подходит к окончанию'))->save();
		Jelly::factory('client_sharestatus')->set(array('_id' => 3, 'color' => 'e64744', 'name' => 'Срок аренды истёк'))->save();

		//ОПОВЕЩЕНИЯ ОБ ИСТЕЧЕНИИ СРОКА АРЕНДЫ
		Jelly::factory('client_sharealertperiod')->set(array('_id' => 1, 'order' => 1, 'color' => '98cc6b', 'name' => 'За 1 день',  'seconds' => 86400))->save();
		Jelly::factory('client_sharealertperiod')->set(array('_id' => 2, 'order' => 2, 'color' => '98cc6b', 'name' => 'За 7 дней',  'seconds' => 604800))->save();
		Jelly::factory('client_sharealertperiod')->set(array('_id' => 3, 'order' => 3, 'color' => '98cc6b', 'name' => 'За 1 месяц', 'seconds' => 2592000))->save();
		Jelly::factory('client_sharealertperiod')->set(array('_id' => 4, 'order' => 4, 'color' => '98cc6b', 'name' => 'За год',     'seconds' => 31536000))->save();

		
		// system_producers
		//СИСТЕМНЫЕ ПРОИЗВОДИТЕЛИ
		$params_for_p1=array('_id' => 1001,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => '2097ff', 'name' => 'Производители Семян');
		$p1 =Jelly::select('client_producergroup',$params_for_p1['_id']);
		if($p1){
			$p1->set($params_for_p1)->save();
		} else {
			$p1 = Jelly::factory('client_producergroup')->set($params_for_p1)->save();
		}

		$params_for_p2=array('_id' => 1002,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => '65bb1a', 'name' => 'Производители СЗР');
		$p2 =Jelly::select('client_producergroup',$params_for_p2['_id']);
		if($p2){
			$p2->set($params_for_p2)->save();
		} else {
			$p2 = Jelly::factory('client_producergroup')->set($params_for_p2)->save();
		}

		$params_for_p3=array('_id' => 1003,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => '7d83fd', 'name' => 'Производители Удобрений');
		$p3 =Jelly::select('client_producergroup',$params_for_p3['_id']);
		if($p3){
			$p3->set($params_for_p3)->save();
		} else {
			$p3 = Jelly::factory('client_producergroup')->set($params_for_p3)->save();
		}

		$params_for_p4=array('_id' => 1004,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => 'fe2c2d', 'name' => 'Производители Техники');
		$p4 =Jelly::select('client_producergroup',$params_for_p4['_id']);
		if($p4){
			$p4->set($params_for_p4)->save();
		} else {
			$p4 = Jelly::factory('client_producergroup')->set($params_for_p4)->save();
		}

                // system_szrgroups
		//СИСТЕМНЫЕ ГРУППЫ СЗР
		$params_for_p1=array('_id' => 1001,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => 'fe3018', 'name' => 'Гербициды');
		$p1 =Jelly::select('glossary_szrgroup',$params_for_p1['_id']);
		if($p1){
			$p1->set($params_for_p1)->save();
		} else {
			$p1 = Jelly::factory('glossary_szrgroup')->set($params_for_p1)->save();
		}

		$params_for_p2=array('_id' => 1002,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => '00c11a', 'name' => 'Инсектициды');
		$p2 =Jelly::select('glossary_szrgroup',$params_for_p2['_id']);
		if($p2){
			$p2->set($params_for_p2)->save();
		} else {
			$p2 = Jelly::factory('glossary_szrgroup')->set($params_for_p2)->save();
		}

		$params_for_p3=array('_id' => 1003,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => '204aee', 'name' => 'Протравители');
		$p3 =Jelly::select('glossary_szrgroup',$params_for_p3['_id']);
		if($p3){
			$p3->set($params_for_p3)->save();
		} else {
			$p3 = Jelly::factory('glossary_szrgroup')->set($params_for_p3)->save();
		}

		$params_for_p4=array('_id' => 1004,'deleted' =>0,'path'=>'/','update_date' =>time(), 'parent_id' =>0, 'color' => 'fcf52b', 'name' => 'Фунгициды');
		$p4 =Jelly::select('glossary_szrgroup',$params_for_p4['_id']);
		if($p4){
			$p4->set($params_for_p4)->save();
		} else {
			$p4 = Jelly::factory('glossary_szrgroup')->set($params_for_p4)->save();
		}

		Request::instance()->redirect(Request::instance()->uri(array('action'=>'')));
	}
	
	
	public function action_clear_photo(){
		$this->clear_photo_folders();
		
		Request::instance()->redirect(
	    	Request::instance()->uri(array('action'=>''))
		);
	}
	
	
	public function clear_photo_folders(){
		$dir = DOCROOT.'media/pictures/';
		try{
			$this->removedir($dir, false);
		}catch(Exception $e){
			//нет слов
		}
	}
	
	private function removedir($directory, $delete_directory = true) { 
		$dir = opendir($directory); 
		while(false !== ($file = readdir($dir))) 
		{ 
			if (is_file($directory.$file)) { 
				unlink($directory.$file); 
			} else if (is_dir($directory.$file) && ($file != ".") && ($file != "..")) { 
				$this->removedir($directory.$file."/"); 
			} 
		} 
		closedir($dir); 
		if($delete_directory) rmdir($directory); 
		return true;  
	}  
	
}
