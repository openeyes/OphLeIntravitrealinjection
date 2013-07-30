<?php

class OphLeIntravitrealinjection_EventHandler
{
	public function patientFound($params)
	{
		$patient = $params['patient'];
		// check for any unattached legacy injection events for this patient, and set them up as appropriate
		if ($elements = Element_OphLeIntravitrealinjection_IntravitrealInjection::model()->findAll('archive_hosnum = ?', array($patient->hos_num))) {
			foreach ($elements as $el) {
				$el->unArchive($patient);
			}
		}
	}
}