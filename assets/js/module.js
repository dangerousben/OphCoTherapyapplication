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

function ComplianceCalculator(elem, properties) {
	
	this._elem = elem;
	this._properties = properties;
	this._side = properties.side;
	this._nodes = {};
	this._nodes_by_parent = {};
	
	this.init()
}

/*
 * Initialises the ComplianceCalculator object, storing node definitions from data attributes and showing the root node
 */
ComplianceCalculator.prototype.init = function()
{
	var self = this;
	if (this._elem.data('defn')) {
		self._root_node_id = this._elem.data('defn').root_id;
	}
	else {
		console.log('ERROR: need root id');
	}
	// build up a store of the decision tree node hierarchy
	self._elem.find('.dt-node').each(function() {
		var defn = $(this).data('defn');
		self._nodes[defn.id] = $(this).data('defn');
		if (defn.parent_id) {
			if (self._nodes_by_parent[defn.parent_id]) {
				self._nodes_by_parent[defn.parent_id].push(defn.id);
			}
			else {
				self._nodes_by_parent[defn.parent_id] = [defn.id];
			}
		}
	});
	
	self.showNode(self._root_node_id);
};

/*
 * internal method to show the appropriate outcome and set the form value when an outcome is reached
 * stores the source_node_id against the outcome to keep track of what node is defining the outcome
 */
ComplianceCalculator.prototype.showOutcome = function(outcome_id, source_node_id)
{
	var node_elem = this._elem.find('#' + this._side + '_outcome_' + outcome_id); 
	this._elem.find('div.outcome').hide().each(function() {$(this).data('source-node-id', null); });
	node_elem.show().data('source-node-id', source_node_id);
	this._elem.find('#Element_OphCoTherapyapplication_PatientSuitability_' + this._side + '_nice_compliance').val(node_elem.data('comp-val'));
}

/*
 * internal method to hide outcome and reset the form value - will only hide the outcome if the source_node_id matches
 * the data attribute on the outcome being hidden (the outcome may now be being displayed because of a different source
 * node)
 */
ComplianceCalculator.prototype.hideOutcome = function(outcome_id, source_node_id)
{
	var node_elem = this._elem.find('#' + this._side + '_outcome_' + outcome_id); 
	if (node_elem.is(":visible") && node_elem.data('source-node-id') == source_node_id) {
		node_elem.hide();
		node_elem.data('source-node-id', null);
		this._elem.find('#Element_OphCoTherapyapplication_PatientSuitability_' + this._side + '_nice_compliance').val('');
	}
	
}

/*
 * show the specified node - if the node is an outcome then we show the outcome result, otherwise display node and check children or a child node
 */
ComplianceCalculator.prototype.showNode = function(node_id)	
{
	if (this._nodes[node_id].outcome_id) {
		this.showOutcome(this._nodes[node_id].outcome_id, node_id);
	}
	else {
		this._elem.find('#' + this._side + '_node_' + node_id).show();
		this.checkNode(node_id);
	}
};

/*
 * hide the specified node - will hide its children as well
 */
ComplianceCalculator.prototype.hideNode = function(node_id)	
{
	// clear the outcome if this was defining what the outcome was
	if (this._nodes[node_id]['outcome_id']) {
		this.hideOutcome(this._nodes[node_id]['outcome_id'], node_id);
	}
	var node_elem = this._elem.find('#' + this._side + '_node_' + node_id);
	if (node_elem.is(":visible") ) {
		node_elem.hide();
		// remove prev value attribute so that this node will be checked fresh if it is redisplayed
		node_elem.data('prev-val',null);
		
		// hide the children
		if (this._nodes_by_parent[node_id]) {
			for (var i =0; i < this._nodes_by_parent[node_id].length; i++) {
				this.hideNode(this._nodes_by_parent[node_id][i]);
			}
		}
	}
};

/*
 * check the given node to determine if a child should be shown because it has an answer
 */
