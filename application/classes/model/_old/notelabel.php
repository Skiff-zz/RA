<?php

class Model_NoteLabel extends AC_UserInfoLabel
{
    public $is_dictionary = true;

    public static function initialize(Jelly_Meta $meta, $type='note', $is_dictionary = true)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('note_labels')
	    ->fields(array(

	    ));
    }

}
