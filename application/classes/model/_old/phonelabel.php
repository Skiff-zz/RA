<?php

class Model_PhoneLabel extends AC_UserInfoLabel
{
    public $is_dictionary = true;

    public static function initialize(Jelly_Meta $meta, $type='phone', $is_dictionary = true)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('phone_labels')
	    ->fields(array(

	    ));
    }

}
