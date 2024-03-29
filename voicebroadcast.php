<?php

require_once 'voicebroadcast.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function voicebroadcast_civicrm_config(&$config) {
  _voicebroadcast_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function voicebroadcast_civicrm_xmlMenu(&$files) {
  _voicebroadcast_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function voicebroadcast_civicrm_install() {
  return _voicebroadcast_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function voicebroadcast_civicrm_uninstall() {
  return _voicebroadcast_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function voicebroadcast_civicrm_enable() {
  return _voicebroadcast_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function voicebroadcast_civicrm_disable() {
  return _voicebroadcast_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function voicebroadcast_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _voicebroadcast_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function voicebroadcast_civicrm_managed(&$entities) {
  return _voicebroadcast_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function voicebroadcast_civicrm_caseTypes(&$caseTypes) {
  _voicebroadcast_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function voicebroadcast_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _voicebroadcast_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function voicebroadcast_civicrm_searchColumns($objectName, &$headers, &$rows, &$selector) {
  /* $header = array(); */
  /* $header = $headers[1]; */
  /* $headers[1] = $headers[2]; */
  /* $headers[2] = $header; */
}