<?php
/**
 * (C) OpenEyes Foundation, 2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

class OphCoTherapyapplication_ProcessorTest extends CTestCase
{
	private $event;
	private $event_props;

	private $processor;
	private $elements;

	private $moduleAPI;

	static public function setupBeforeClass()
	{
		// FIXME: Modules should be initialised by the test bootstrap once the core has support for running module tests
		Yii::app()->getModule('OphCiExamination');
		Yii::app()->getModule('OphCoTherapyapplication');
	}

	public function setUp()
	{
		$this->event = $this->getMockBuilder('Event')->disableOriginalConstructor()->getMock();
		$this->event->expects($this->any())->method('__get')->will($this->returnCallback(array($this, 'getEventProperty')));
		$this->event_props = array(
			'eventType' => (object)array('class_name' => 'OphCoTherapyapplication'),
			'episode' => (object)array('patient' => (object)array())
		);

		// Hate doing this but until we have a way to properly mock out active records it'll have to do
		$this->processor = $this->getMockBuilder('OphCoTherapyapplication_Processor')
			->setMethods(array('getElement'))
			->setConstructorArgs(array($this->event))
			->getMock();
		$this->processor->expects($this->any())->method('getElement')->will($this->returnCallback(array($this, 'getMockElement')));
		$this->elements = array();

		$this->moduleAPI = $this->getMockBuilder('ModuleAPI')->disableOriginalConstructor()->getMock();
		Yii::app()->setComponent('moduleAPI', $this->moduleAPI);
	}

	public function tearDown()
	{
		Yii::app()->setComponent('moduleAPI', null);
	}

	/**
	 * @expectedException Exception
	 */
	public function testConstruct_ValidateEventType()
	{
		$this->event_props['eventType']->class_name = 'foo';
		new OphCoTherapyapplication_Processor($this->event);
	}

	public function getProcessWarningsDataProvider()
	{
		return array(
			array(
				Eye::LEFT, true, true, true, true,
				array(
				)
			),
			array(
				Eye::LEFT, false, true, true, true,
				array(
					'No Injection Management has been created for left diagnosis.'
				)
			),
			array(
				Eye::LEFT, true, false, true, true,
				array(
				)
			),
			array(
				Eye::LEFT, true, true, false, true,
				array(
					'Visual acuity not found for left eye.'
				)
			),
			array(
				Eye::LEFT, true, true, true, false,
				array(
					'Visual acuity not found for right eye.'
				)
			),
			array(
				Eye::LEFT, false, false, false, false,
				array(
					'No Injection Management has been created for left diagnosis.',
					'Visual acuity not found for left eye.',
					'Visual acuity not found for right eye.'
				)
			),
			array(
				Eye::RIGHT, true, true, true, true,
				array(
				)
			),
			array(
				Eye::RIGHT, false, true, true, true,
				array(
				)
			),
			array(
				Eye::RIGHT, true, false, true, true,
				array(
					'No Injection Management has been created for right diagnosis.'
				)
			),
			array(
				Eye::RIGHT, true, true, false, true,
				array(
					'Visual acuity not found for left eye.'
				)
			),
			array(
				Eye::RIGHT, true, true, true, false,
				array(
					'Visual acuity not found for right eye.'
				)
			),
			array(
				Eye::RIGHT, false, false, false, false,
				array(
					'No Injection Management has been created for right diagnosis.',
					'Visual acuity not found for left eye.',
					'Visual acuity not found for right eye.'
				)
			),
			array(
				Eye::BOTH, true, true, true, true,
				array(
				)
			),
			array(
				Eye::BOTH, false, true, true, true,
				array(
					'No Injection Management has been created for left diagnosis.'
				)
			),
			array(
				Eye::BOTH, true, false, true, true,
				array(
					'No Injection Management has been created for right diagnosis.'
				)
			),
			array(
				Eye::BOTH, true, true, false, true,
				array(
					'Visual acuity not found for left eye.'
				)
			),
			array(
				Eye::BOTH, true, true, true, false,
				array(
					'Visual acuity not found for right eye.'
				)
			),
			array(
				Eye::BOTH, false, false, false, false,
				array(
					'No Injection Management has been created for left diagnosis.',
					'No Injection Management has been created for right diagnosis.',
					'Visual acuity not found for left eye.',
					'Visual acuity not found for right eye.'
				)
			),
		);
	}

	/**
	 * @dataProvider getProcessWarningsDataProvider
	 */
	public function testGetProcessWarnings($eye_id, $injLeft, $injRight, $acLeft, $acRight, $warnings)
	{
		$diag = $this->getMockElement('Element_OphCoTherapyapplication_Therapydiagnosis');
		$diag->expects($this->any())->method('hasLeft')->will($this->returnValue($eye_id != Eye::RIGHT));
		$diag->expects($this->any())->method('hasRight')->will($this->returnValue($eye_id != Eye::LEFT));

		$exam_api = $this->getMockBuilder('OphCiExamination_API')->disableOriginalConstructor()->getMock();
		$this->moduleAPI->expects($this->any())->method('get')->will($this->returnValueMap(array(array('OphCiExamination', $exam_api))));

		$exam_api->expects($this->any())->method('getInjectionManagementComplexInEpisodeForDisorder')
			 ->will($this->returnCallback(
				 function ($patient, $episode, $side) use ($injLeft, $injRight) {
					 return ($side == 'left' && $injLeft) || ($side == 'right' && $injRight);
				 }
			 ));
		$exam_api->expects($this->any())->method('getLetterVisualAcuityForEpisodeLeft')->will($this->returnValue($acLeft));
		$exam_api->expects($this->any())->method('getLetterVisualAcuityForEpisodeRight')->will($this->returnValue($acRight));

		$this->assertEquals($warnings, $this->processor->getProcessWarnings());
	}

	public function testIsEventNonCompliant_True()
	{
		$suitability = $this->getMockElement('Element_OphCoTherapyapplication_PatientSuitability');
		$suitability->expects($this->any())->method('isNonCompliant')->will($this->returnValue(true));

		$this->assertTrue($this->processor->isEventNonCompliant());
	}

	public function testIsEventNonCompliant_False()
	{
		$suitability = $this->getMockElement('Element_OphCoTherapyapplication_PatientSuitability');
		$suitability->expects($this->any())->method('isNonCompliant')->will($this->returnValue(false));

		$this->assertFalse($this->processor->isEventNonCompliant());
	}

	public function testGeneratePreviewPdf_NoEc()
	{
		$controller = $this->getMockBuilder('CController')->disableOriginalConstructor()->getMock();
		$this->elements['Element_OphCoTherapyapplication_ExceptionalCircumstances'] = null;
		$this->assertNull($this->processor->generatePreviewPdf($controller));
	}

	public function getEventProperty($name)
	{
		return $this->event_props[$name];
	}

	/**
	 * @param string $class_name
	 * @return BaseEventTypeElement
	 */
	public function getMockElement($class_name)
	{
		if (!array_key_exists($class_name, $this->elements)) {
			$this->elements[$class_name] = $this->getMockBuilder($class_name)->disableOriginalConstructor()->getMock();
		}

		return $this->elements[$class_name];
	}
}
