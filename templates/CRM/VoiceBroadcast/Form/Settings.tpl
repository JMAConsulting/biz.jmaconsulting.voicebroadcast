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

<script  type="text/javascript">
{literal}

CRM.$(function($) {
  // hide all the selects that contains only one option
  $('.crm-message-select select').each(function (){
    if ($(this).find('option').size() == 1) {
      $(this).parent().parent().hide();
    }
  });
  if (!$('#override_verp').prop('checked')){
    $('.crm-mailing-settings-form-block-forward_replies,.crm-mailing-settings-form-block-auto_responder').hide();
  }
  $('#override_verp').click(function(){
      $('.crm-mailing-settings-form-block-forward_replies,.crm-mailing-settings-form-block-auto_responder').toggle();
       if (!$('#override_verp').prop('checked')) {
             $('#forward_replies, #auto_responder').prop('checked', false);
           }
    });

});
{/literal}
</script>

<div class="crm-block crm-form-block crm-mailing-settings-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}These settings control tracking and responses to recipient actions. The number of recipients selected to receive this mailing is shown in the box to the right. If this count doesn't match your expectations, click <strong>Previous</strong> to review your selection(s).{/ts}
</div>
{include file="CRM/Mailing/Form/Count.tpl"}
<div class="crm-block crm-form-block crm-mailing-settings-form-block">
  <fieldset><legend>{ts}Tracking{/ts}</legend>
    <table class="form-layout">
        <tr class="crm-mailing-settings-form-block-url_tracking">
            <td class="label">{$form.is_track_call_disposition.label}</td>
            <td>
                {$form.is_track_call_disposition.html}<span class="description">{ts}Call disposition (Not in Service, No Answer, Left Message on machine, Delivered){/ts}</span>
            </td>
        </tr>
        <tr class="crm-mailing-settings-form-block-open_tracking">
            <td class="label">{$form.is_track_call_duration.label}</td>
            <td>{$form.is_track_call_duration.html}
                <span class="description">{ts}Duration of Call.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-mailing-settings-form-block-open_tracking">
            <td class="label">{$form.is_track_call_cost.label}</td>
            <td>{$form.is_track_call_cost.html}
                <span class="description">{ts}Cost of Call.{/ts}</span>
            </td>
        </tr>
   </table>
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
</div>

