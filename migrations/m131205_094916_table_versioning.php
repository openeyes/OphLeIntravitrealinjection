<?php

class m131205_094916_table_versioning extends CDbMigration
{
	public function up()
	{
		$this->execute("
CREATE TABLE `et_ophleinjection_injection_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned DEFAULT NULL,
  `eye_id` int(10) unsigned NOT NULL DEFAULT '3',
  `left_drug_id` int(10) unsigned DEFAULT NULL,
  `right_drug_id` int(10) unsigned DEFAULT NULL,
  `left_number` int(10) unsigned DEFAULT NULL,
  `right_number` int(10) unsigned DEFAULT NULL,
  `archive_firm_id` int(10) unsigned DEFAULT NULL,
  `archive_hosnum` varchar(40) COLLATE utf8_bin DEFAULT NULL,
  `last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `last_modified_date` datetime NOT NULL DEFAULT '1901-01-01 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT '1901-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `acv_et_ophleinjection_injection_lmui_fk` (`last_modified_user_id`),
  KEY `acv_et_ophleinjection_injection_cui_fk` (`created_user_id`),
  KEY `acv_et_ophleinjection_injection_ev_fk` (`event_id`),
  KEY `acv_et_ophleinjection_injection_eye_id_fk` (`eye_id`),
  KEY `acv_et_ophleinjection_injection_left_drug_id_fk` (`left_drug_id`),
  KEY `acv_et_ophleinjection_injection_right_drug_id_fk` (`right_drug_id`),
  CONSTRAINT `acv_et_ophleinjection_injection_lmui_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_et_ophleinjection_injection_cui_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_et_ophleinjection_injection_ev_fk` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `acv_et_ophleinjection_injection_eye_id_fk` FOREIGN KEY (`eye_id`) REFERENCES `eye` (`id`),
  CONSTRAINT `acv_et_ophleinjection_injection_left_drug_id_fk` FOREIGN KEY (`left_drug_id`) REFERENCES `ophtrintravitinjection_treatment_drug` (`id`),
  CONSTRAINT `acv_et_ophleinjection_injection_right_drug_id_fk` FOREIGN KEY (`right_drug_id`) REFERENCES `ophtrintravitinjection_treatment_drug` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
		");

		$this->alterColumn('et_ophleinjection_injection_version','id','int(10) unsigned NOT NULL');
		$this->dropPrimaryKey('id','et_ophleinjection_injection_version');

		$this->createIndex('et_ophleinjection_injection_aid_fk','et_ophleinjection_injection_version','id');
		$this->addForeignKey('et_ophleinjection_injection_aid_fk','et_ophleinjection_injection_version','id','et_ophleinjection_injection','id');

		$this->addColumn('et_ophleinjection_injection_version','version_date',"datetime not null default '1900-01-01 00:00:00'");

		$this->addColumn('et_ophleinjection_injection_version','version_id','int(10) unsigned NOT NULL');
		$this->addPrimaryKey('version_id','et_ophleinjection_injection_version','version_id');
		$this->alterColumn('et_ophleinjection_injection_version','version_id','int(10) unsigned NOT NULL AUTO_INCREMENT');
	}

	public function down()
	{
		$this->dropTable('et_ophleinjection_injection_version');
	}
}
