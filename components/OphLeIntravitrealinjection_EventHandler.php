<?php

class OphLeIntravitrealinjection_EventHandler
{
	public function patientFound($params)
	{
		$patient = $params['patient'];

		$institution_id = Institution::model()->getCurrent()->id;
		$site_id = Yii::app()->session['selected_site_id'];
		$local_identifier_value = PatientIdentifierHelper::getIdentifierValue(PatientIdentifierHelper::getIdentifierForPatient(
                    'LOCAL',
                    $patient->id,
                    $institution_id, $site_id
                ));

		// check for any unattached legacy injection events for this patient, and set them up as appropriate
		if ($elements = Element_OphLeIntravitrealinjection_IntravitrealInjection::model()->findAll(
				array(
					'condition' => 'archive_hosnum = :hosnum', 
					'order' => 'created_date asc', 
					'params' => array(':hosnum' => $local_identifier_value)
				)
			) ) {
			foreach ($elements as $el) {
				$el->unArchive($patient);
			}
		}
	}
}