<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Glossary_Target_FertilizerGroup extends Controller_Glossary_AbstractGroup
{

    protected $model_name = 'glossary_fertilizer_target';
	protected $model_group_name = 'glossary_fertilizergroup';
    protected $items_field = 'target_fertilizers';
}

