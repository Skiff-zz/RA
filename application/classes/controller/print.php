<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Print extends AC_Controller{
	
	public $auto_render  = false;
	
	public function action_field($id){
		$view = Twig::factory('print/field');

		$field = Jelly::select('field')->with('culture')->with('culture_before')->with('farm')->with('seed')->with('notes')->with('works')->with('acidity')->load($id);
		$shares = Jelly::select('client_fieldshare')->where('field', '=', (int)$id)->execute()->as_array();
		
		$notes = array();
		foreach($field->notes as $note) {
			$notes[] = array(
				'create_date' => date("d.m.Y", (int)$note->create_date),
				'execute_date' => date("d.m.Y", (int)$note->execute_date),
				'txt' => $note->txt,
				'executed' => $note->executed
			);
		}
		
		$works = array();
		$area = $field->area;
		$totals = 0;
		foreach($field->works as $work) {
			$percent = (float)$area!=0 ? (((float)$work->processed)/((float)$area))*100 : 0;
			$totals += (float)$work->inputs;
			$works[] = array(
				'work_number' => $work->work_number,
				'work_color' => $work->work_color,
				'work_date' => date("d.m.Y", (int)$work->work_date),
				'operation' => $work->operation,
				'processed' => $work->processed.'га ('.$percent.'%)',
				'technic_mobile' => $work->technic_mobile,
				'technic_trailer' => $work->technic_trailer,
				'personals' => $work->personals,
				'inputs' => number_format((float)$work->inputs, 2, '.', ' ')
			);
		}
		
		$view->field = $field;
		$view->notes = $notes;
		$view->works = $works;
		$view->works_inputs_total = number_format($totals, 2, '.', ' ');
		$view->shares = $shares;
		$view->set('google_api_key', Kohana::config('application.google_api_key'));
		$this->request->response = $view->render();
	}
	
	
	public function action_fieldwork(){
		$view = Twig::factory('print/fieldwork');
		
		$user = Auth::instance()->get_user();
		$data = Jelly::factory('field')->get_work_grid_data($user->license->id());

		$view->data = $data;
		$this->request->response = $view->render();
	}
	
	
}