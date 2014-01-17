<?php

class DefaultController extends BaseEventTypeController
{
	public function initActionView()
	{
		parent::initActionView();
		$this->editable = false;
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
