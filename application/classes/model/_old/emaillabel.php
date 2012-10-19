<?php

class Model_EmailLabel extends AC_UserInfoLabel
{
    public $is_dictionary = true;

    public static function initialize(Jelly_Meta $meta, $type='email', $is_dictionary = true)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('email_labels')
	    ->fields(array(

	    ));
    }

}
