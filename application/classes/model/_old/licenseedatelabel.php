<?php

class Model_LicenseeDateLabel extends AC_UserInfoLicensee
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='date', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('licensee_date_labels')
	    ->fields(array(

	    ));
    }

}
