<?php 

class m130913_000011_consolidation extends OEMigration
{

	public function up()
	{
		if (!$this->consolidate(
			array(
				'm130730_150253_event_type_OphLeIntravitrealinjection'
			)
		)
		) {
			return $this->createTables();
		}
	}

	public function down()
	{
		echo "You cannot migrate down past a consolidation migration\n";
		return false;
	}

	public function safeUp()
	{
		$this->up();
	}

	public function safeDown()
	{
		$this->down();
	}

	protected function createTables()
	{
		if (!Yii::app()->hasModule('OphTrIntravitrealinjection')) {
			echo "
			-----------------------------------
			Skipping OphTrIntravitrealinjection - missing module dependency
			-----------------------------------
			";
			return false;
			//throw new Exception("OphTrIntravitrealinjection is required for this module to work");
		}

		if (!in_array('ophtrintravitinjection_treatment_drug',Yii::app()->db->getSchema()->tableNames)) {
			echo "
			-----------------------------------
			Skipping OphTrIntravitrealinjection - missing module table ophtrintravitinjection_treatment_drug dependency
			-----------------------------------
			";
			return false;
			//throw new Exception("OphTrIntravitrealinjection is required for this module to work");
		}

		// --- EVENT TYPE ENTRIES ---

		// create an event_type entry for this event type name if one doesn't already exist
		if (!$this->dbConnection->createCommand()->select('id')->from('event_type')->where('class_name=:class_name', array(':class_name'=>'OphLeIntravitrealinjection'))->queryRow()) {
			$group = $this->dbConnection->createCommand()->select('id')->from('event_group')->where('name=:name',array(':name'=>'Legacy data'))->queryRow();
			$this->insert('event_type', array('class_name' => 'OphLeIntravitrealinjection', 'name' => 'Legacy Intravitreal injection','event_group_id' => $group['id']));
		}
		// select the event_type id for this event type name
		$event_type = $this->dbConnection->createCommand()->select('id')->from('event_type')->where('class_name=:class_name', array(':class_name'=>'OphLeIntravitrealinjection'))->queryRow();

		// --- ELEMENT TYPE ENTRIES ---

		// create an element_type entry for this element type name if one doesn't already exist
		if (!$this->dbConnection->createCommand()->select('id')->from('element_type')->where('name=:name and event_type_id=:eventTypeId', array(':name'=>'Intravitreal Injection',':eventTypeId'=>$event_type['id']))->queryRow()) {
			$this->insert('element_type', array('name' => 'Intravitreal Injection','class_name' => 'Element_OphLeIntravitrealinjection_IntravitrealInjection', 'event_type_id' => $event_type['id'], 'display_order' => 1));
		}
		// select the element_type_id for this element type name
		$element_type = $this->dbConnection->createCommand()->select('id')->from('element_type')->where('event_type_id=:eventTypeId and name=:name', array(':eventTypeId'=>$event_type['id'],':name'=>'Intravitreal Injection'))->queryRow();

		// create the table for this element type: et_modulename_elementtypename
		$this->createTable('et_ophleinjection_injection', array(
				'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
				'event_id' => 'int(10) unsigned',
				'eye_id' => 'int(10) unsigned NOT NULL DEFAULT 3', // Eye
				'left_drug_id' => 'int(10) unsigned', // Drug
				'right_drug_id' => 'int(10) unsigned', // Drug
				'left_number' => 'int(10) unsigned', // Number of Injections
				'right_number' => 'int(10) unsigned', // Number of Injections
				'archive_firm_id' => 'int(10) unsigned',
				'archive_hosnum' => 'varchar(40)',
				'last_modified_user_id' => 'int(10) unsigned NOT NULL DEFAULT 1',
				'last_modified_date' => 'datetime NOT NULL DEFAULT \'1901-01-01 00:00:00\'',
				'created_user_id' => 'int(10) unsigned NOT NULL DEFAULT 1',
				'created_date' => 'datetime NOT NULL DEFAULT \'1901-01-01 00:00:00\'',
				'PRIMARY KEY (`id`)',
				'KEY `et_ophleinjection_injection_lmui_fk` (`last_modified_user_id`)',
				'KEY `et_ophleinjection_injection_cui_fk` (`created_user_id`)',
				'KEY `et_ophleinjection_injection_ev_fk` (`event_id`)',
				'KEY `et_ophleinjection_injection_eye_id_fk` (`eye_id`)',
				'KEY `et_ophleinjection_injection_left_drug_id_fk` (`left_drug_id`)',
				'KEY `et_ophleinjection_injection_right_drug_id_fk` (`right_drug_id`)',
				'CONSTRAINT `et_ophleinjection_injection_lmui_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`)',
				'CONSTRAINT `et_ophleinjection_injection_cui_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`)',
				'CONSTRAINT `et_ophleinjection_injection_ev_fk` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`)',
				'CONSTRAINT `et_ophleinjection_injection_eye_id_fk` FOREIGN KEY (`eye_id`) REFERENCES `eye` (`id`)',
				'CONSTRAINT `et_ophleinjection_injection_left_drug_id_fk` FOREIGN KEY (`left_drug_id`) REFERENCES `ophtrintravitinjection_treatment_drug` (`id`)',
				'CONSTRAINT `et_ophleinjection_injection_right_drug_id_fk` FOREIGN KEY (`right_drug_id`) REFERENCES `ophtrintravitinjection_treatment_drug` (`id`)',
			), 'ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin');

	}

}