ComplianceCalculator.prototype.checkNode = function(node_id)
{
	var node_elem = this._elem.find('#' + this._side + '_node_' + node_id);
	var node_defn = this._nodes[node_id];
	if (node_defn.question) {
		// has a value to check against
		var value = undefined;
		if (node_elem.find('select').length) {
			value = node_elem.find('select').val();
		}
		else {
			value = node_elem.find('input').val();
		}
		
		// if the value has changed and the node has children
		if (value != node_elem.data('prev-val') && this._nodes_by_parent[node_id]) {
			// set the store of the previous value
			node_elem.data('prev-val', value);
			// if it's an actual value
			if (value !== undefined && value.length) {
				// go through each child node to see if it has rules that match the value
				// if it does, show it.
				// FIXME: ensure we check nodes with rules before we check any without
				var notMatched = true;
				var default_node_id = null;
				for (var i = 0; i < this._nodes_by_parent[node_id].length; i++) {
					var child_id = this._nodes_by_parent[node_id][i];
					if (!this._nodes[child_id]['rules'].length) {
						default_node_id = child_id;
					}
					else {
						if (this.checkNodeRule(child_id, value) && notMatched) {
							this.showNode(child_id);
							notMatched = false;
						}
						else {
							this.hideNode(child_id);
						}
					}
				}
				
				if (default_node_id != null) {
					// there was a node without rules (indicating a default child)
					if (notMatched) {
						// show the default
						this.showNode(default_node_id);
					}
					else {
						// hide the default
						this.hideNode(default_node_id);
					}
				}
			}
			else {
				// hide the child nodes
				for (var i = 0; i < this._nodes_by_parent[node_id].length; i++) {
					var child_id = this._nodes_by_parent[node_id][i];
					this.hideNode(child_id);
				}
			}
		}
	}
	
};

/*
 * Checks the rules for the node of the given node id against the value. Returns
 * true if the node should be shown according to the rules
 */
ComplianceCalculator.prototype.checkNodeRule = function(node_id, value) {
	if (this._nodes[node_id]['rules'].length) {
		var res = true;
		
		for (var i = 0; i < this._nodes[node_id]['rules'].length; i++) {
			var cmp =  this._nodes[node_id]['rules'][i]['parent_check'];
			var chk_val = this._nodes[node_id]['rules'][i]['parent_check_value'];
			switch (cmp)
			{
				case "eq":
					res = (res && value == chk_val) ? true : false;
					break;
				case "lt":
					res = (res && value < chk_val) ? true : false;
					break;
				case "lte":
					res = (res && value <= chk_val) ? true : false;
					break;
				case "gt":
					res = (res && value > chk_val) ? true : false;
					break;
				case "gte":
					res = (res && value >= chk_val) ? true : false;
					break;
				default:
					res = false;
			}
		}
		return res;
	}
	else {
		// if there are no rules on the node, then it is considered to be the default child node and so should return true
		return true;
	}
};

/* go through the values of the form, and show the relevant form elements
 * and possibly outcome
 */
ComplianceCalculator.prototype.update = function update(node_id) 
{
	if (!node_id) {
		node_id = this._root_node_id;
	}
	this.checkNode(node_id);
}
	
/*
 * initialise a compliance calculator object for the side provided
 */
function OphCoTherapyapplication_ComplianceCalculator_init(side) {
	calc_obj = new ComplianceCalculator($('#OphCoTherapyapplication_ComplianceCalculator_' + side), {'side': side});
	$('#OphCoTherapyapplication_ComplianceCalculator_' + side).data('calc_obj', calc_obj);
	calc_obj.update();
}

/*
 * Should be called when the decision tree form changes. The form element that has changed
 * is used to then tell the ComplianceCalculator for the tree what point it should update from.
 */
function OphCoTherapyapplication_ComplianceCalculator_update(elem) {
	var node = elem.parents('.dt-node');
	var id = node.data('defn').id;
	var side = node.closest('.side').data('side');
	
	$('#OphCoTherapyapplication_ComplianceCalculator_' + side).data('calc_obj').update(id);
	
	OphCoTherapyapplication_ExceptionalCircumstances_check();
}

/*
 * determines if the treatment for the specified side requires the contraindications form or not.
 */
function _getContraindicationsFromSide(side) {
	if ($('#Element_OphCoTherapyapplication_PatientSuitability_' + side + '_treatment_id').is(':visible')) {
		var tr = $('#Element_OphCoTherapyapplication_PatientSuitability_' + side + '_treatment_id').val();
		var ci = null;
		$('#Element_OphCoTherapyapplication_PatientSuitability_' +side+'_treatment_id').find('option').each( function() {
			if ($(this).val() == tr) {
				if ($(this).data('contraindications')) {
					ci = true;
				}
				else {
					ci = false;
				}
				// finish
				return false;
			}
		});
		return ci;
	}
	// nothing for this side, so no need for contraindications.
	return false;
}

