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

<style>

input#delete    {ldelim}
  background:url({$deleteIcon}) !important;
  background-repeat: no-repeat;
  height:10px !important;
  border: 0 !important;
{rdelim}

</style>


<div class="crm-block crm-form-block crm-mailing-upload-form-block">
{include file="CRM/common/WizardHeader.tpl"}

<div id="help">
    {ts}You can either <strong>upload</strong> the voice file from your computer OR <strong>record</strong> the content on this screen.{/ts} {help id="content-intro"}
</div>

{include file="CRM/Mailing/Form/Count.tpl"}
{$form.voice_rec.html}
<table class="form-layout-compressed">
    <tr class="crm-mailing-upload-form-block-from_email_address"><td class="label">{$form.contact_id.label}</td>
        <td>{$form.contact_id.html} {help id ="id-from_email" isAdmin=$isAdmin}</td><td class="label">{$form.phone_number.label}</td>
        <td>{$form.phone_number.html}</td>
    </tr>
</table>

  <fieldset id="upload_id"><legend>{ts}Upload Content{/ts}</legend>
    <table class="form-layout-compressed">
    	<tr>
          <td>
              <div class="crm-attachment-wrapper crm-entity">
              {if $deleteURL}
	        {$deleteURL}
              {/if}
              </div>
          </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-textFile">	
	{if $deleteLink}
	<tr>
          <td class="label">Uploaded Voice Recording</td>
	  <td>
	    {$viewLink}   <input type="button" id="delete" title="Delete Voice Recording">
          </td>
        </tr>
	{/if}
            <td class="label">Record a voice message</td>
            <td>
		<input type="button" id="record" value="Record">  
		<span id="status"></span>
		<input type="button" id="stop" value="Stop">
		<input type="button" id="send" value="Save Voice Recording" onclick="return submitOnce();"><span id="voiceRecordFile" style="display:none">Playing back...</span>
</td>
        </tr>
	<tr><td></td>
            <td>
	        <b style="padding-left:110px;">OR</b>
            </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-textFile">
            <td class="label">{$form.voiceFile.label}</td>
            <td>{$form.voiceFile.html}<br />
                <span class="description">{ts}Browse to the <strong>Voice</strong> message file you have prepared for this mailing. (Only mp3 and wav files are allowed){/ts}</span>
            </td>
        </tr>
    </table>
  </fieldset> 


  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div><!-- / .crm-form-block -->

{literal}
<script type="text/javascript">


var deleteLink = '{/literal}{$deleteLink}{literal}';

cj("#delete").click(function(){
    cj.ajax({
      url: deleteLink, 
      success: function(result){
        CRM.alert(ts('Voice file has been deleted!'));
    }});
});


function submitOnce() {
  cj('#send').val('Uploading...');
}
// Phone numbers

var phno = '{/literal}{$phone_number_default}{literal}';

if (phno) {
 option = '<option value="'+ phno + '" selected="selected">' + phno + '</option>';
 cj('#phone_number').append(option);
}


var numbers = [];

function fn() {

var option = '';
for (i=0;i<numbers.length;i++){
   option += '<option value="'+ numbers[i] + '">' + numbers[i] + '</option>';
}
cj('#phone_number option[value!=""]').remove();
cj('#phone_number').append(option);
numbers = [];
}

cj('#contact_id').change(function () {

var cid = cj('#contact_id').val();

CRM.api3('Phone', 'get', {contact_id:cid})
  .done(function(result) {
    cj.each(result.values, function(key, value) {
      numbers.push(value.phone);
    });
    fn();
  });

});



// Recorder stuff

var recName = '{/literal}{$recName}{literal}';
var swfURL = '{/literal}{$swfURL}{literal}';
var uploadPath = '{/literal}{$uploadPath}{literal}';
cj("input[name='voice_rec']").val('');

cj.jRecorder(     
     { 
        host : recName,
        
        callback_started_recording:     function(){ callback_started(); },
        callback_stopped_recording:     function(){ callback_stopped(); },
        callback_activityLevel:          function(level){callback_activityLevel(level); },
        callback_activityTime:     function(time){callback_activityTime(time); },
        
        callback_finished_sending:     function(time){ callback_finished_sending() },


        swf_path: swfURL,
     
     }
   );

cj('#record').click(function(){       
    cj.jRecorder.record(30); //record up to 30 sec and stops automatically    
    cj(this).attr('value', 'Recording...'); 
   });

cj('#stop').click(function(){
    cj.jRecorder.stop(); 
    cj('#voiceRecordFile').show();    
    cj('#record').attr('value', 'Record'); 
   });

cj('#send').click(function(){
   cj.jRecorder.sendData();
    cj("input[name='voice_rec']").val(uploadPath + '.wav');
    cj('#voiceRecordFile').hide();
    setTimeout(function(){
        cj('#send').delay(500).val('Done!');
    }, 5000); 
    });

 function callback_finished() {
  cj('#status').text('Recording is finished');
   }         
                    
 function callback_started() {
  cj('#status').text('Recording is started');
   }

function callback_activityTime(time) {
    $('#time').html(time);
   }

   
</script>
{/literal}
