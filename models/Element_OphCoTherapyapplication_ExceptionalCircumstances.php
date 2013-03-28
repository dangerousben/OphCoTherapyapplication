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
 * This is the model class for table "et_ophcotherapya_exceptional".
 *
 * The followings are the available columns in table:
 * @property string $id
 * @property integer $event_id
 * @property boolean $left_standard_intervention_exists
 * @property string $left_details
 * @property integer $left_intervention_id
 * @property string $left_description
 * @property integer $left_patient_factors
 * @property string $left_patient_factor_details
 * @property boolean $right_standard_intervention_exists
 * @property string $right_details
 * @property integer $right_intervention_id
 * @property string $right_description
 * @property integer $right_patient_factors
 * @property string $right_patient_factor_details
 *
 * The followings are the available model relations:
 *
 * @property ElementType $element_type
 * @property EventType $eventType
 * @property Event $event
 * @property User $user
 * @property User $usermodified
 * @property array(OphCoTherapyapplication_ExceptionalCircumstances_PrevIntervention) $left_previnterventions
 * @property array(OphCoTherapyapplication_ExceptionalCircumstances_PrevIntervention) $right_previnterventions
 */

class Element_OphCoTherapyapplication_ExceptionalCircumstances extends SplitEventTypeElement
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
		return 'et_ophcotherapya_exceptional';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('event_id, left_standard_intervention_exists, left_details, left_intervention_id, left_description, left_patient_factors, ' .
					'left_patient_factor_details, right_standard_intervention_exists, right_details, right_intervention_id, right_description, ' . 
					'right_patient_factors, right_patient_factor_details', 'safe'),
			array('left_standard_intervention_exists, left_details, left_intervention_id, left_description, left_patient_factors, left_patient_factor_details,', 
					'requiredIfSide', 'left'),
			array('right_standard_intervention_exists, right_details, right_intervention_id, right_description, right_patient_factors, right_patient_factor_details,',
					'requiredIfSide', 'right'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, event_id, left_standard_intervention_exists, left_details, left_intervention_id, left_description, left_patient_factors, ' .
					'left_patient_factor_details, right_standard_intervention_exists, right_details, right_intervention_id, right_description, ' . 
					'right_patient_factors, right_patient_factor_details', 'safe', 'on' => 'search'),
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
			'left_intervention' => array(self::BELONGS_TO, 'Element_OphCoTherapyapplication_ExceptionalCircumstances_Intervention', 'left_intervention_id'),
			'left_previousinterventions' => array(self::HAS_MANY, 'OphCoTherapyapplication_ExceptionalCircumstances_PrevIntervention', 'exceptional_id', 
					'on' => 'left_previousinterventions.exceptional_side = ' . SplitEventTypeElement::LEFT),
			'right_previousinterventions' => array(self::HAS_MANY, 'OphCoTherapyapplication_ExceptionalCircumstances_PrevIntervention', 'exceptional_id',
					'on' => 'right_previousinterventions.exceptional_side = ' . SplitEventTypeElement::RIGHT),
		);
	}

	public function sidedFields() {
		return array('standard_intervention_exists, details, intervention_id, description, patient_factors, patient_factor_details');
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'event_id' => 'Event',
			'left_standard_intervention_exists' => 'Standard Intervention Exists',
			'left_details' => 'Details and standard algorithm of care',
			'left_intervention_id' => 'Intervention',
			'left_description' => 'Description',
			'left_patient_factors' => 'Patient Factors',
			'left_patient_factor_details' => 'Details',
			'left_previousinterventions' => 'Previous Interventions',
			'right_standard_intervention_exists' => 'Standard Intervention Exists',
			'right_details' => 'Details and standard algorithm of care',
			'right_intervention_id' => 'Intervention',
			'right_description' => 'Description',
			'right_patient_factors' => 'Patient Factors',
			'right_patient_factor_details' => 'Details',
			'right_previousinterventions' => 'Previous Interventions',
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
		$criteria->compare('standard_intervention_exists', $this->standard_intervention_exists);
		$criteria->compare('details', $this->details);
		$criteria->compare('interventions_id', $this->interventions_id);
		$criteria->compare('description', $this->description);
		$criteria->compare('patient_factors', $this->patient_factors);
		$criteria->compare('patient_factor_details', $this->patient_factor_details);
		
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
}
?>