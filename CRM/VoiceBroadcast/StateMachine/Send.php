<?php


require_once 'CRM/Core/StateMachine.php';

class CRM_VoiceBroadcast_StateMachine_Send extends CRM_Core_StateMachine
{
    /**
     * Contract State Machine
     *
     * @param $controller
     * @param $action
     */
    function __construct($controller, $action = CRM_Core_Action::NONE)
    {
        parent::__construct($controller, $action);

        $this->_pages = array('CRM_VoiceBroadcast_Form_Group'           => NULL,
                              'CRM_VoiceBroadcast_Form_Settings'        => NULL,
                              'CRM_VoiceBroadcast_Form_Upload'          => NULL,
                              'CRM_VoiceBroadcast_Form_Test'            => NULL,
                              'CRM_VoiceBroadcast_Form_Schedule'        => NULL,
                             );

//        if (CRM_Mailing_Info::workflowEnabled()) {
//
////          if (CRM_Core_Permission::check('schedule mailings')) {
////            $this->_pages['CRM_Mailing_Form_Schedule'] = NULL;
////          }
////
////          if (CRM_Core_Permission::check('approve mailings')) {
////            $this->_pages['CRM_Mailing_Form_Approve'] = NULL;
////          }
////
////        } else {
//          $this->_pages['CRM_Mailing_Form_Schedule'] = NULL;
//        }


        $this->addSequentialPages($this->_pages, $action);
    }
}
