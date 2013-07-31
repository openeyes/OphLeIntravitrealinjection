<?php
return array(
	'import' => array(
		'application.modules.OphLeIntravitrealinjection.*',
		'application.modules.OphLeIntravitrealinjection.components.*',
		'application.modules.OphLeIntravitrealinjection.models.*',
	),
	'components' => array(
		'event' => array(
			'observers' => array(
				'patient_after_find' => array(
					'import_injections' => array(
						'class' => 'OphLeIntravitrealinjection_EventHandler',
						'method' => 'patientFound',
					),
				),
			)
		)
	),
	'params' => array(
		//enable this param to display a URL for each legacy event that will take users to an external legacy application
		//'OphLeIntravitrealinjection_legacylink' => '',
	)
);