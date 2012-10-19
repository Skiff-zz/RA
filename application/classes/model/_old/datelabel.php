<?php

class Model_DateLabel extends AC_UserInfoLabel
{
    public $is_dictionary = true;

    public static function initialize(Jelly_Meta $meta, $type='date', $is_dictionary = true)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('date_labels')
	    ->fields(array(

	    ));
    }

}
