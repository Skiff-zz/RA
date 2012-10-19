<?php defined('SYSPATH') or die('No direct script access.');

/**
 * JSON Helper
 * 
 * */

	class JSON
	{
		public static function reply($string, $url = null)
		{
			return json_encode(array(	'success' 	=> true,
										'script'	=> $string,
										'url'		=> $url)
							  );			
		}
		
		public static function error($string,$fields=array())
		{
			return json_encode(array(	'success' 	=> false,
										'error'		=> $string,
										'fields'	=> $fields)
							  );			
		}
		
		public static function tree($array, $count) 
		{
			return JSON::arr($array, $count);			
		}
		
		public static function arr($array, $count) 
		{
			$r = array( 'count' => $count,
						'data'  => $array);
			
			return json_encode($r);			
		}
		
		public static function success($array)
		{
			$array['result'] = true;
			
			return json_encode($array);
		}
		
		public static function fail($array)
		{
			$array['result'] = false;
			
			return json_encode($array);
		}	
		
	}
	
?>