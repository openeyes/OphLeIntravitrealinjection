<?php
return array(
	'import' => array(
			'application.modules.ophleintravitrealinjection.*',
			'application.modules.ophleintravitrealinjection.components.*',
			'application.modules.ophleintravitrealinjection.models.*',
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
		)
	);