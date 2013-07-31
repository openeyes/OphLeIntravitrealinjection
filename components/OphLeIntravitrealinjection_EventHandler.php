<?php

class OphLeIntravitrealinjection_EventHandler
{
	public function patientFound($params)
	{
		$patient = $params['patient'];
		// check for any unattached legacy injection events for this patient, and set them up as appropriate
		if ($elements = Element_OphLeIntravitrealinjection_IntravitrealInjection::model()->findAll(
				array(
					'condition' => 'archive_hosnum = :hosnum', 
					'order' => 'created_date asc', 
					'params' => array(':hosnum' => $patient->hos_num)
				)
			) ) {
			foreach ($elements as $el) {
				$el->unArchive($patient);
			}
		}
	}
}