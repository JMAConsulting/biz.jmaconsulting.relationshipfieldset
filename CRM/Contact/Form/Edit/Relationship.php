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
  public static function postProcess($form) {
    // Store the submitted values in an array.
    $name = $form->getVar('_name');
    $submittedValues = $form->controller->exportValues($name);

    $requiredValues = array(
      'is_permission_a_b',
      'is_permission_b_a',
      'relationship_type_id',
      'related_contact_id',
      'is_current_employer',
      'start_date',
      'end_date',
      'is_active',
      'description',
      'relationship_note',
    );
    $params = array();
    foreach ($requiredValues as $values) {
      $params[$values] = CRM_Utils_Array::value($values, $submittedValues);
    }

    // CRM-14612 - Don't use adv-checkbox as it interferes with the form js
    $params['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_a_b', $params, 0);
    $params['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_b_a', $params, 0);

    $relationshipTypeParts = explode('_', $params['relationship_type_id']);
    $params['relationship_type_id'] = $relationshipTypeParts[0];
    $params['contact_id_' .  $relationshipTypeParts[1]] = $form->_contactId;

    $params['contact_id_' .  $relationshipTypeParts[2]] = explode(',', $params['related_contact_id']);
    $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $relationshipTypeParts[1]);
    $relationshipIds = $outcome['relationship_ids'];

    $params['relationship_ids'] = $relationshipIds;

    // Set current employee/employer relationship, CRM-3532
    $allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');
    if ($params['is_current_employer'] && $allRelationshipNames[$params['relationship_type_id']]["name_a_b"] ==
        'Employee of') {
      $employerParams = array();
      foreach ($relationshipIds as $id) {
        // Fixme this is dumb why do we have to look this up again?
        $rel = CRM_Contact_BAO_Relationship::getRelationshipByID($id);
        $employerParams[$rel->contact_id_a] = $rel->contact_id_b;
      }
      // @todo this belongs in the BAO.
      CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($employerParams);
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
      $start_date = strtotime($params['start_date']);
      $end_date = strtotime($params['end_date']);
      if ($start_date > $end_date) {
        $errors['end_date'] = ts('The relationship end date cannot be prior to the start date.');
      }
    }
  }

}
