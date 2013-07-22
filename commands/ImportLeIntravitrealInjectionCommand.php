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
	
	public function getName()
	{
		return 'Import Legacy Intravitreal Injection Command.';
	}
	
	protected $firm_subspecialty_map = array();
	protected $subspecialty_firms_map = array();
	protected $event_type_id;
	
	public function run($args)
	{
		// get the event type for the module
		$event_type = EventType::model()->find('class_name = ?', array('OphLeIntravitrealinjection'));

		$this->event_type_id = $event_type->id;	
		parent::run($args);
	}
	
	protected function getFirmSubspecialtyId($firm_id)
	{
		$db = Yii::app()->db;
		if (!isset($this->firm_subspecialty_map[$firm_id])) {
			$query = "SELECT sa.subspecialty_id FROM  service_subspecialty_assignment sa, firm f WHERE sa.id = f.service_subspecialty_assignment_id AND f.id = " . $db->quoteValue($firm_id);
			$res =  $db->createCommand($query)->query();
			foreach ($res as $row) {
				// we'll grab the last if there are multiple.
				$this->firm_subspecialty_map[$firm_id] = $row['subspecialty_id'];
			}
		}
		return $this->firm_subspecialty_map[$firm_id];
	}
	
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
	 * @return Ambigous <NULL, unknown>
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
				list($imp_id, $insert_cols, $data) = $this->handleRawData($raw_columns, $raw_data);
				$col_pos = array(
					'patient_id' => null,
					'firm_id' => null
				); 
				foreach ($insert_cols as $i => $col) {
					echo $col . "<<\n";
					if (array_key_exists($col, $col_pos)) {
						$col_pos[$col] = $i;
					}
				}
				foreach ($col_pos as $required => $pos) {
					echo $required . ":" . $pos . "\n";
					if (is_null($pos)) {
						throw new Exception('missing required column in episode map ' . $required);
					}
				}
				
				// look to see if there is already an open episode for the patient in the subspecialty, and map the import id to that if so
				// get the subspecialty_id
				$subspecialty_id = $this->getFirmSubspecialtyId($data[$col_pos['firm_id']]);
				// get open episode for patient in this subspecialty
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
			else {
				if ($table == 'event') {
					if (!array_key_exists('event_type_id', $raw_columns)) {
						$raw_columns[] = 'event_type_id';
						$raw_data[] = $this->event_type_id;
					}
				}
				parent::insert($table, $raw_columns, $raw_data);
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
	
	/**
	 * (non-PHPdoc)
	 * @see RelatedImportComplexCommand::_storeTableVal()
	 */
	protected function _storeTableVal($col_spec, $value)
	{
		
		// split the col spec on dot, do select and store
		list($table, $column) = explode(".", $col_spec);
		if ($table == 'patient') {
			// for patient importing we need to trigger the search if the patient isn't there
			if (!$patient = Patient::model()->find($column . '= ?',array($value))) {
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
			}
			$this->column_value_map[$col_spec][$value] = $patient->id;
		}
		else {
			parent::_storeTableVal($col_spec, $value);
		}
		
	}
	
}
