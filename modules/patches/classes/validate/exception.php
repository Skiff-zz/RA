<?php defined('SYSPATH') or die('No direct script access.');

class Validate_Exception extends Kohana_Validate_Exception {
	
	public function __construct(Validate $array, $message = 'Failed to validate array', array $values = NULL, $code = 0)
	{
		parent::__construct($array, $message, $values, 1001);
	}
}
