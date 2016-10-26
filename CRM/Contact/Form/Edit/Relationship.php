<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * This class generates form components for relationship.
 */
class CRM_Contact_Form_Edit_Relationship {
  /**
   * Set default values for the form.
   */
  public static function setDefaultValues() {
    $defaults = array();
    $defaults['is_active'] = $defaults['is_current_employer'] = 1;
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public static function buildQuickForm(&$form) {

    // Select list
    $relationshipList = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $form->_contactType, FALSE, 'label', TRUE, $form->_contactSubType);

    // Metadata needed on clientside
    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    $jsData = array();
    // Get just what we need to keep the dom small
    $whatWeWant = array_flip(array('contact_type_a', 'contact_type_b', 'contact_sub_type_a', 'contact_sub_type_b'));
    $allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');
    foreach ($allRelationshipNames as $id => $vals) {
      if ($vals['name_a_b'] === 'Employee of') {
        $form->assign('employmentRelationship', $id);
      }
      if (isset($relationshipList["{$id}_a_b"]) || isset($relationshipList["{$id}_b_a"])) {
        $jsData[$id] = array_filter(array_intersect_key($allRelationshipNames[$id], $whatWeWant));
        // Add user-friendly placeholder
        foreach (array('a', 'b') as $x) {
          $type = !empty($jsData[$id]["contact_sub_type_$x"]) ? $jsData[$id]["contact_sub_type_$x"] : CRM_Utils_Array::value("contact_type_$x", $jsData[$id]);
          $jsData[$id]["placeholder_$x"] = $type ? ts('- select %1 -', array(strtolower($contactTypes[$type]['label']))) : ts('- select contact -');
        }
      }
    }
    $form->assign('relationshipData', $jsData);

    $form->add(
      'select',
      'relationship_type_id',
      ts('Relationship Type'),
      array('' => ts('- select -')) + $relationshipList,
      TRUE,
      array('class' => 'crm-select2 huge')
    );
    $contactField = $form->addEntityRef('related_contact_id', ts('Contact(s)'), array(
        'multiple' => TRUE,
        'create' => TRUE,
      ), TRUE);

    $form->add('advcheckbox', 'is_current_employer', $form->_contactType == 'Organization' ? ts('Current Employee') : ts('Current Employer'));

    $form->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'searchDate'));

    $form->add('advcheckbox', 'is_active', ts('Enabled?'));
    $form->add('checkbox', 'is_permission_a_b');
    $form->add('checkbox', 'is_permission_b_a');

    $form->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Relationship', 'description'));

    $defaults = self::setDefaultValues();
    $form->setDefaults($defaults);

    $editOptions = $form->getVar('_editOptions');
    $editOptions['Relationships'] = ts('Relationships');
    $form->setVar('_editOptions', $editOptions);
    $form->assign('editOptions', $editOptions);
  }

  /**
   * This function is called when the form is submitted.
   */
  public function postProcess() {
    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);

    // CRM-14612 - Don't use adv-checkbox as it interferes with the form js
    $params['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_a_b', $params, 0);
    $params['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_b_a', $params, 0);

    // action is taken depending upon the mode
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Relationship::del($this->_relationshipId);

      // CRM-15881 UPDATES
      // Since the line above nullifies the organization_name and employer_id fiels in the contact record, we need to reload all blocks to reflect this chage on the user interface.
      $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');

      return;
    }

    $relationshipTypeParts = explode('_', $params['relationship_type_id']);
    $params['relationship_type_id'] = $relationshipTypeParts[0];
    if (!$this->_rtype) {
      // Do we need to wrap this in an if - when is rtype used & is relationship_type_id always set then?
      $this->_rtype = $params['relationship_type_id'];
    }
    $params['contact_id_' .  $relationshipTypeParts[1]] = $this->_contactId;

    // Update mode (always single)
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $update = TRUE;
      $params['id'] = $this->_relationshipId;
      $ids['relationship'] = $this->_relationshipId;
      $relation = CRM_Contact_BAO_Relationship::getRelationshipByID($this->_relationshipId);
      if ($relation->contact_id_a == $this->_contactId) {
        // I couldn't replicate this path in testing. See below.
        $params['contact_id_a'] = $this->_contactId;
        $params['contact_id_b'] = array($params['related_contact_id']);
        $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $relationshipTypeParts[1]);
        $relationshipIds = $outcome['relationship_ids'];
      }
      else {
        // The only reason we have changed this to use the api & not the above is that this was broken.
        // Recommend extracting all of update into a function that uses the api
        // and ensuring api / bao take care of 'other stuff' in this form
        // the contact_id_a & b can't be changed on this form so don't really need setting.
        $params['contact_id_b'] = $this->_contactId;
        $params['contact_id_a'] = $params['related_contact_id'];
        $result = civicrm_api3('relationship', 'create', $params);
        $relationshipIds = array($result['id']);
      }
      $ids['contactTarget'] = ($relation->contact_id_a == $this->_contactId) ? $relation->contact_id_b : $relation->contact_id_a;

      // @todo this belongs in the BAO.
      if ($this->_isCurrentEmployer) {
        // if relationship type changes, relationship is disabled, or "current employer" is unchecked,
        // clear the current employer. CRM-3235.
        $relChanged = $params['relationship_type_id'] != $this->_values['relationship_type_id'];
        if (!$params['is_active'] || !$params['is_current_employer'] || $relChanged) {

          // CRM-15881 UPDATES
          // If not is_active then is_current_employer needs to be set false as well! Logically a contact cannot be a current employee of a disabled employer relationship.
          // If this is not done, then the below process will go ahead and disable the organization_name and employer_id fields in the contact record (which is what is wanted) but then further down will be re-enabled becuase is_current_employer is not false, therefore undoing what was done correctly.
          if (!$params['is_active']) {
            $params['is_current_employer'] = FALSE;
          }

          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($this->_values['contact_id_a']);
          // Refresh contact summary if in ajax mode
          $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');
        }
      }
      if (empty($outcome['saved']) && !empty($update)) {
        $outcome['saved'] = $update;
      }
      $this->setMessage($outcome);
    }
    // Create mode (could be 1 or more relationships)
    else {
      $params['contact_id_' .  $relationshipTypeParts[2]] = explode(',', $params['related_contact_id']);
      $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $relationshipTypeParts[1]);
      $relationshipIds = $outcome['relationship_ids'];
      if (empty($outcome['saved']) && !empty($update)) {
        $outcome['saved'] = $update;
      }
      $this->setMessage($outcome);
    }

    // if this is called from case view,
    //create an activity for case role removal.CRM-4480
    // @todo this belongs in the BAO.
    if ($this->_caseId) {
      CRM_Case_BAO_Case::createCaseRoleActivity($this->_caseId, $relationshipIds, $params['contact_check'], $this->_contactId);
    }

    // Save notes
    // @todo this belongs in the BAO.
    if ($this->_action & CRM_Core_Action::UPDATE || $params['note']) {
      foreach ($relationshipIds as $id) {
        $noteParams = array(
          'entity_id' => $id,
          'entity_table' => 'civicrm_relationship',
        );
        $existing = civicrm_api3('note', 'get', $noteParams);
        if (!empty($existing['id'])) {
          $noteParams['id'] = $existing['id'];
        }
        $noteParams['note'] = $params['note'];
        $noteParams['contact_id'] = $this->_contactId;
        if (!empty($existing['id']) || $params['note']) {
          $action = $params['note'] ? 'create' : 'delete';
          civicrm_api3('note', $action, $noteParams);
        }
      }
    }

    $params['relationship_ids'] = $relationshipIds;

    // Refresh contact tabs which might have been affected
    $this->ajaxResponse['updateTabs'] = array(
      '#tab_member' => CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactId),
      '#tab_contribute' => CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId),
    );

    // Set current employee/employer relationship, CRM-3532
    if ($params['is_current_employer'] && $this->_allRelationshipNames[$params['relationship_type_id']]["name_a_b"] ==
    'Employee of') {
      $employerParams = array();
      foreach ($relationshipIds as $id) {
        // Fixme this is dumb why do we have to look this up again?
        $rel = CRM_Contact_BAO_Relationship::getRelationshipByID($id);
        $employerParams[$rel->contact_id_a] = $rel->contact_id_b;
      }
      // @todo this belongs in the BAO.
      CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($employerParams);
      // Refresh contact summary if in ajax mode
      $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');
    }
  }

  /**
   * validation.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @param array $errors
   *
   */
  public static function formRule($params, &$errors) {
    // check start and end date
    if (!empty($params['start_date']) && !empty($params['end_date'])) {
      $start_date = CRM_Utils_Date::format(CRM_Utils_Array::value('start_date', $params));
      $end_date = CRM_Utils_Date::format(CRM_Utils_Array::value('end_date', $params));
      if ($start_date && $end_date && (int ) $end_date < (int ) $start_date) {
        $errors['end_date'] = ts('The relationship end date cannot be prior to the start date.');
      }
    }
  }

}
