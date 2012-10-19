<?php

class Model_LicenseePhoneLabel extends AC_UserInfoLicensee
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='phone', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('licensee_phone_labels')
	    ->fields(array(

	    ));
    }

}
