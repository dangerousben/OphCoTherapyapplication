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
* @copyright Copyright (c) 2011-2012, OpenEyes Foundation
* @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
*/
?>

<style>
td, p {
	font-size: 8pt;
}

h1 {
	text-align: center;
}

table td {
	border: 1px solid #dae6f1;
	padding: 3px;
}

td.signature {
	background-color: #dae6f1;
}
	
</style>

<?php 
$exam_api = Yii::app()->moduleAPI->get('OphCiExamination');
?>

<p>Notification for Dexamethasone intravitreal implant for the treatment of macular oedema secondary to retinal vein occlusion.</p>

<table>
<tr>
<td>Patient NHS Number:</td>
<td><?php echo $patient->nhs_num ? $patient->nhs_num : "Unknown" ?></td>
<td>Trust</td>
<td>Moorfields NHS Foundation Trust</td>
<td>GP Name:</td>
<td><?php echo ($patient->gp) ? $patient->gp->contact->fullName : 'Unknown'; ?></td>
</tr>

<tr>
<td>Patient Hospital Number:</td>
<td><?php echo $patient->hos_num ?></td>
<td>Consultant Making Request:</td>
<td><?php echo $service_info->consultant->getConsultantName() ?></td>
<td>GP Practice Code:</td>
<td>TBD</td>
</tr>

<tr>
<td>Patient Name</td>
<td><?php echo $patient->getFullName() ?></td>
<td>Proposed MEH Site:</td>
<td>TBD</td>
<td>GP Post Code:</td>
<td><?php echo ($patient->practice && $patient->practice->address) ? $patient->practice->address->postcode : 'Unknown' ?></td>
</tr>

<tr>
<td>Patient VA</td>
<td colspan="5"><?php echo ($exam_api && ($va = $exam_api->getLetterVisualAcuity($patient, $event->episode, $side)) ) ? $va : "Not measured"; ?></td>
</tr>

<tr>
<td>GP PCT</td>
<td colspan="5">TBD</td>
</tr>

<tr>
<td colspan="6"><b>Please indicate which aspect of NICE TA229 applies for the patient:</b></td>
</tr>

<tr>
<td colspan="6">here we should throw an exception if there is not an appropriate Examination response for an Ozurdex application</td>
</tr>

<tr>
<td colspan="6" class="signature">Signature by Trust Chief Pharmacist:<br /><br /><br /></td>
</tr>
</table>