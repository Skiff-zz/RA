<?php

class Model_Object extends Jelly_Model
{
	const OPERATION_CREATE 	= 0;
	const OPERATION_UPDATE 	= 1;
	const OPERATION_DELETE 	= 2;
	
	const STATUS_WAIT		= 0;
	const STATUS_COMMITTED	= 1;
	const STATUS_REJECTED	= 2;
	const STATUS_ERROR		= 3;
	 
	public static function initialize(Jelly_Meta $meta)
	{
		$meta->table('objects')
			->fields(array(
							// Первичный ключ
							'_id'			=> Jelly::field('Primary'),
							'deleted'		=> Jelly::field('Boolean', array('label' => 'Удалена')),
							'result'		=> Jelly::field('Integer', array('label' => 'Результат')),
							'object_id'		=> Jelly::field('String', array('label' => 'Внутренний идентификатор объекта в МС')),
							'operation'		=> Jelly::field('Integer', array('label' => 'Выполняемая операция')),
							
							'errors'		=> Jelly::field('Text', array('label' => 'Ошибки коммита')),
														
							'create_date'	=>  Jelly::field('Integer', array('label' => 'Дата создания',
								'rules' => array(
									'not_empty' => NULL
							))),
							
							'data'	=>  Jelly::field('Text', array('label' => 'Массив данных')),

							'type'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'type',
														'column'	=> 'type_id',
														'label'		=> 'Тип объекта',
													)),
							
							'version'		=> Jelly::field('BelongsTo',array(
														'foreign'	=> 'version',
														'column'	=> 'version_id',
														'label'		=> 'Версия',
													)),
							'update_date'	=>  Jelly::field('Integer', array('label' => 'Дата обновления',
								'rules' => array(
									'not_empty' => NULL
							))),
							
			));
	}
	
	public function commit($license, $save = true)
	{
			$data = @unserialize($this->data);
			
			if(is_array($data))
			{
				$data = Jelly::factory($this->type->model)->prepare($data, $this->object_id);
			} 
			
			switch($this->operation)
			{
				case self::OPERATION_CREATE:
					if(! Jelly::factory($this->type->model)->create($license, $data, $this, $save))
					{
						return false;
					}
				break;
				
				case self::OPERATION_UPDATE:
					if(! Jelly::factory($this->type->model)->update($license, $this->object_id, $data, $this, $save))
					{
						return false;
					}
				break;
				
				case self::OPERATION_DELETE:
					if(! Jelly::factory($this->type->model)->set_deleted($license, $this->object_id, $this, $save))
					{
						return false;
					}
				break;
			}
			
			if($save)
			{
				$this->result = self::STATUS_COMMITTED;
				$this->errors = null;
				$this->save();
			}
			
			return true;
	}
	
	public function error($status, $error_string, $errors = null)
	{
		if(!$this->loaded())
			return false;
		
		if(is_object($error_string) and $error_string instanceof Validate_Exception)
		{
			$this->errors = serialize(array('message' => $error_string->getMessage(), 'errors' => $error_string->array->errors('validate',true)));
		}
		else
		{
			if($errors)	
				$this->errors = serialize(array('message' => $error_string, 'errors' => $errors));
			else
				$this->errors = $error_string;
		}
			
		$this->result = $status;
		$this->save();
	}

	public function get_errors($version_id, $type){
	    $builder = Jelly::select('object')->where('version', '=', (int)$version_id)
					      ->and_where('result', '=', self::STATUS_ERROR)
					      ->and_where('deleted', '=', false)
					      ->and_where('type', '=', $type);
	    return $builder;
	}
}
?>
