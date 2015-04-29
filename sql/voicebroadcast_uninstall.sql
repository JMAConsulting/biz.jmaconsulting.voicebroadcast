DROP TABLE IF EXISTS `civicrm_voicebroadcast`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_calls`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_event_delivered`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_event_queue`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_group`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_job`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_lookup`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_mapping`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_plivo`;
DROP TABLE IF EXISTS `civicrm_voicebroadcast_recipients`;

DELETE FROM TABLE `civicrm_navigation` WHERE `name` = 'Voice Broadcasts';
DELETE FROM TABLE `civicrm_navigation` WHERE `name` = 'VoiceBroadcast Plivo Integration';
DELETE FROM TABLE `civicrm_navigation` WHERE `name` = 'New Voice Broadcast';
DELETE FROM TABLE `civicrm_navigation` WHERE `name` = 'Scheduled and Sent Broadcasts';

DELETE FROM TABLE `civicrm_option_group` WHERE `name` = 'file_type';
DELETE FROM TABLE `civicrm_option_value` WHERE `name` = 'Voice Broadcast';
DELETE FROM TABLE `civicrm_option_value` WHERE `name` = 'wav';
DELETE FROM TABLE `civicrm_option_value` WHERE `name` = 'mp3';
