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

class OphLeIntravitrealinjection_API extends BaseAPI
{
	private $previous_treatments = array();

	/**
	 * caching method for previous injections store
	 *
	 * @param Patient $patient
	 * @param Episode $episode
	 *
	 * @return Element_OphLeIntravitrealinjection_IntravitrealInjection[]
	 */
	protected function previousInjectionsForPatientEpisode($patient, $episode)
	{
		if (!isset($this->previous[$patient->id])) {
			$this->previous[$patient->id] = array();
		}

		if (!isset($this->previous[$patient->id][$episode->id])) {
			$events = $this->getEventsInEpisode($patient, $episode);
			$previous = array();
			foreach ($events as $event) {
				if ($treat = Element_OphLeIntravitrealinjection_IntravitrealInjection::model()->find('event_id = :event_id', array(':event_id' => $event->id))) {
				$previous[] = $treat;
				}
			}
			$this->previous[$patient->id][$episode->id] = $previous;
		}
		return $this->previous[$patient->id][$episode->id];
	}

	/**
	 * return the set of treatment elements from previous injection events
	 *
	 * @param Patient $patient
	 * @param Episode $episode
	 * @param string $side
	 * @param Drug $drug
	 * @throws Exception
	 *
	 * @return Element_OphLeIntravitrealinjection_IntravitrealInjection[] - array of treatment elements for the eye and optional drug
	 */
	public function previousInjections($patient, $episode, $side, $drug = null)
	{
		$previous = $this->previousInjectionsForPatientEpisode($patient, $episode);

		switch ($side) {
			case 'left':
				$eye_ids = array(SplitEventTypeElement::LEFT, SplitEventTypeElement::BOTH);
				break;
			case 'right':
				$eye_ids = array(SplitEventTypeElement::RIGHT, SplitEventTypeElement::BOTH);
				break;
			default:
				throw new Exception('invalid side value provided: ' . $side);
				break;
		}

		$res = array();
		foreach ($previous as $prev) {
			if (in_array($prev->eye_id,$eye_ids)) {
				if ($drug == null || $prev->{$side . '_drug_id'} == $drug->id) {
					$res[] = array(
						$side . '_drug_id' => $prev->{$side . '_drug_id'}, 
						$side . '_number' => $prev->{$side . '_number'},
						'date' => $prev->created_date
					);
				}
			}
		}
		return $res;
	}
}
