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

class OphCoTherapyapplication_Processor
{
	const SNOMED_INTRAVITREAL_INJECTION = 231755001;

	const STATUS_PENDING = 'pending';
	const STATUS_SENT = 'sent';
	const STATUS_REOPENED = 're-opened';

	private $event;

	/**
	 * @param Event $event Must be an OphCoTherapyapplication event
	 * @throws Exception
	 */
	public function __construct(Event $event)
	{
		$event_type = $event->eventType->class_name;
		if ($event_type != 'OphCoTherapyapplication') {
			throw new Exception("Passed an event of type '$event_type'");
		}

		$this->event = $event;
	}

	/**
	 * Returns status of applicant: pending, sent, re-opened
	 *
	 * @return string
	 */
	public function getApplicationStatus()
	{
		$emails = OphCoTherapyapplication_Email::model()->forEvent($this->event);
		if (!$emails->exists()) {
			return self::STATUS_PENDING;
		}

		$diag = $this->getElement('Element_OphCoTherapyapplication_Therapydiagnosis');

		if ($diag->hasLeft() && !$emails->leftEye()->unarchived()->exists() ||
		    $diag->hasRight() && !$emails->rightEye()->unarchived()->exists()) {
			return self::STATUS_REOPENED;
		} else {
			return self::STATUS_SENT;
		}
	}

	/**
	 * Get any relevant warnings
	 *
	 * @return array
	 */
	public function getProcessWarnings()
	{
		$warnings = array();

		$el_diag  = $this->getElement('Element_OphCoTherapyapplication_Therapydiagnosis');
		$sides = array();
		if ($el_diag->hasLeft()) {
			$sides[] = 'left';
		}
		if ($el_diag->hasRight()) {
			$sides[] = 'right';
		}

		if ($api = Yii::app()->moduleAPI->get('OphCiExamination')) {
			$missing_sides = array();

			foreach ($sides as $side) {
				if (!$api->getInjectionManagementComplexInEpisodeForDisorder(
					$this->event->episode->patient,
					$this->event->episode,
					$side,
					$el_diag->{$side . '_diagnosis1_id'},
					$el_diag->{$side . '_diagnosis2_id'})) {
					$missing_sides[] = $side;
				}
			}

			foreach ($missing_sides as $missing) {
				$warnings[] = 'No Injection Management has been created for ' . $missing . ' diagnosis.';
			}

			//TODO: the exam api should be consolidated at some point, and these methods may be deprecated
			if (!$api->getLetterVisualAcuityForEpisodeLeft($this->event->episode)) {
				$warnings[] = 'Visual acuity not found for left eye.';
			}

			if (!$api->getLetterVisualAcuityForEpisodeRight($this->event->episode)) {
				$warnings[] = 'Visual acuity not found for right eye.';
			}
		} else {
			error_log('Therapy application requires OphCIExamination module');
		}
		if ($api = Yii::app()->moduleAPI->get('OphTrConsent')) {
			$procedure = Procedure::model()->find(array('condition' => 'snomed_code = :snomed', 'params' => array(':snomed' => $this::SNOMED_INTRAVITREAL_INJECTION)));
			foreach ($sides as $side) {
				if (!$api->hasConsentForProcedure($this->event->episode, $procedure, $side)) {
					$warnings[] = 'Consent form is required for ' . $side . ' eye.';
				}
			}
		}

		return $warnings;
	}

	/**
	 * return boolean to indicate whether the given event is non compliant or not
	 *
	 * @return boolean
	 * @see Element_OphCoTherapyapplication_PatientSuitability::isNonCompliant()
	 */
	public function isEventNonCompliant()
	{
		return $this->getElement('Element_OphCoTherapyapplication_PatientSuitability')->isNonCompliant();
	}

	/**
	 * @return OphCoTherapyapplication_Email[]
	 */
	public function getLeftSentEmails()
	{
		return OphCoTherapyapplication_Email::model()->forEvent($this->event)->leftEye()->findAll();
	}

	/**
	 * @return OphCoTherapyapplication_Email[]
	 */
	public function getRightSentEmails()
	{
		return OphCoTherapyapplication_Email::model()->forEvent($this->event)->rightEye()->findAll();
	}

