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
?>
<?php echo $form->dropdownlist($model, 'drug_id', CHtml::listData($model->getTreatmentDrugs(), 'id', 'name'), array('empty' => '- Please select -', 'class' => 'clearfix'))?>
<?php echo $form->dropdownlist($model, 'decisiontree_id', CHtml::listData(OphCoTherapyapplication_DecisionTree::model()->findAll(),'id','name'),array('empty'=>'- Please select -'))?>
<?php echo $form->radioBoolean($model, 'contraindications_required', array(1 => 'Yes', 0 => 'No'), array('field' => 9))?>
<?php echo $form->textField($model, 'template_code', array(), array(), array('field' => 5))?>
<div class="row field-row">
	<div class="large-10 large-offset-2 column">
		<span class="field-info">The template code is used to determine what form is attached to application email. Leave blank for the default behaviour.</span>
	</div>
</div>

<hr />

<?php echo $form->textfield($model, 'intervention_name')?>
<?php echo $form->textField($model, 'dose_and_frequency')?>
<?php echo $form->textField($model, 'administration_route')?>

<div class="row field-row">
	<div class="large-2 column">
		<label for="<?php echo get_class($model).'_cost';?>">
			<?php echo $model->getAttributeLabel('cost_type_id')?>:
		</label>
	</div>
	<div class="large-2 column">
		<?php echo $form->textField($model, 'cost',  array('nowrapper' => true))?>
	</div>
	<div class="large-1 column">
		<label for="<?php echo get_class($model).'_cost_type_id';?>">per</label>
	</div>
	<div class="large-2 column end">
		<?php echo $form->dropDownList($model, 'cost_type_id', CHtml::listData(OphCoTherapyapplication_Treatment_CostType::model()->findAll(), 'id', 'name'), array('nowrapper' => true))?>
	</div>
</div>

<fieldset class="row field-row">
	<legend class="large-2 column">
		<?php echo $model->getAttributeLabel('monitoring_frequency')?>:
	</legend>
	<div class="large-1 column">
		<label for="<?php echo get_class($model).'_monitoring_frequency';?>">
			Every
		</label>
	</div>
	<div class="large-1 column">
		<?php echo $form->textField($model, 'monitoring_frequency',  array('nowrapper' => true))?>
	</div>
	<div class="large-3 column end">
		<?php echo $form->dropDownList($model, 'monitoring_frequency_period_id', CHtml::listData(Period::model()->findAll(), 'id', 'name'), array('nowrapper' => true))?>
	</div>
</fieldset>

<?php echo $form->textArea($model, 'duration')?>
<?php echo $form->textArea($model, 'toxicity')?>
