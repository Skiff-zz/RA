<?php defined('SYSPATH') or die('No direct script access.');

/**
 *  This code is based on Jelly Timestamp field
 *  but has defference in storage method
 * 
 *  In timestamp, we keep date as int, but in this class we
 *  keep original inserted date
 * 
 *  And feature should be mentioned - we can change 
 *  date saving format in xml as format="<Php date function format>"
 **/ 

class Field_Datetime extends Jelly_Field
{
	/**
	 * @var  boolean  Whether or not to automatically set now() on creation
	 */
	public $auto_now_create = FALSE;

	/**
	 * @var  boolean  Whether or not to automatically set now() on update
	 */
	public $auto_now_update = FALSE;

	/**
	 * @var  string  A date formula representing the time in the database
     * 
     * yyyymmdd hh:mm:ss.mmm for mssql
     * aka 2010-05-21 12:36:28.950
	 */
    public $format = 'Y-m-j H:i:s.000';
    
	/**
	 * @var  string  A pretty format used for representing the date to users
	 */
	public $pretty_format = 'd.m.Y H:i:s';

	/**
	 * Automatically creates or updates the time and
	 * converts it, if necessary
	 *
	 * @param   Jelly  $model
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function save($model, $value, $loaded)
	{
		if (( ! $loaded AND $this->auto_now_create) OR ($loaded AND $this->auto_now_update))
		{
			$value = time();
		}
        
        // php doesn`t have (?) more accurate date storage
        // than timestamp, so we are limited 1901-2038 years
		
        if (is_string($value))        
            $value = strtotime($value);
        
        if (is_object($value))
        {
            if ($value instanceof DateTime)
                $value = $value->getTimestamp();
            else
                throw new Kohana_Exception('Date is not a date!');
        }
        
		// Does it need converting?
		if (FALSE !== strtotime($value))
		{
			$value = strtotime($value);
		}
        
        if ($value == 0)
            return NULL;

		$value = date($this->format, $value);

		return $value;
	}
    
	public function get($model, $value)
	{
        if (is_string($value))
        {
            $value = strtotime($value);
        } 
        
        if (is_object($value))   //sometimes we have DateTime object here!
        {
            return date_format($value, $this->pretty_format);
        }
        elseif ((string)(integer)$value === (string)$value)
        {
            return date($this->pretty_format, $value);
        }
        else
        {
            return $value;
        }
	}
    
    public function input($prefix = 'jelly/field', $data = array())
    {
        $data['value'] = $this->get($data['model'], $data['value']);
        
        return parent::input($prefix, $data);
    }
    
}