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
		)
	);