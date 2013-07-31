<?php

class DefaultController extends BaseEventTypeController
{
	public function actionCreate()
	{
		parent::actionCreate();
	}

	public function actionUpdate($id)
	{
		parent::actionUpdate($id);
	}

	public function actionView($id)
	{
		$this->editable = false;
		parent::actionView($id);
	}

	public function actionPrint($id)
	{
		parent::actionPrint($id);
	}
	
	public function generateLegacyUrl() 
	{
		$base_url = Yii::app()->params['OphLeIntravitrealinjection_legacylink'];
		return strtr($base_url, array('{patient.hos_num}' => $this->patient->hos_num));
	}
}
