<?php
/**
 * PHPUnit testsuite for kohana application
 *
 * @package    Kohana/Unittest
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @author	   Paul Banks
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
 set_include_path (DOCROOT."/PHPUnit");
 
class Kohana_Tests
{
	static protected $cache = array();

	/**
	 * Loads test files if they cannot be found by kohana
	 * @param <type> $class
	 */
	static function autoload($class)
	{
		$file = str_replace('_', '/', $class);

		if ($file = Kohana::find_file('tests', $file))
		{
			require_once $file;
		}
	}

	/**
	 * Configures the environment for testing
	 *
	 * Does the following:
	 *
	 * * Loads the phpunit framework (for the web ui)
	 * * Restores exception phpunit error handlers (for cli)
	 * * registeres an autoloader to load test files
	 */
	static public function configure_environment($do_whitelist = TRUE, $do_blacklist = TRUE)
	{
		if ( ! class_exists('PHPUnit_Util_Filter', FALSE))
		{
			// Make sure the PHPUnit classes are available
			require_once 'PHPUnit/Framework.php';
		}

		if (Kohana::$is_cli)
		{
			restore_exception_handler();
			restore_error_handler();
		}

		spl_autoload_register(array('Kohana_Tests', 'autoload'));

		Kohana_Tests::$cache = ($cache = Kohana::cache('unittest_whitelist_cache')) === NULL ? array() : $cache;

		$config = Kohana::config('unittest');

		if ($do_whitelist AND $config->use_whitelist)
		{
			self::whitelist();
		}

		if ($do_blacklist AND count($config['blacklist']))
		{
			foreach ($config->blacklist as $item)
			{
				if (is_dir($item))
				{
					PHPUnit_Util_Filter::addDirectoryToFilter($item);
				}
				else
				{
					PHPUnit_Util_Filter::addFileToFilter($item);
				}
			}
		}
	}

	/**
	 * Helper function to see if unittest is enabled in the config
	 *
	 * @return boolean
	 */
	static function enabled()
	{
		$p_environment = Kohana::config('unittest.environment');
		$k_environment = Kohana::$environment;

		return  (is_array($p_environment) AND in_array($k_environment, $p_environment))
				OR
				($k_environment === $p_environment);
	}

	/**
	 * Creates the test suite for kohana
	 *
	 * @return PHPUnit_Framework_TestSuite
	 */
	static function suite()
	{
		static $suite = NULL;

		if ($suite instanceof PHPUnit_Framework_TestSuite)
		{
			return $suite;
		}

		$files = Kohana::list_files('tests');

		$suite = new PHPUnit_Framework_TestSuite();

		self::addTests($suite, $files);

		return $suite;
	}

	/**
	 * Add files to test suite $suite
	 *
	 * Uses recursion to scan subdirectories
	 *
	 * @param PHPUnit_Framework_TestSuite  $suite   The test suite to add to
	 * @param array                        $files   Array of files to test
	 */
	static function addTests(PHPUnit_Framework_TestSuite $suite, array $files)
	{
		foreach ($files as $file)
		{
			if (is_array($file))
			{
				self::addTests($suite, $file);
			}
			else
			{
				// Make sure we only include php files
				if (is_file($file) AND substr($file, -strlen(EXT)) === EXT)
				{
					// The default PHPUnit TestCase extension
					if ( ! strpos($file, 'TestCase'.EXT))
					{
						$suite->addTestFile($file);
					}
					else
					{
						require_once($file);
					}

					PHPUnit_Util_Filter::addFileToFilter($file);
				}
			}
		}
	}

	/**
	 * Sets the whitelist
	 *
	 * If no directories are provided then the function'll load the whitelist
	 * set in the config file
	 *
	 * @param array $directories Optional directories to whitelist
	 */
	static public function whitelist(array $directories = NULL)
	{
		if (empty($directories))
		{
			$directories = self::get_config_whitelist();
		}

		if (count($directories))
		{
			foreach ($directories as &$directory)
			{
				$directory = realpath($directory).'/';
			}

			// Only whitelist the "top" files in the cascading filesystem
			self::set_whitelist(Kohana::list_files('classes', $directories));
		}
	}

	/**
	 * Works out the whitelist from the config
	 * Used only on the CLI
	 *
	 * @returns array Array of directories to whitelist
	 */
	static protected function get_config_whitelist()
	{
		$config = Kohana::config('unittest');
		$directories = array();

		if ($config->whitelist['app'])
		{
			$directories['k_app'] = APPPATH;
		}

		if ($modules = $config->whitelist['modules'])
		{
			$k_modules = Kohana::modules();

			// Have to do this because kohana merges config...
			// If you want to include all modules & override defaults then TRUE must be the first
			// value in the modules array of your app/config/unittest file
			if (array_search(TRUE, $modules, TRUE) === (count($modules) - 1))
			{
				$modules = $k_modules;
			}
			elseif (array_search(FALSE, $modules, TRUE) === FALSE)
			{
				$modules = array_intersect_key($k_modules, array_combine($modules, $modules));
			}
			else
			{
				// modules are disabled
				$modules = array();
			}

			$directories += $modules;
		}

		if ($config->whitelist['system'])
		{
			$directories['k_sys'] = SYSPATH;
		}

		return $directories;
	}

	/**
	 * Recursively whitelists an array of files
	 *
	 * @param array $files Array of files to whitelist
	 */
	static protected function set_whitelist($files)
	{
		foreach ($files as $file)
		{
			if (is_array($file))
			{
				self::set_whitelist($file);
			}
			else
			{
				if ( ! isset(Kohana_Tests::$cache[$file]))
				{
					$relative_path = substr($file, strrpos($file, 'classes'.DIRECTORY_SEPARATOR) + 8, -strlen(EXT));
					$cascading_file = Kohana::find_file('classes', $relative_path);

					// The theory is that if this file is the highest one in the cascading filesystem
					// then it's safe to whitelist
					Kohana_Tests::$cache[$file] =  ($cascading_file === $file);
				}

				if (Kohana_Tests::$cache[$file])
				{
					PHPUnit_Util_Filter::addFileToWhitelist($file);
				}
			}
		}
	}

}
