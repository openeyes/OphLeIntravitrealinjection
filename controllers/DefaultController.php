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

	public function canDelete()
	{
		return false;
	}
	
	/**
	 * if a legacy link is configured in the app (OphLeIntravitrealinjection_legacylink)
	 * will generate a link for the patient being viewed and return it. Currently supports the following substitutions:
	 * 		- {patient.hos_num}
	 * 
	 * @return string $url
	 */
	public function generateLegacyUrl() 
	{
		if (isset(Yii::app()->params['OphLeIntravitrealinjection_legacylink'])) {
			$base_url = Yii::app()->params['OphLeIntravitrealinjection_legacylink'];
			return strtr($base_url, array('{patient.hos_num}' => $this->patient->hos_num));
		}
		
	}
}
