<?php defined('SYSPATH') or die('No direct script access.');
/**
* 
*/
class Auth_Ac extends Auth_Jelly
{
	
	/**
	 * Logs a user in.
	 *
	 * @param   string   username
	 * @param   string   password
	 * @param   boolean  enable auto-login
	 * @return  boolean
	 */
	public function _login($user, $password, $remember)
	{
		// Make sure we have a user object
		$user = $this->_get_object($user);

		// If the passwords match, perform a login
		if ($user->password === $password)
		{
			if ($remember === TRUE)
			{
				// Create a new autologin token
				$token = Model::factory('user_token');

				// Set token data
				$token->user = $user->id();
				$token->expires = time() + $this->_config['lifetime'];

				$token->create();

				// Set the autologin Cookie
				Cookie::set('authautologin', $token->token, $this->_config['lifetime']);
			}

			// Finish the login
			$this->complete_login($user);

			return TRUE;
		}

		// Login failed
		return FALSE;
	}
	
	protected function _get_object($user)
	{
		static $current;
		
		//make sure the user is loaded only once.
		if ( ! is_object($current) AND is_string($user))
		{
			// Load the user
			$current = Jelly::select('user')->where('username', '=', $user)->where_open()->where('deleted', '=', 0)->or_where('deleted', 'IS', null)->where_close()->limit(1)->execute();
		}

		if ($user instanceof Model_User AND $user->loaded()) 
		{
			$current = $user;
		}

		return $current;
	}
}