/* 
 * show or hide the contraindications form depending on if it is needed or not.
 */
function OphCoTherapyapplication_ContraIndications_check() {
	var lt = _getContraindicationsFromSide('left');
	var rt = _getContraindicationsFromSide('right');
	if (lt || rt) {
		$('.Element_OphCoTherapyapplication_RelativeContraindications').show();
		// enable form elements (in case they were disabled)
		$('.Element_OphCoTherapyapplication_RelativeContraindications').find('input, select, textarea').each(function() { $(this).removeAttr('disabled')});
	}
	else {
		$('.Element_OphCoTherapyapplication_RelativeContraindications').hide();
		// disable form elements so they are not submitted and saved
		$('.Element_OphCoTherapyapplication_RelativeContraindications').find('input, select, textarea').each(function() { $(this).attr('disabled', 'disabled')});
	}
}

// check whether the patient suitability elements should be shown for the given eye side
function OphCoTherapyapplication_PatientSuitability_check(side) {
	var el = $('#Element_OphCoTherapyapplication_Therapydiagnosis_' + side + '_diagnosis1_id');
	
	if (el.is(":visible") && el.val()) {
		var l2_data;
		el.find('option').each(function() {
			if ($(this).val() == el.val()) {
				l2_data = $(this).data('level2');
				return true;
			}
		});
		
		if (l2_data) {
			// need to update the list of options in the level 2 drop down
			var options = '<option>- Please Select -</option>';
			for (var i in l2_data) {
				options += '<option value="' + l2_data[i].id + '">' + l2_data[i].term + '</option>';
			}
			$('#Element_OphCoTherapyapplication_Therapydiagnosis_' + side + '_diagnosis2_id').html(options);
			$('#Element_OphCoTherapyapplication_Therapydiagnosis_' + side + '_diagnosis2_id').removeAttr('disabled')
			$('#' + side + '_diagnosis2_wrapper').removeClass('hidden');
		}
		else {
			$('#Element_OphCoTherapyapplication_Therapydiagnosis_' + side + '_diagnosis2_id').attr('disabled', 'disabled');
			$('#' + side + '_diagnosis2_wrapper').addClass('hidden');
		}
		showSplitElementSide('Element_OphCoTherapyapplication_PatientSuitability', side);
	}
	else {
		hideSplitElementSide('Element_OphCoTherapyapplication_PatientSuitability', side);
	}
	
	OphCoTherapyapplication_ExceptionalCircumstances_check(side);
}

function _isCompliant(side) {
	if ($('#nice_compliance_' + side).is(':visible')) {
		// this side is showing
		var compliant = $('#Element_OphCoTherapyapplication_PatientSuitability_' + side + '_nice_compliance').val();
		if (compliant.length == 0) {
			return null;
		}
		else if (compliant == '0') {
			return false;
		}
		else {
			return true;
		}
	}
	return null;
}

// check whether the exceptional circumstances elements should be shown for the given eye side
function OphCoTherapyapplication_ExceptionalCircumstances_check(side) {
	var compliant = _isCompliant(side);
	
	var other_side = 'right';
	if (side == 'right') {
		other_side = 'left';
	}
	
	if (compliant != null && !compliant) {
		showSplitElementSide('Element_OphCoTherapyapplication_ExceptionalCircumstances', side);
		// enable form elements (in case this is the first side to be shown)
		$('.Element_OphCoTherapyapplication_ExceptionalCircumstances').find('input, select, textarea').each(function() { $(this).removeAttr('disabled')});
	}
	else {
		hideSplitElementSide('Element_OphCoTherapyapplication_ExceptionalCircumstances', side);	
		// check if the other side is visible
		// if it isn't disable the form elements
		if ($('.Element_OphCoTherapyapplication_ExceptionalCircumstances').find('div.side.' + side).hasClass('inactive')) {
			$('.Element_OphCoTherapyapplication_ExceptionalCircumstances').find('input, select, textarea').each(function() { $(this).attr('disabled', 'disabled') });
		}
	}
}


function OphCoTherapyapplication_previntervention_getNextKey(side) {
	var keys = $('#event_content .Element_OphCoTherapyapplication_ExceptionalCircumstances .previousintervention').map(function(index, el) {
		return parseInt($(el).attr('data-key'));
	}).get();
	// ensure we start at zero
	keys.push(-1);
	return Math.max.apply(null, keys) + 1;
}

