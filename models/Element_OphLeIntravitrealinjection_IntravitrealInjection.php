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

/**
 * This is the model class for table "et_ophleinjection_injection".
 *
 * The followings are the available columns in table:
 * @property string $id
 * @property integer $event_id
 * @property integer $eye_id
 * @property integer $left_drug_id
 * @property integer $right_drug_id
 * @property integer $left_number
 * @property integer $right_number
 *
 * The followings are the available model relations:
 *
 * @property ElementType $element_type
 * @property EventType $eventType
 * @property Event $event
 * @property User $user
 * @property User $usermodified
 * @property Eye $eye
 * @property OphTrIntravitrealinjection_Treatment_Drug $left_drug
 * @property OphTrIntravitrealinjection_Treatment_Drug $right_drug
 */

class Element_OphLeIntravitrealinjection_IntravitrealInjection extends SplitEventTypeElement
{
	public $service;

	/**
	 * Returns the static model of the specified AR class.
	 * @return the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'et_ophleinjection_injection';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('event_id, eye_id, left_drug_id, right_drug_id, left_number, right_number, ', 'safe'),
			array('eye_id, left_drug_id, right_drug_id, left_number, right_number, ', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, event_id, eye_id, left_drug_id, right_drug_id, left_number, right_number, ', 'safe', 'on' => 'search'),
			array('left_number', 'numerical', 'integerOnly' => true, 'min' => 1, 'message' => 'Number of Injections must be higher or equal to 1'),
			array('right_number', 'numerical', 'integerOnly' => true, 'min' => 1, 'message' => 'Number of Injections must be higher or equal to 1'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'element_type' => array(self::HAS_ONE, 'ElementType', 'id','on' => "element_type.class_name='".get_class($this)."'"),
			'eventType' => array(self::BELONGS_TO, 'EventType', 'event_type_id'),
			'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
			'user' => array(self::BELONGS_TO, 'User', 'created_user_id'),
			'usermodified' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
			'eye' => array(self::BELONGS_TO, 'Eye', 'eye_id'),
			'left_drug' => array(self::BELONGS_TO, 'OphTrIntravitrealinjection_Treatment_Drug', 'left_drug_id'),
			'right_drug' => array(self::BELONGS_TO, 'OphTrIntravitrealinjection_Treatment_Drug', 'right_drug_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'event_id' => 'Event',
			'eye_id' => 'Eye',
			'left_drug_id' => 'Drug',
			'right_drug_id' => 'Drug',
			'left_number' => 'Number of Injections',
			'right_number' => 'Number of Injections',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('event_id', $this->event_id, true);
		$criteria->compare('eye_id', $this->eye_id);
		$criteria->compare('left_drug_id', $this->left_drug_id);
		$criteria->compare('right_drug_id', $this->right_drug_id);
		$criteria->compare('left_number', $this->left_number);
		$criteria->compare('right_number', $this->right_number);

		return new CActiveDataProvider(get_class($this), array(
			'criteria' => $criteria,
		));
	}



	protected function beforeSave()
	{
		return parent::beforeSave();
	}

	protected function afterSave()
	{

		return parent::afterSave();
	}

	protected function beforeValidate()
	{
		return parent::beforeValidate();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see BaseEventTypeElement::getInfoText()
	 */
	public function getInfoText()
	{
		if ($this->eye_id == SplitEventTypeElement::LEFT) {
			return $this->eye->name . ": " . $this->left_drug->name;
		} elseif ($this->eye_id == SplitEventTypeElement::RIGHT) {
			return $this->eye->name . ": " . $this->right_drug->name;
		} else {
			if ($this->right_drug_id == $this->left_drug_id) {
				return $this->eye->name . ": " . $this->left_drug->name;
			} else {
				return "L: " . $this->left_drug->name . " / R: " . $this->right_drug->name;
			}
		}
	}
}
?>