	/**
	 * Generate PDFs in a wrapper for preview purposes
	 *
	 * Note that this is currently only used for non-compliant applications.
	 *
	 * @param CController $controller
	 * @return OETCPDF|null
	 */
	public function generatePreviewPdf($controller)
	{
		$ec = $this->getElement('Element_OphCoTherapyapplication_ExceptionalCircumstances');
		if (!$ec) return null;

		$template_data = $this->getTemplateData();

		$pdfbodies = array();

		if ($ec->hasLeft()) {
			$left_template_data = $template_data + $this->getSideSpecificTemplateData('left');
			$pdfbodies[] = $this->generatePdfForSide($controller, $left_template_data, 'left');
		}

		if ($ec->hasRight()) {
			$right_template_data = $template_data + $this->getSideSpecificTemplateData('right');
			$pdfbodies[] = $this->generatePdfForSide($controller, $right_template_data, 'right');
		}

		$pdfwrapper = new OETCPDF();
		$pdfwrapper->SetAuthor($ec->usermodified->fullName);
		$pdfwrapper->SetTitle('Therapy application preview');
		$pdfwrapper->SetSubject('Therapy application');

		foreach($pdfbodies as $body) {
			$body->render($pdfwrapper);
		}

		return $pdfwrapper;
	}

	/**
	 * processes the application for the event with id $event_id returns a boolean to indicate whether this was successful
	 * or not.
	 *
	 * @param CController $controller
	 * @throws Exception
	 * @return boolean
	 */
	public function processEvent(CController $controller)
	{
		if ($this->getApplicationStatus() == self::STATUS_SENT || $this->getProcessWarnings()) {
			return false;
		}

		$success = true;

		$template_data = $this->getTemplateData();

		$diag = $this->getElement('Element_OphCoTherapyapplication_Therapydiagnosis');

		if ($diag->hasLeft() && !$this->processEventForEye($controller, $template_data, Eye::LEFT)) {
			$success = false;
		}

		if ($diag->hasRight() && !$this->processEventForEye($controller, $template_data, Eye::RIGHT)) {
			$success = false;
		}

		return $success;
	}

	/**
	 * returns boolean as to whether the event has been submitted (successfully) or not.
	 *
	 * @return bool
	 */
	public function isEventSubmitted()
	{
		if ($this->getApplicationStatus() == self::STATUS_SENT) {
			return true;
		}
		return null;
	}

	/**
	 * Get an element object by class name
	 *
	 * We could potentially add a caching layer here if performance becomes a problem, but would have to watch out for stale data
	 *
	 * @todo This should really be available as a public method on Event in the core
	 *
	 * @param string $class_name
	 * @return BaseEventTypeElement|null
	 */
	protected function getElement($class_name)
	{
		return $class_name::model()->findByAttributes(array('event_id' => $this->event->id));
	}

	/**
	 * @return string
	 */
	private function getViewPath()
	{
		return Yii::app()->getModule('OphCoTherapyapplication')->getViewPath() . DIRECTORY_SEPARATOR . 'email';
	}

	/**
	 * Generate PDF for the given side, if applicable, otherwise return null
	 *
	 * @param CController $controller
	 * @param array $template_data
	 * @param string $side
	 * @return OETCPDF|null
	 */
	private function generatePdfForSide(CController $controller, array $template_data, $side)
	{
		if ($template_data['suitability']->{$side . "_nice_compliance"}) {
			$file = $this->getViewPath() . DIRECTORY_SEPARATOR . 'pdf_compliant';
		} else {
			$file = $this->getViewPath() . DIRECTORY_SEPARATOR . 'pdf_noncompliant';
		}

		$template_code = $template_data['treatment']->template_code;
		if ($template_code) {
			$specific = $file . "_" . $template_code . ".php";
			if (file_exists($specific)) {
				$file = $specific;
			} else {
				$file .= ".php";
			}
		} else {
			$file .= ".php";
		}

		if (file_exists($file)) {
			$body = $controller->renderInternal($file, $template_data, true);

			$letter = new OELetter();
			$letter->setBarcode("E:" . $this->event->id);
			$letter->addBody($body);
			return $letter;
		}
		return null;
	}

	/**
	 * create the PDF file as a ProtectedFile for the given side
	 *
	 * @param CController $controller
	 * @param array $template_data
	 * @param string $side
	 * @throws Exception
	 * @return ProtectedFile|null
	 */
	protected function createAndSavePdfForSide(CController $controller, array $template_data, $side)
	{
		$pdfdoc = $this->generatePdfForSide($controller, $template_data, $side);
		if ($pdfdoc) {
			$pdf = new OETCPDF();
			$pdf->setAuthor('OpenEyes');
			$pdf->setTitle('Therapy Application');
			$pdf->SetSubject('Therapy Application');

			$pdfdoc->render($pdf);

			$pfile = ProtectedFile::createForWriting('ECForm - ' . $side . ' - ' . $template_data['patient']->hos_num . '.pdf');
			$pdf->Output($pfile->getPath(), "F");
			if (!$pfile->save()) {
				throw new Exception('unable to save protected file');
			}

			return $pfile;
		} else {
			return null;
		}
	}

