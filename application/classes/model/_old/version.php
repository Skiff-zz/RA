<?php
class Model_Version extends Jelly_Model
{
	const STATUS_WAIT 		= 0;
	const STATUS_SUCCESS 	= 1;
	const STATUS_FAIL	 	= 2;
		
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('versions')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'create_date'	=>  Jelly::field('Integer', array('label' => 'Дата создания',
								'rules' => array(
									'not_empty' => NULL
							))),
							
							'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата обновления',
								'rules' => array(
									'not_empty' => NULL
							))),
							
							'deleted'		=> Jelly::field('Boolean', array('label' => 'Удалена')),
							'result'		=> Jelly::field('Integer', array('label' => 'Результат')),
							
							'farm'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'farm_id',
														'label'		=> 'Род. лицензиат',
													)),
							'station'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'station',
														'column'	=> 'station_id',
														'label'		=> 'Станция',
													)),						
							'manager'	=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'user',
														'column'	=> 'manager_id',
														'label'		=> 'Менеджер',
													)),
							'user'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'subuser',
														'column'	=> 'user_id',
														'label'		=> 'Пользователь',
													)),
							'objects'		=> Jelly::field('HasMany',array(
														'foreign'	=> 'object',
														'column'	=> 'version_id',
														'label'		=> 'Объекты в правке',
													)),
			));
	}
	
	public function commit()
	{
		if(!$this->loaded())
			return false;
		
		// Сортируем, что бы создание было раньше изменения
		
		$objects = Jelly::select('object')->
						with('type')->
						where('deleted', '=', 0)->
						where('result', '=', Model_Object::STATUS_WAIT)->
						order_by('operation', 'ASC')->
						where('version', '=', $this->id())
					->execute();
		
		// Сначала проверяем все, потом сохраняем
		foreach($objects as $object)
		{
			if(!$object->commit($this->farm, false))
			{
				$this->result = self::STATUS_FAIL;
				$this->save();
				return false;
			}	
		}
		
		// Вся валидация пройдена, можно сохранять
		@reset($objects);
		
		foreach($objects as $object)
		{
			if(!$object->commit($this->farm))
			{
				$this->result = self::STATUS_FAIL;
				$this->save();
				return false;
			}	
		}
		
		$this->result = self::STATUS_SUCCESS;
		$this->save();
		
		return true;
	}
	
}
?>
