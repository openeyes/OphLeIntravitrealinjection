<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */


class ImportLeIntravitrealInjectionCommand extends RelatedImportComplexCommand 
{
	protected $DATA_FOLDER = 'data/import/legacyinjections';
	const NOPTNT = 100;
	// flag to prevent PAS import
	const NOPAS = true;
	const EP_HOS_NUM_COLNAME = '[patient_id=patient.hos_num]';
	
	// default firm id
	protected $default_firm_id = 110;
	// list of subspecialty ids in order of precedence that we want to use for firms
	protected $ordered_subspecialty_ids = array(8, 15);
	
	public function getName()
	{
		return 'Import Legacy Intravitreal Injection Command.';
	}
	
	protected $firm_subspecialty_map = array();
	protected $subspecialty_firms_map = array();
	protected $event_type_id;
	protected $drug_names_by_id = array();
	
	public function getHelp()
	{
		return <<<EOH
Imports legacy intravitreal injection events. Extends the relatedimportcomplex command, and is expecting three data files:
	episode
	event
	et_ophleintravitrealinjection_injection
	
	along with the cpxmap file as standard for this import method.
	
	Has various functions based on certain expectations. It is set to not use the PAS when importing, so that legacy injection events
	are only created for patients that are already in the OE database. Patients that have not yet been brought in from PAS will be stored
	as unattached injection elements, which will then be associated with a patient through the patient_after_find event.
				
	Will accept a numeric argument which is the limit of the number of hos nums that will be output at the end of the process. These hosnums are
	for patients that have archive records imported (i.e. the patients don't yet exist in the db). This is primarily for testing purposes.

EOH;
	}
	
	/*
	 * extends the relative import complex command to perform some custom import behaviour for legacy injections
	 * Once the full requirements for this were resolved it would probably have been better to write an import
	 * command from scratch, but we'd committed to the format so the additional functionality has been wrapped into 
	 * the code. 
	 */
	public function run($args)
	{
		// get the event type for the module
		$event_type = EventType::model()->find('class_name = ?', array('OphLeIntravitrealinjection'));

		$this->event_type_id = $event_type->id;	
		parent::run($args);
		echo count(array_keys($this->patient_archive_episode)) . " archive episodes created\n";
		if (count($args) && $sample_limit = (int)$args[0]) {
			$counter = 0;
			echo "Sample hos nums:\n";
			foreach (array_keys($this->patient_archive_episode) as $key) {
				echo $this->patient_archive_episode[$key]['hosnum'] . "\n";
				if ($counter++ > $sample_limit) {
					break;
				}
			}
		}
	}
	
	/**
	 * simple lookup function to get drug names from the actual injection event
	 * this should be abstracted at some point so that these things are mapped, rather than 
	 * having an actual cross table dependency
	 * 
	 * @param integer $id
	 * @return string
	 */
	protected function getDrugNameForId($id)
	{
		if (!count($this->drug_names_by_id)) {
			foreach (OphTrIntravitrealinjection_Treatment_Drug::model()->findAll() as $drug) {
				$this->drug_names_by_id[$drug->id] = $drug->name;
			}
		}
		return $this->$drug_names_by_id[$id];
	}
	
	/**
	 * determines the best firm id to use out of a selection. If none of the firms have the appropriate subspecialty
	 * we will use the default firm
	 * 
	 * @param integer[] $firm_ids
	 * @return integer
	 */
	protected function getFirmIdWithBestSubspecialty($firm_ids) {
		foreach ($this->ordered_subspecialty_ids as $sid) {
			foreach ($firm_ids as $fid) {
				if ($this->getFirmSubspecialtyId($fid) == $sid) {
					return $fid;
				}
			}
		}
		echo "WARN: no appropriate firm in firm id list [" . implode(', ', $firm_ids) . "] using default firm\n";
		return $this->default_firm_id;
	}
	
	/**
	 * map of subspecialty id for a firm
	 * TODO: prioritise the subspecialties that we map to, so that it pertains more specifically to the likely
	 * subspecialty for an injection event.
	 * 
	 * @param unknown $firm_id
	 * @return multitype:
	 */
	protected function getFirmSubspecialtyId($firm_id)
	{
		$db = Yii::app()->db;
		if (!isset($this->firm_subspecialty_map[$firm_id])) {
			$query = "SELECT sa.subspecialty_id FROM  service_subspecialty_assignment sa, firm f WHERE sa.id = f.service_subspecialty_assignment_id AND f.id = " . $db->quoteValue($firm_id);
			$res =  $db->createCommand($query)->query();
			$this->firm_subspecialty_map[$firm_id] = array();
			foreach ($res as $row) {
				$this->firm_subspecialty_map[$firm_id][] = $row['subspecialty_id'];
			}
		}
		return $this->firm_subspecialty_map[$firm_id][0];
	}
	
	/**
	 * helper function to get all the subspecialties relevant for a firm
	 * 
	 * @param integer $subspecialty_id
	 * @return integer[] firm_ids
	 */
	protected function getSubspecialtyFirmIds($subspecialty_id)
	{
		if (!isset($this->subspecialty_firms_map[$subspecialty_id])) {
			$lst = Firm::model()->getList($subspecialty_id);
			$this->subspecialty_firms_map[$subspecialty_id] = array();
			foreach ($lst as $id => $name) {
				$this->subspecialty_firms_map[$subspecialty_id][] = $id;
			}
		}
		return $this->subspecialty_firms_map[$subspecialty_id];
	}
	
	/**
	 * retrieve an open episode for a patient with the given subspecialty
	 * @param unknown $patient_id
	 * @param unknown $subspecialty_id
	 * @return integer $episode_id or null
	 */
	protected function getOpenEpisodeForPatient($patient_id, $subspecialty_id)
	{
		$id = null;
		$db = Yii::app()->db;
		$firm_ids = $this->getSubspecialtyFirmIds($subspecialty_id);
		$quoted_ids = array();
		foreach ($firm_ids as $fid) {
			$quoted_ids[] = $db->quoteValue($fid);
		}
		$query = "SELECT id FROM episode where patient_id = " . $db->quoteValue($patient_id) . " AND end_date is NULL and firm_id in (" . implode(',', $quoted_ids) . ")";
		$res =  $db->createCommand($query)->query();
		foreach ($res as $row) {
			// we'll grab the last if there are multiple.
			$id = $row['id'];
		}
		return $id;
	}
	
	protected $event_idx = null;
	protected $left_drug_idx = null;
	protected $right_drug_idx = null;
	/**
	 * get the left and right drug column values from the data
	 *
	 * @return array(event_id, left_drug_id, right_drug_id) 
	 */
	protected function getDataForEventInfo($insert_cols, $data)
	{
		if ($this->event_idx === null) {
			foreach ($insert_cols as $i => $colname) {
				if ($colname == 'event_id') {
					$this->event_idx = $i;
				}
				if (preg_match('/^(left|right)_drug_id$/',$colname, $matches)) {
					$this->{$matches[1] . '_drug_idx'} = $i;
				}
			}
		}
		
		return array($data[$this->event_idx], $data[$this->left_drug_idx], $data[$this->right_drug_idx]);
	}
	
	protected $drug_lkup = array();
	/**
	 * get the drug name for the given id
	 * @param integer $drug_id
	 * @return string $drug_name
	 */
	protected function getDrugName($drug_id)
	{
		if (!count($this->drug_lkup)) {
			foreach (OphTrIntravitrealinjection_Treatment_Drug::model()->findAll() as $drug) {
				$this->drug_lkup[$drug->id] = $drug->name;
			}
		}
		return $this->drug_lkup[$drug_id];
	}
	
	/**
	 * Update event information
	 * 
	 * @param integer $event_id
	 * @param string $info
	 */
	protected function updateEventInfo($event_id, $info) 
	{
		$db = Yii::app()->db;
		$query = "UPDATE event SET info = " . $db->quoteValue($info) . " WHERE id = " . $db->quoteValue($event_id);
		$db->createCommand($query)->execute();
	}
	
	protected $raw_cols_map = array();
	/**
	 * raw data column lookup function
	 * 
	 * @param string[] $raw_columns
	 * @param string $table
	 * @param string $col
	 */
	protected function getRawColPos($raw_columns, $table, $col) {
		if (!@$this->raw_cols_map[$table]) {
			$this->raw_cols_map[$table] = array();
			foreach ($raw_columns as $i => $raw) {
				$this->raw_cols_map[$table][$raw] = $i;
			}
		}
		return $this->raw_cols_map[$table][$col];
	}
	
	
	protected $episode_archive_record = false;
	/**
	 * (non-PHPdoc)
	 * @see RelatedImportComplexCommand::insert()
	 */
	protected function insert($table, $raw_columns, $raw_data)
	{
		try {
			if ($table == 'episode') {
				// need to do some custom work here
				$db = Yii::app()->db;
				$this->episode_archive_record = false;
				// a quick hack to ensure that the hosnum is appropriately padded
				
				$hosnum = $raw_data[$this->getRawColPos($raw_columns, $table, self::EP_HOS_NUM_COLNAME)];
				$raw_data[$this->getRawColPos($raw_columns, $table, self::EP_HOS_NUM_COLNAME)] = sprintf('%07s', $hosnum); 
				
				list($imp_id, $insert_cols, $data) = $this->handleRawData($raw_columns, $raw_data);
				$col_pos = array(
					'patient_id' => null,
					'firm_id' => null
				);
				
				// get the hos num from the raw data for the patient id col
				// that should be used to have an index store for unavailable patients
				// then the import id should map to hash array that contains the data we need
				// to store as legacy information
				
				// when we get the event data, we check this structure on the imp id
				// if it's there, we then do a similar event based imp id index with the data
				
				// then for actual injection record, check for event based imp id
				// and mung all the information in if its there (otherwise carry on as before)
				
				// then update the signal handler to pull this data out when the patient has been found
				
				foreach ($insert_cols as $i => $col) {
					if (array_key_exists($col, $col_pos)) {
						$col_pos[$col] = $i;
					}
				}
				foreach ($col_pos as $required => $pos) {
					if (is_null($pos)) {
						throw new Exception('missing required column in episode map ' . $required);
					}
				}
				
				if ($this->episode_archive_record) {
					// this record has been marked as an archive import
					$hosnum = $raw_data[$this->getRawColPos($raw_columns, $table, self::EP_HOS_NUM_COLNAME)];
					// note this import will not work without imp_id - but work to handle this case is 
					// unnecessary for our current purposes.
					if ($imp_id) {					
						$this->patient_archive_episode['a' . $imp_id] = array(
								'hosnum' => $hosnum,
								'firm_id' => $data[$col_pos['firm_id']],
								);
						// prefix with a so that we can't possibly clash with a genuine mapping
						$this->imp_id_map[$table][$imp_id] = 'a' . $imp_id;
					}
				}
				else {
					// get the subspecialty_id
					$subspecialty_id = $this->getFirmSubspecialtyId($data[$col_pos['firm_id']]);
					
					// look to see if there is already an open episode for the patient in the subspecialty, and map the import id to that if so
					$episode_id = $this->getOpenEpisodeForPatient($data[$col_pos['patient_id']], $subspecialty_id);
					
					if ($episode_id) {
						if ($imp_id) {
							$this->imp_id_map[$table][$imp_id] = $episode_id;
						}
					}
					else {
						$this->processHandledData($table, $insert_cols, $data, $imp_id);
					}
				}
			}
			else {
				if ($table == 'event') {
					// ensure we have an event type
					if (!array_key_exists('event_type_id', $raw_columns)) {
						$raw_columns[] = 'event_type_id';
						$raw_data[] = $this->event_type_id;
					}
					list($imp_id, $insert_cols, $data) = $this->handleRawData($raw_columns, $raw_data);
					
					// get the episode id
					foreach ($insert_cols as $i => $col) {
						if ($col == 'episode_id') {
							$episode_imp_id = $data[$i];
							break;
						}
					}
					
					if (array_key_exists($episode_imp_id, $this->patient_archive_episode)) {
						// we are not storing this against a live patient
						$this->patient_archive_event['a' . $imp_id] = array(
							'episode_imp_id' => $episode_imp_id,		
						);
						// prefix to guarantee uniqueness
						$this->imp_id_map[$table][$imp_id] = 'a' . $imp_id;
					}
					else {
						$this->processHandledData($table, $insert_cols, $data, $imp_id);
					}
				}
				else if ($table == 'et_ophleinjection_injection') {
					// this is the insert for the injection treatment detail as well as doing the insert, we want to 
					// define info entry for the event
					list($imp_id, $insert_cols, $data) = $this->handleRawData($raw_columns, $raw_data);
					
					foreach ($insert_cols as $i => $col) {
						if ($col == 'event_id') {
							$event_imp_id = $data[$i];
							break;
						}
					}
					
					// we're creating an archived event with this injection
					if ($archive = @$this->patient_archive_event[$event_imp_id]) {
						$insert_cols[] = 'archive_firm_id';
						$insert_cols[] = 'archive_hosnum';
						$data[] = $this->patient_archive_episode[$archive['episode_imp_id']]['firm_id'];
						$data[] = $this->patient_archive_episode[$archive['episode_imp_id']]['hosnum'];
						
						// remove the event id
						$new_cols = array();
						$new_data = array();
						foreach ($insert_cols as $i => $col) {
							if ($col != 'event_id') {
								$new_cols[] = $col;
								$new_data[] = $data[$i];
							}
						}
						$insert_cols = $new_cols;
						$data = $new_data;
					}
					else {
						list($event_id, $left_drug_id, $right_drug_id) = $this->getDataForEventInfo($insert_cols, $data);
						// update the info text for the event
						$info = '';
						if ((int)$left_drug_id) {
							if ((int)$right_drug_id) {
								if ($left_drug_id == $right_drug_id) {
									$info = 'Both: ' . $this->getDrugName($left_drug_id);
								}
								else {
									$info = 'L: ' . $this->getDrugName($left_drug_id) . ' / ' . 'R: ' . $this->getDrugName($right_drug_id);
								}
							}
							else {
								$info = 'Left: ' . $this->getDrugName($left_drug_id);
							}
						}
						else if ((int)$right_drug_id) {
							$info = 'Right: ' . $this->getDrugName($right_drug_id);
						}
						$this->updateEventInfo($event_id, $info);
					}
					$this->processHandledData($table, $insert_cols, $data, $imp_id);
				}
			}
		} catch (Exception $e) {
			if ($e->getCode() == self::NOPTNT) {
				echo "WARN: " . $e->getMessage();
			}
			else {
				throw $e;
			}
		}
	}
	
	protected $archivePatient = array();
	protected $patient_archive_event = array();
	protected $patient_archive_episode = array();
	
	/**
	 * (non-PHPdoc)
	 * @see RelatedImportComplexCommand::_storeTableVal()
	 */
	protected function _storeTableVal($col_spec, $value)
	{
		
		// split the col spec on dot, do select and store
		list($table, $column) = explode(".", $col_spec);
		if ($col_spec == 'firm.pas_code') {
			// we can have more than one firm based on the pas_code, so we need to get the best one for the
			// purposes of injection.
			$firms = Firm::model()->findAll('pas_code = ?', array($value));
			$fids = array();
			foreach ($firms as $f) {
				$fids[] = $f->id;
			}
			if (count($fids)) {
				$this->column_value_map[$col_spec][$value] = $this->getFirmIdWithBestSubspecialty($fids);
			} 
			else {
				echo "WARN: No firm match found for pas_code " . $value . ", using default firm\n";
				$this->column_value_map[$col_spec][$value] = $this->default_firm_id;
			}
		}
		else if ($table == 'patient') {
			// for patient importing we need to trigger the search if the patient isn't there
			// TODO use noPas when PAS suppressed for import and we put data into holding event ...
			if (self::NOPAS) {
				$patient = Patient::model()->noPas()->find($column . '= ?',array($value));
				if (!$patient) {
					// record that we are tracking this patient in archive mode
					$this->episode_archive_record = true;
				}
			}
			else {
				$patient = Patient::model()->find($column . '= ?',array($value));
				if (!$patient) {
					$patient = new Patient;
					$patient->$column = sprintf('%07s',$value);
					$data_provider = $patient->search(array(
							'pageSize' => 20,
							'currentPage' => 0,
							'sortBy' => 'hos_num*1',
							'sortDir' => 'asc',
							'first_name'=>null, 'last_name'=>null));
					$nr = $patient->search_nr(array('first_name'=>null, 'last_name'=>null));
					if ($nr != 1) {
						throw new Exception('Patient not found with ' . $column . ' of ' . $value, self::NOPTNT);
					}
					// have to iterate through getData, though we know there should only be
					// one record
					foreach ($data_provider->getData() as $item) {
						$patient = $item;
					}
				}
			}
			if ($patient) {
				$this->column_value_map[$col_spec][$value] = $patient->id;
			}
			else {
				$this->column_value_map[$col_spec][$value] = -1;
			}
		}
		else {
			parent::_storeTableVal($col_spec, $value);
		}
		
	}
	
	protected function getTableVal($col_spec, $value)
	{
		$val = parent::getTableVal($col_spec, $value);
		if ($val == -1) {
			// record that we are tracking this patient in archive mode
			$this->episode_archive_record = true;
		}
		return $val;
	}
	
}
