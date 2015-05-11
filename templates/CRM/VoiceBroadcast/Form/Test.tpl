{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
*}
<div class="crm-block crm-form-block crm-mailing-test-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}It's a good idea to test your voice broadcast by sending it to yourself and/or a selected group of people in your organization.{/ts}
</div>

{include file="CRM/Mailing/Form/Count.tpl"}

<fieldset>
  <legend>Test Robo Call</legend>
  <table class="form-layout">
    <tr class="crm-voicebroadcast-test-form-block-test_phone"><td class="label">{$form.test_phone.label}</td><td>{$form.test_phone.html}</td></tr>
    <tr class="crm-voicebroadcast-test-form-block-test_group"><td class="label">{$form.test_group.label}</td><td>{$form.test_group.html}</td></tr>
    <tr><td></td><td>{$form.sendtest.html}</td>  
  </table>
</fieldset>

<div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
         
        {ts}Listen to Message{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <table class="form-layout-compressed">
        <tr class="crm-mailing-listen-form-block-textFile">	
            <td class="label"></td>
            <td>
<div id="jquery_jplayer_1" class="jp-jplayer"></div>
<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">
  <div class="jp-type-single">
    <div class="jp-gui jp-interface">
      <div class="jp-controls">
        <button class="jp-play" role="button" tabindex="0">play</button>
        <button class="jp-stop" role="button" tabindex="0">stop</button>
      </div>
      <div class="jp-progress">
        <div class="jp-seek-bar">
          <div class="jp-play-bar"></div>
        </div>
      </div>
      <div class="jp-volume-controls">
        <button class="jp-mute" role="button" tabindex="0">mute</button>
        <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
      </div>
      <div class="jp-time-holder">
        <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
        <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
        <div class="jp-toggles">
          <button class="jp-repeat" role="button" tabindex="0">repeat</button>
        </div>
      </div>
    </div>
    <div class="jp-details">
      <div class="jp-title" aria-label="title">&nbsp;</div>
    </div>
    <div class="jp-no-solution">
      <span>Update Required</span>
      To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
    </div>
  </div>
</div>
	    </td>	
        </tr>
    </table>
      </div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->    

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
    
</div>
{literal}
<script type="text/javascript">

</script>
{/literal}
