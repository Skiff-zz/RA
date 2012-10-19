<?php

class Model_UserEmail extends AC_UserInfoData
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='email', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('user_emails')
	    ->fields(array(
		'email' => new Field_Email(array(
		    'label' => 'E-Mail',
		    'rules' => array(
			'max_length' => array(1000),
			'min_length' => array(3)
		    )
		))
	    ));
    }

}
