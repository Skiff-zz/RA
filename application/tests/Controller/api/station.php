<?php defined('SYSPATH') or die('No direct script access.');  

/**
 * @group stationApi
 */
class StationApiTest extends Kohana_Unittest_Testcase
{
	/**
	 * @group stationApi.check
	 */
	public function test_check()
	{
		// Пустой запрос
		$_REQUEST = array();
		$request  = AC_API::get_request('/api/station/check/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY');

		// Станция заблокирована
		$_REQUEST = array('id' => 'phpunit_test_3');
		$request  = AC_API::get_request('/api/station/check/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$this->assertEquals('{"name":"'.$station->name.'","result":true}', $request, 'SUCCESS');

		// Станция не заблокирована
		$_REQUEST = array('id' => 'phpunit_test_2');
		$request  = AC_API::get_request('/api/station/check/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_2')->load();
		$att      = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_2')->load();
		$attempts = $att->loaded() ? Kohana::config('application.blacklist_attempts')-$att->attempts : Kohana::config('application.blacklist_attempts');
		$this->assertEquals('{"attempt":'.$attempts.',"name":"'.$station->name.'","result":false}', $request, 'FALSE');
	}



	/**
	 * @group stationApi.activate
	 */
	public function test_activate()
	{
		// Пустой запрос
		$_REQUEST = array();
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY');

		// Несуществующий ид мс
		$_REQUEST = array('id' => md5(mt_rand(0, time())), 'code' => '1234567890');
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY');

		// Станция не заблокирована
		$_REQUEST = array('id' => 'phpunit_test_1', 'code' => '1234567890');
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_IS_NOT_BLACKLISTED.'}', $request, 'MOBILE_STATION_IS_NOT_BLACKLISTED');

		// Пустой код активации
		$_REQUEST = array('id' => 'phpunit_test_3');
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::ACTIVATION_CODE_IS_EMPTY.'}', $request, 'ACTIVATION_CODE_IS_EMPTY');
		$this->assertTrue($this->check_last_log(117, 'Попытка активации с пустым кодом'), 'LOG_MISMATCH_emptyCode');

		//ставим попытки в 1 чтоб сделать 2 неудачных запроса
		$bl = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$bl->attempts = 1;
		$bl->save();