	/**
	 * generate the email text for the given side
	 *
	 * @param CController $controller
	 * @param array $template_data
	 * @param string $side
	 * @return string
	 */
	protected function generateEmailForSide(CController $controller, array $template_data, $side)
	{
		if ($template_data['compliant']) {
			$file = 'email_compliant.php';
		} else {
			$file = 'email_noncompliant.php';
		}

		$view = $this->getViewPath() . DIRECTORY_SEPARATOR . $file;

		return $controller->renderInternal($view, $template_data, true);
	}

	/**
	 * @return array
	 */
	private function getTemplateData()
	{
		// at the moment we are using a fixed type of commissioning body, but it's possible that in the future this
		// might need to be determined in a more complex fashion, so we pass the type through to the templates
		$cbody_type = CommissioningBodyType::model()->findByPk(1);

		return array(
			'event' => $this->event,
			'patient' => $this->event->episode->patient,
			'cbody_type' => $cbody_type,
			'diagnosis' => $this->getElement('Element_OphCoTherapyapplication_Therapydiagnosis'),
			'suitability' => $this->getElement('Element_OphCoTherapyapplication_PatientSuitability'),
			'service_info' => $this->getElement('Element_OphCoTherapyapplication_MrServiceInformation'),
			'exceptional' => $this->getElement('Element_OphCoTherapyapplication_ExceptionalCircumstances'),
		);
	}

	/**
	 * @param string $side
	 * @return array
	 */
	private function getSideSpecificTemplateData($side)
	{
		$suitability = $this->getElement('Element_OphCoTherapyapplication_PatientSuitability');

		return array(
			'side' => $side,
			'treatment' => $suitability->{"${side}_treatment"},
			'compliant' => $suitability->{"${side}_nice_compliance"}
		);
	}

	/**
	 * @param CController $controller
	 * @param array $template_data
	 * @param int $eye_id
	 * @throws Exception
	 * @return boolean
	 */
	private function processEventForEye(CController $controller, array $template_data, $eye_id)
	{
		switch($eye_id) {
			case Eye::LEFT:
				$eye_name = 'left';
				break;
			case Eye::RIGHT:
				$eye_name = 'right';
				break;
			default:
				throw new Exception("Invalid eye ID: '$eye_id'");
		}

		$template_data += $this->getSideSpecificTemplateData($eye_name);

		$attachments = array();
		$attach_size = 0;

		if (($app_file = $this->createAndSavePdfForSide($controller, $template_data, $eye_name))) {
			$attachments[] = $app_file;
			$attach_size += $app_file->size;
		}

		if (($ec = $this->getElement('Element_OphCoTherapyapplication_ExceptionalCircumstances'))) {
			foreach ($ec->{"${eye_name}_filecollections"} as $fc) {
				$attachments[] = $fc->getZipFile();
				$attach_size += $fc->getZipFile()->size;
			}
		}

		$link_to_attachments = ($attach_size > Helper::convertToBytes(Yii::app()->params['OphCoTherapyapplication_email_size_limit']));

		$template_data['link_to_attachments'] = $link_to_attachments;
		$email_text = $this->generateEmailForSide($controller, $template_data, $eye_name);

		$message = Yii::app()->mailer->newMessage();
		$message->setSubject('Therapy Application');
		$message->setFrom(Yii::app()->params['OphCoTherapyapplication_sender_email']);
		if ($template_data['compliant']) {
			$message->setTo(Yii::app()->params['OphCoTherapyapplication_compliant_recipient_email']);
		} else {
			$message->setTo(Yii::app()->params['OphCoTherapyapplication_noncompliant_recipient_email']);
		}
		$message->setBody($email_text);

		if (!$link_to_attachments) {
			foreach ($attachments as $att) {
				$message->attach(Swift_Attachment::fromPath($att->getPath())->setFilename($att->name));
			}
		}

		$success = Yii::app()->mailer->sendMessage($message);

		if ($success) {
			$email = new OphCoTherapyapplication_Email;
			$email->event_id = $this->event->id;
			$email->eye_id = $eye_id;
			$email->email_text = $email_text;
			$email->save();

			$email->addAttachments($attachments);

			$this->event->audit('therapy-application', 'submit');

			$this->event->info = self::STATUS_SENT;
			$this->event->save();

			return true;
		} else {
			OELog::log("Failed to send email for therapy application event_id '{$this->event->id}', eye_id '{$eye_id}'");

			// clean up
			if ($app_file) $app_file->delete();

			return false;
		}
	}
}
