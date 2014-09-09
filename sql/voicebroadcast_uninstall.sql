-- /*******************************************************
-- *
-- * voice Extension
-- *
-- * Eftakhairul Islam <eftakhairul@gmail.com>
-- *******************************************************/

DROP TABLE IF EXISTS `civicrm_voice_braodcast_group`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_call`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_job`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_recipients`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_response`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_spool`;

-- Delete the row from civicrm cron job
DELETE FROM `civicrm`.`civicrm_job` WHERE `civicrm_job`.`api_action` = 'processvoicebroadcast' AND `civicrm_job`.`api_entity` = 'job';