		// Неверный код активации
		$_REQUEST = array('id' => 'phpunit_test_3', 'code' => '1234567890');
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::ACTIVATION_CODE_IS_INCORRECT.'}', $request, 'ACTIVATION_CODE_IS_INCORRECT');
		$this->assertTrue($this->check_last_log(117, 'Попытка активации, код: '.UTF8::trim('1234567890')), 'LOG_MISMATCH_wrongCode');
		$blacklist = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$this->assertEquals($blacklist->attempts, 2, 'ATTEMPTS_COUNT');

		// Неверный код активации + закончились попытки
		$_REQUEST = array('id' => 'phpunit_test_3', 'code' => '1234567890');
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::ACTIVATION_CODE_IS_INCORRECT.'}', $request, 'ACTIVATION_CODE_IS_INCORRECT');
		$this->assertTrue($this->check_last_log(117, 'Сгенерирован новый код активации:'), 'LOG_MISMATCH_newCode');
		$this->assertTrue($this->check_last_log(117, 'Попытка активации, код: '.UTF8::trim('1234567890')), 'LOG_MISMATCH_wrongCode2');
		$blacklist = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$this->assertEquals($blacklist->attempts, 0, 'ATTEMPTS_COUNT');

		// Правильный код активации
		$_REQUEST = array('id' => 'phpunit_test_3', 'code' => $blacklist->unblock_code);
		$request  = AC_API::get_request('/api/station/activate/');
		$this->assertEquals('{"attempt":'.Kohana::config('application.blacklist_attempts').',"result":true}', $request, 'SUCCESS');
		$this->assertTrue($this->check_last_log(117, 'Станция активирована'), 'LOG_MISMATCH_stantionActivated');

		$blacklist = Jelly::select('blacklist')->with('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$this->assertTrue(!$blacklist->loaded(), 'STATION_STILL_IN_BLACKLIST');
		$attempts = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_3')->load();
		$this->assertTrue(!$attempts->loaded(), 'STATION_STILL_IN_ATTEMPTS_LIST');

		//возвращаем станцию назад в блэклист
		$this->addStationToBlacklist('phpunit_test_3');
	}



	/**
	 * @group stationApi.autologin
	 */
	public function test_autologin()
	{
		// Пустой запрос
		$_REQUEST = array();
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY_noId');

		// Несуществующий ид мс
		$_REQUEST = array('id' => md5(mt_rand(0, time())), 'license' => '667883300');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY_badId');

		// Нет лицензии
		$_REQUEST = array('id' => 'phpunit_test_1');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_NUMBER_IS_EMPTY.'}', $request, 'LICENSE_NUMBER_IS_EMPTY');

		// Станция в блэклисте
		$_REQUEST = array('id' => 'phpunit_test_3', 'license' => '667883300');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED');

		// несуществующая лицензия
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '1989');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_NUMBER_IS_INVALID.'}', $request, 'LICENSE_NUMBER_IS_INVALID');

		// Лицензия существует но юзер удален
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '184619328');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_NUMBER_IS_INVALID.'}', $request, 'LICENSE_NUMBER_IS_INVALID_deleted');

		// Лицензия неактивна(авто)
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '161029052');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_IS_BLOCKED.'}', $request, 'LICENSE_IS_BLOCKED_auto');

		// Лицензия неактивна(выключена вручную)
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '154382324');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_IS_BLOCKED.'}', $request, 'LICENSE_IS_BLOCKED_manual');

		// MC не зарегистрирована
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '802296524');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_IS_NOT_REGISTERED.'}', $request, 'MOBILE_STATION_IS_NOT_REGISTERED');

		// Успешный автологин
		$_REQUEST = array('id' => 'phpunit_test_2', 'license' => '667883300');
		$request  = AC_API::get_request('/api/station/autologin/');
		$this->assertEquals('{"result":true}', $request, 'SUCCESS');
	}



	/**
	 * @group stationApi.register
	 */
	public function test_register()
	{
		// Пустой запрос
		$_REQUEST = array();
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::HARDWARE_ID_IS_EMPTY.'}', $request, 'HARDWARE_ID_IS_EMPTY_noId');

		// Станция в блэклисте
		$_REQUEST = array('id' => 'phpunit_test_3', 'license' => '667883300');
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED');

		$this->removeStationFromBlacklist('phpunit_test_1');

		// Пустой ключ лицензии (Попытка #1)
		$_REQUEST = array('id' => 'phpunit_test_1');
		$request  = AC_API::get_request('/api/station/register/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$att      = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$attempts = $att->loaded() ? Kohana::config('application.blacklist_attempts')-$att->attempts : Kohana::config('application.blacklist_attempts');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_NUMBER_IS_EMPTY.',"attempt":'.$attempts.',"name":"'.$station->name.'"}', $request, 'LICENSE_NUMBER_IS_EMPTY');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense');
		
		//Попытка #2
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense_1');

		//Попытка #3 и заносится в блэклист
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED-2');
		$this->assertTrue($this->check_last_log(115, 'Станция занесена в черный список'), 'LOG_MISMATCH_blacklist');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense_a');
		$bl = Jelly::select('blacklist')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$this->assertEquals($bl->hardware_id, 'phpunit_test_1', 'NOT_IN_BL');

		$this->removeStationFromBlacklist('phpunit_test_1');

		// Такой лицензии нет (Попытка #1)
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '1989');
		$request  = AC_API::get_request('/api/station/register/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$att      = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$attempts = $att->loaded() ? Kohana::config('application.blacklist_attempts')-$att->attempts : Kohana::config('application.blacklist_attempts');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_NUMBER_IS_INVALID.',"attempt":'.$attempts.',"name":"'.$station->name.'"}', $request, 'LICENSE_NUMBER_IS_INVALID');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense2');

		//Попытка #2
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense_2');

		//Попытка #3 и заносится в блэклист
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED-3');
		$this->assertTrue($this->check_last_log(115, 'Станция занесена в черный список'), 'LOG_MISMATCH_blacklist2');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense2_a');
		$bl = Jelly::select('blacklist')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$this->assertEquals($bl->hardware_id, 'phpunit_test_1', 'NOT_IN_BL');

		$this->removeStationFromBlacklist('phpunit_test_1');

		// Лицензия заблокирована (Попытка #1)
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '161029052');
		$request  = AC_API::get_request('/api/station/register/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$att      = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$attempts = $att->loaded() ? Kohana::config('application.blacklist_attempts')-$att->attempts : Kohana::config('application.blacklist_attempts');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::LICENSE_IS_BLOCKED.',"attempt":'.$attempts.',"name":"'.$station->name.'"}', $request, 'LICENSE_IS_BLOCKED');
		$this->assertTrue($this->check_last_log(115, 'Лицензия заблокирована'), 'LOG_MISMATCH_wrongLicense3');

		//Попытка #2
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertTrue($this->check_last_log(115, 'Лицензия заблокирована'), 'LOG_MISMATCH_wrongLicense_3');

		//Попытка #3 и заносится в блэклист
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED-4');
		$this->assertTrue($this->check_last_log(115, 'Станция занесена в черный список'), 'LOG_MISMATCH_blacklist3');
		$this->assertTrue($this->check_last_log(115, 'Лицензия заблокирована'), 'LOG_MISMATCH_wrongLicense_3');
		$bl = Jelly::select('blacklist')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$this->assertEquals($bl->hardware_id, 'phpunit_test_1', 'NOT_IN_BL');

		$this->removeStationFromBlacklist('phpunit_test_1');

		// Нет свободных слотов (Попытка #1)
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '802296524');
		$request  = AC_API::get_request('/api/station/register/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$att      = Jelly::select('attempts')->with('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$attempts = $att->loaded() ? Kohana::config('application.blacklist_attempts')-$att->attempts : Kohana::config('application.blacklist_attempts');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::NO_FREE_SLOTS.',"attempt":'.$attempts.',"name":"'.$station->name.'"}', $request, 'NO_FREE_SLOTS');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense4');

		//Попытка #2
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense_4');

		//Попытка #3 и заносится в блэклист
		$request  = AC_API::get_request('/api/station/register/');
		$this->assertEquals('{"result":false,"error":'.Controller_API_Station::MOBILE_STATION_BLACKLISTED.'}', $request, 'MOBILE_STATION_BLACKLISTED-6');
		$this->assertTrue($this->check_last_log(115, 'Станция занесена в черный список'), 'LOG_MISMATCH_blacklist4');
		$this->assertTrue($this->check_last_log(115, 'Неверный ключ лицензии'), 'LOG_MISMATCH_wrongLicense4_a');
		$bl = Jelly::select('blacklist')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$this->assertEquals($bl->hardware_id, 'phpunit_test_1', 'NOT_IN_BL');

		$this->removeStationFromBlacklist('phpunit_test_1');

		//удачная регистрация
		$_REQUEST = array('id' => 'phpunit_test_1', 'license' => '667883300');
		$request  = AC_API::get_request('/api/station/register/');
		$station  = Jelly::select('station')->where('hardware_id', '=', 'phpunit_test_1')->load();
		$this->assertEquals('{"result":true,"name":"'.$station->name.'"}', $request, 'SUCCESS');
		$this->assertTrue($this->check_last_log(115, 'МС зарегистрирована'), 'LOG_MISMATCH_reg_success');

		//отсоединили от лицензиата
		$user = Jelly::select('user')->where('number', '=', '667883300')->where('deleted', '=', 0)->limit(1)->execute();
		$user->remove('stations', 115);
		$user->save();
	}




	
	//ФУНКЦИИ - ПОМОШНИЧКИ
    private function check_last_log($station_id, $message) {
		$stats = Jelly::select('stat')->where('station_id', '=', $station_id)
									  ->and_where('message', 'LIKE', $message.'%')
									  //->and_where('date', '>', date("Y-m-d H:i:s", strtotime("-1 seconds")))
									  ->limit(1)->execute();
		if($res = $stats->loaded()){
			$stats->delete();
		}
		return $res;
	}

	private function addStationToBlacklist($hardware_id) {
		$blacklist			= Jelly::factory('blacklist');
		$blacklist->station = Jelly::select('station')->where('hardware_id', '=', $hardware_id)->load();
		$blacklist->hardware_id 	= $hardware_id;
		$blacklist->create_date 	= time();
		$blacklist->attempts	 	= 0;
		$blacklist->unblock_code 	= mt_rand(100000000, 999999999);
		Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();
		$blacklist->save();
	}

	private function removeStationFromBlacklist($hardware_id) {
		Jelly::delete('attempts')->where('hardware_id', '=', $hardware_id)->execute();
		Jelly::delete('blacklist')->where('hardware_id', '=', $hardware_id)->execute();
	}
}