function OphCoTherapyapplication_addPrevintervention(side) {
	var template = $('#previntervention_template').html();
	var data = {
		"key" : OphCoTherapyapplication_previntervention_getNextKey(side),
		"side" : side,
	};
	var form = Mustache.render(template, data);
	var table = $('#event_content .Element_OphCoTherapyapplication_ExceptionalCircumstances .[data-side="' + side + '"] table');
	$('tbody', table).append(form);
	$("#Element_OphCoTherapyapplication_ExceptionalCircumstances_" + side + "_previnterventions_" + data.key + "_treatment_date").datepicker({
		'maxDate': 'today',
		'showAnim': 'fold',
		'dateFormat': nhs_date_format});
}


$(document).ready(function() {
	// standard stuff
	handleButton($('#et_save'),function() {
			});
	
	handleButton($('#et_cancel'),function(e) {
		if (m = window.location.href.match(/\/update\/[0-9]+/)) {
			window.location.href = window.location.href.replace('/update/','/view/');
		} else {
			window.location.href = baseUrl+'/patient/episodes/'+et_patient_id;
		}
		e.preventDefault();
	});

	handleButton($('#et_deleteevent'));

	handleButton($('#et_canceldelete'),function(e) {
		if (m = window.location.href.match(/\/delete\/[0-9]+/)) {
			window.location.href = window.location.href.replace('/delete/','/view/');
		} else {
			window.location.href = baseUrl+'/patient/episodes/'+et_patient_id;
		}
		e.preventDefault();
	});

	$('select.populate_textarea').unbind('change').change(function() {
		if ($(this).val() != '') {
			var cLass = $(this).parent().parent().parent().attr('class').match(/Element.*/);
			var el = $('#'+cLass+'_'+$(this).attr('id'));
			var currentText = el.text();
			var newText = $(this).children('option:selected').text();

			if (currentText.length == 0) {
				el.text(ucfirst(newText));
			} else {
				el.text(currentText+', '+newText);
			}
		}
	});
	
	$('.Element_OphCoTherapyapplication_Therapydiagnosis').delegate('#Element_OphCoTherapyapplication_Therapydiagnosis_right_diagnosis1_id, ' +
			'#Element_OphCoTherapyapplication_Therapydiagnosis_left_diagnosis1_id', 'change', function() {
		var side = getSplitElementSide($(this));
		
		OphCoTherapyapplication_PatientSuitability_check(side);
	})
	
	// handle treatment selection when editing
	$('#event_content').delegate('#Element_OphCoTherapyapplication_PatientSuitability_left_treatment_id, ' +
			'#Element_OphCoTherapyapplication_PatientSuitability_right_treatment_id', 'change', function() {
		var selected = $(this).val();
		var side = getSplitElementSide($(this));
		
		OphCoTherapyapplication_ContraIndications_check();
		
		$(this).find('option').each( function() {
			if ($(this).val() == selected) {
				// this is the option that has been switched to
				if ($(this).attr('data-treeid')) {
					var params = {
						'patient_id': OE_patient_id,
						'treatment_id': $(this).val(),
						'side': side
					};
					
					//TODO: check if there are any answers on a current tree
					// if there are, should confirm before blowing them away
					$.ajax({
						'type': 'GET',
						'url': decisiontree_url + '?' + $.param(params),
						'success': function(html) {
							if (html.length > 0) {
								$('#OphCoTherapyapplication_ComplianceCalculator_' + side).replaceWith(html);
								OphCoTherapyapplication_ComplianceCalculator_init(side);
								OphCoTherapyapplication_ExceptionalCircumstances_check(side);
							}
						}
					});
				}
				else {
					// TODO: reset the workflow to neutral?
				}
			}
		})
		
	});
	
	// various inputs that we need to react to changes on for the compliance calculator
	$('#nice_compliance_left, #nice_compliance_right').delegate('input, select', 'change', function() {
		OphCoTherapyapplication_ComplianceCalculator_update($(this));
		var side = getSplitElementSide($(this)); 
		OphCoTherapyapplication_ExceptionalCircumstances_check(side);
	});
	
	$('#nice_compliance_left, #nice_compliance_right').delegate('input', 'keyup', function() {
		OphCoTherapyapplication_ComplianceCalculator_update($(this));
		var side = getSplitElementSide($(this)); 
		OphCoTherapyapplication_ExceptionalCircumstances_check(side);
	});
	
	if ($('#Element_OphCoTherapyapplication_PatientSuitability_left_treatment_id').val()) {
		// there should be a tree to initialise given that a treatment has been chosen
		// TODO: work out what to do if the treatment is no longer available (i.e. we are editing a now redundant application)
		OphCoTherapyapplication_ComplianceCalculator_init('left');
		OphCoTherapyapplication_ContraIndications_check();
	}
	
	if ($('#Element_OphCoTherapyapplication_PatientSuitability_right_treatment_id').val()) {
		// there should be a tree to initialise given that a treatment has been chosen
		// TODO: work out what to do if the treatment is no longer available (i.e. we are editing a now redundant application)
		OphCoTherapyapplication_ComplianceCalculator_init('right');
		OphCoTherapyapplication_ContraIndications_check();
	}
	
	// show/hide the standard interventions element
	$('.Element_OphCoTherapyapplication_ExceptionalCircumstances').delegate('.standard_intervention_exists input', 'change', function() {
		var side = getSplitElementSide($(this));
		
		if ($(this).val() == '1') {
			
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_details').closest('.elementField').show();
		}
		else {
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_details').closest('.elementField').hide();
		}
	});
	
	$('.Element_OphCoTherapyapplication_ExceptionalCircumstances').delegate('.intervention input', 'change', function() {
		var side = getSplitElementSide($(this));
		
		if ($(this).val()) {
			if ($(this).data('description-label')) {
				$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_description').closest('.elementField').find('.label').text($(this).data('description-label'));
			}
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_description').closest('.elementField').show();
		}
		else {
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_description').closest('.elementField').hide();
		}
		
	});
	
	// Manage previous interventions in exceptional circumstances element
	$(this).delegate('#event_content .Element_OphCoTherapyapplication_ExceptionalCircumstances .removePrevintervention', 'click', function(e) {
		var block = $(this).closest('.data');
		$(this).closest('tr').remove();
		e.preventDefault();
	});

	$(this).delegate('#event_content .Element_OphCoTherapyapplication_ExceptionalCircumstances .addPrevintervention', 'click', function(e) {
		var side = getSplitElementSide($(this));
		OphCoTherapyapplication_addPrevintervention(side);
		e.preventDefault();
	});
	
	// show/hide the patient factors element
	$('.Element_OphCoTherapyapplication_ExceptionalCircumstances').delegate('.patient_factors input', 'change', function() {
		var side = getSplitElementSide($(this));
		
		if ($(this).val() == '1') {
			
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_patient_factor_details').closest('.elementField').show();
		}
		else {
			$('#Element_OphCoTherapyapplication_ExceptionalCircumstances_'+side+'_patient_factor_details').closest('.elementField').hide();
		}
	});
	
	// check whether we need to be showing the other elements
	OphCoTherapyapplication_PatientSuitability_check('left');
	OphCoTherapyapplication_PatientSuitability_check('right');
	OphCoTherapyapplication_ExceptionalCircumstances_check('left');
	OphCoTherapyapplication_ExceptionalCircumstances_check('right');
	
	// extend the removal behaviour for diagnosis to affect the dependent elements
	$(this).delegate('#event_content .side .activeForm a.removeSide', 'click', function(e) {
		side = getSplitElementSide($(this));
		var other_side = 'left';
		if (side == 'left') {
			other_side = 'right';
		}
		hideSplitElementSide('Element_OphCoTherapyapplication_PatientSuitability', side);
		hideSplitElementSide('Element_OphCoTherapyapplication_ExceptionalCircumstances', side);
		// if the other side has been revealed by this, need to check whether the dependent elements should also be shown.
		OphCoTherapyapplication_PatientSuitability_check(other_side);
		OphCoTherapyapplication_ContraIndications_check();
		OphCoTherapyapplication_ExceptionalCircumstances_check(other_side);
	});
	
	// extend the adding behaviour for diagnosis to affect dependent elements
	$(this).delegate('#event_content .side .inactiveForm a', 'click', function(e) {
		side = getSplitElementSide($(this));
		OphCoTherapyapplication_PatientSuitability_check(side);
		OphCoTherapyapplication_ContraIndications_check();
		OphCoTherapyapplication_ExceptionalCircumstances_check(side);
	});
	
});

function ucfirst(str) { str += ''; var f = str.charAt(0).toUpperCase(); return f + str.substr(1); }

function eDparameterListener(_drawing) {
	if (_drawing.selectedDoodle != null) {
		// handle event
	}
}
