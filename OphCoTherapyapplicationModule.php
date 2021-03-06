<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

/**
 * This is the module class for $this->moduleSuffix
 *
 * The followings are the available columns in table:
 * @property string $moduleShortSuffix
  */

class OphCoTherapyapplicationModule extends BaseEventTypeModule
{
	// this property is really only relevant to gii auto-generation, specifically
	// for updates to the module through gii
	public $moduleShortSuffix;

	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// import the module-level models and components
		$this->setImport(array(
			'OphCoTherapyapplication.models.*',
			'OphCoTherapyapplication.components.*',
			'OphCoTherapyapplication.services.*',
			'OphCoTherapyapplication.helpers.*',
			'OphTrIntravitrealinjection.models.*',
		));

		$this->moduleShortSuffix = "TherapyA";

		// check for required configuration variables
		$missing_config = array();
		foreach (array('OphCoTherapyapplication_sender_email',
				'OphCoTherapyapplication_compliant_recipient_email',
				'OphCoTherapyapplication_noncompliant_recipient_email',
				'OphCoTherapyapplication_applicant_email',
				'OphCoTherapyapplication_chief_pharmacist',
				'OphCoTherapyapplication_chief_pharmacist_contact',
				'OphCoTherapyapplication_email_size_limit',
				) as $required_config) {

			if (!isset(Yii::app()->params[$required_config])) {
				$missing_config[] = $required_config;
			}
		}
		if (count($missing_config)) {
			throw new Exception('Missing required configuration variables for ' . $this->getName() . ': ' . implode(", ", $missing_config));
		}
	}

	public function beforeControllerAction($controller, $action)
	{
		if (parent::beforeControllerAction($controller, $action)) {
			// this method is called before any module controller action is performed
			// you may place customized code here
			return true;
		} else
			return false;
	}
}
