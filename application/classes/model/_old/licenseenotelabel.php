<?php

class Model_LicenseeNoteLabel extends AC_UserInfoLicensee
{
    public $is_dictionary = false;

    public static function initialize(Jelly_Meta $meta, $type='note', $is_dictionary = false)
    {
	parent::initialize($meta, $type, $is_dictionary);

	$meta->table('licensee_note_labels')
	    ->fields(array(

	    ));
    }

}
