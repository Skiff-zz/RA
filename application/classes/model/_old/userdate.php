<?php

class Model_UserDate extends AC_UserInfoData
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='date', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('user_dates')
	    ->fields(array(
		'date' => Jelly::field('String', array('label' => 'Дата')),
	    ));
    }

}
