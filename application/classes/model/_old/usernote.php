<?php

class Model_UserNote extends AC_UserInfoData
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='note', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('user_notes')
	    ->fields(array(
		'note' => Jelly::field('Text', array('label' => 'Заметки')),
	    ));
    }

}
