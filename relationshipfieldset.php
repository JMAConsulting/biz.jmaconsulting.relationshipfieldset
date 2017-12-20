<?php

require_once 'relationshipfieldset.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function relationshipfieldset_civicrm_config(&$config) {
  _relationshipfieldset_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function relationshipfieldset_civicrm_xmlMenu(&$files) {
  _relationshipfieldset_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function relationshipfieldset_civicrm_install() {
  _relationshipfieldset_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function relationshipfieldset_civicrm_uninstall() {
  _relationshipfieldset_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function relationshipfieldset_civicrm_enable() {
  _relationshipfieldset_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function relationshipfieldset_civicrm_disable() {
  _relationshipfieldset_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function relationshipfieldset_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _relationshipfieldset_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function relationshipfieldset_civicrm_managed(&$entities) {
  _relationshipfieldset_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function relationshipfieldset_civicrm_caseTypes(&$caseTypes) {
  _relationshipfieldset_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function relationshipfieldset_civicrm_angularModules(&$angularModules) {
  _relationshipfieldset_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function relationshipfieldset_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _relationshipfieldset_civix_civicrm_alterSettingsFolders($metaDataFolders);
}


/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 *
 */
function relationshipfieldset_civicrm_buildForm($formName, &$form) {
  if ('CRM_Contact_Form_Contact' == $formName
    && ($form->_action & CRM_Core_Action::ADD)
    && empty($form->_addBlockName)
  ) {
    CRM_Contact_Form_Edit_Relationship::buildQuickForm($form);
  }
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 *
 */
function relationshipfieldset_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ('CRM_Contact_Form_Contact' == $formName
    && ($form->_action & CRM_Core_Action::ADD)
  ) {
    CRM_Contact_Form_Edit_Relationship::formRule($fields, $errors);
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 */
function relationshipfieldset_civicrm_postProcess($formName, &$form) {
  if ('CRM_Contact_Form_Contact' == $formName && ($form->_action & CRM_Core_Action::ADD)) {
    CRM_Contact_Form_Edit_Relationship::postProcess($form);
  }
}
