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

  public static $_blockID = NULL;

  /**
   * Set default values for the form.
   */
  public static function setDefaultValues() {
    $defaults = array();
    $blockId = self::$_blockID;
    $defaults["relationships[$blockId][is_active]"] = $defaults["relationships[$blockId][is_current_employer]"] = 1;
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public static function buildQuickForm(&$form) {

    self::$_blockID = $blockId = ($form->get('Relationship_Block_Count')) ? $form->get('Relationship_Block_Count') : 1;

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
      "relationships[$blockId][relationship_type_id]",
      ts('Relationship Type'),
      array('' => ts('- select -')) + $relationshipList,
      FALSE,
      array('class' => 'crm-select2 huge')
    );
    $contactField = $form->addEntityRef("relationships[$blockId][related_contact_id]", ts('Contact(s)'), array(
        'multiple' => TRUE,
        'create' => TRUE,
      ), FALSE);

    $form->add('advcheckbox', "relationships[$blockId][is_current_employer]", $form->_contactType == 'Organization' ? ts('Current Employee') : ts('Current Employer'));

    $form->addDate("relationships[$blockId][start_date]", ts('Start Date'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate("relationships[$blockId][end_date]", ts('End Date'), FALSE, array('formatType' => 'searchDate'));

    $form->add('advcheckbox', "relationships[$blockId][is_active]", ts('Enabled?'));
    $form->add('checkbox', "relationships[$blockId][is_permission_a_b]");
    $form->add('checkbox', "relationships[$blockId][is_permission_b_a]");

    $form->add('text', "relationships[$blockId][description]", ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Relationship', 'description'));

    $form->applyFilter('__ALL__', 'trim');
    $form->add('textarea', "relationships[$blockId][relationship_note]", ts('Notes'), array('cols' => '60', 'rows' => '3'));
    $defaults = self::setDefaultValues();
    $form->setDefaults($defaults);

    $editOptions = $form->getVar('_editOptions');
    $editOptions['Relationship'] = ts('Relationships');
    $form->setVar('_editOptions', $editOptions);
    $form->assign('editOptions', $editOptions);
    $form->assign('relblockId', $blockId);
  }

  /**
   * This function is called when the form is submitted.
   */
  public static function postProcess($form) {
    // Store the submitted values in an array.
    $submitValues = $form->_submitValues;
    self::upload($form->_submitFiles, $submitValues);
    foreach ($submitValues['relationships'] as $params) {
      if (empty($params['relationship_type_id'])) {
        continue;
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

      // Add Notes
      if (!empty($params['relationship_note'])) {
        foreach ($relationshipIds as $id) {
          $noteParams = array(
            'entity_id' => $id,
            'entity_table' => 'civicrm_relationship',
            'contact_id' => $form->_contactId,
            'note' => $params['relationship_note'],
          );
          civicrm_api3('note', 'create', $noteParams);
        }
      }
    }
  }

  /**
   * validation.
   *
   * @param array $submitValues
   *   (reference ) an assoc array of name/value pairs.
   *
   * @param array $errors
   *
   */
  public static function formRule($submitValues, &$errors) {
    // check start and end date
    foreach ($submitValues['relationships'] as $key => $params) {
      if (empty($params['relationship_type_id'])) {
        continue;
      }
      if (empty($params['related_contact_id'])) {
        $errors["relationships[$key][related_contact_id]"] = ts('Please Select Contact(s).');
      }
      if (!empty($params['start_date']) && !empty($params['end_date'])) {
        $start_date = strtotime($params['start_date']);
        $end_date = strtotime($params['end_date']);
        if ($start_date > $end_date) {
          $errors["relationships[$key][end_date]"] = ts('The relationship end date cannot be prior to the start date.');
        }
      }
    }
  }

  /**
   * Upload and move the file if valid to the uploaded directory.
   * @param array $submitFiles
   * @param array $data
   *
   */
  public static function upload($submitFiles, &$data) {
    if (empty($submitFiles['relationships'])) {
      return;
    }
    $config = CRM_Core_Config::singleton();
    $uploadDir = $config->customFileUploadDir;
    $element = new HTML_QuickForm_file();
    foreach ($submitFiles['relationships']['name'] as $blockID => $customField) {
      foreach ($customField as $customFieldName => $fileName) {
        if (empty($fileName)) {
          continue;
        }
        $newName = CRM_Utils_File::makeFileName($fileName);
        $element->_value['tmp_name'] = $submitFiles['relationships']['tmp_name'][$blockID][$customFieldName];
        $status = $element->moveUploadedFile($uploadDir, $newName);
        if (!$status) {
          CRM_Core_Error::statusBounce(ts('We could not move the uploaded file %1 to the upload directory %2. Please verify that the \'Temporary Files\' setting points to a valid path which is writable by your web server.', array(
            1 => $fileName,
            2 => $uploadDir,
          )));
        }
        $data['relationships'][$blockID][$customFieldName] = array(
          'name' => $uploadDir . $newName,
          'type' => $submitFiles['relationships']['type'][$blockID][$customFieldName],
        );
      }
    }
  }

}
