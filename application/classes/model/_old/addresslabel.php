<?php

class Model_AddressLabel extends AC_UserInfoLabel
{
    public $is_dictionary = true;

    public static function initialize(Jelly_Meta $meta, $type='address', $is_dictionary = true)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('address_labels')
	    ->fields(array(

	    ));
    }

}
