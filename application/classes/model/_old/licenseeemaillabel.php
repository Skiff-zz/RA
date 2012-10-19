<?php

class Model_LicenseeEmailLabel extends AC_UserInfoLicensee
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='email', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('licensee_email_labels')
	    ->fields(array(
		
	    ));
    }

}
