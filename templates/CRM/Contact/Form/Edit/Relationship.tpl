{*
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
*}
{if $title and $className eq 'CRM_Contact_Form_Contact'}
<div id="relationship" class="crm-accordion-wrapper crm-relationships-accordion collapsed">
  <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body" id="relationshipBlock">
{/if}
    <div id="Relationship_Block_{$relblockId}" class="crm-relationship_{$relblockId}">
      <div class="crm-block crm-form-block crm-relationship-form-block">
        {if $relblockId gt 1}<fieldset><legend>{ts}Add Relationship{/ts}</legend>{/if}
        <table class="form-layout-compressed">
          <tr class="crm-relationship-form-block-relationship_type_id">
            <td class="label">{$form.relationships.$relblockId.relationship_type_id.label}</td>
            <td>{$form.relationships.$relblockId.relationship_type_id.html}</td>
          </tr>
          <tr class="crm-relationship-form-block-related_contact_id">
            <td class="label">{$form.relationships.$relblockId.related_contact_id.label}</td>
            <td>{$form.relationships.$relblockId.related_contact_id.html}</td>
          </tr>
          <tr class="crm-relationship-form-block-is_current_employer-{$relblockId}" style="display:none;">
            <td class="label">{$form.relationships.$relblockId.is_current_employer.label}</td>
            <td>{$form.relationships.$relblockId.is_current_employer.html}</td>
          </tr>
          <tr class="crm-relationship-form-block-start_date">
            <td class="label">{$form.relationships.$relblockId.start_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date blockId=$relblockId blockSection='relationships'}<span>{$form.relationships.$relblockId.end_date.label} {include file="CRM/common/jcalendar.tpl" elementName=end_date blockId=$relblockId blockSection='relationships'}</span><br />
              <span class="description">{ts}If this relationship has start and/or end dates, specify themss here.{/ts}</span></td>
          </tr>
          <tr class="crm-relationship-form-block-description">
            <td class="label">{$form.relationships.$relblockId.description.label}</td>
            <td>{$form.relationships.$relblockId.description.html}</td>
          </tr>
          <tr class="crm-relationship-form-block-note">
            <td class="label">{$form.relationships.$relblockId.relationship_note.label}</td>
            <td>{$form.relationships.$relblockId.relationship_note.html}</td>
          </tr>
          <tr class="crm-relationship-form-block-is_permission_a_b">
            {capture assign="contact_b"}{ts}selected contact(s){/ts}{/capture}
            {capture assign="display_name_a"}{ts}This new Contact{/ts}{/capture}
            <td class="label"><label>{ts}Permissions{/ts}</label></td>
            <td>
              {$form.relationships.$relblockId.is_permission_a_b.html}
              {ts 1=$display_name_a 2=$contact_b}<strong>%1</strong> can view and update information about %2.{/ts}
            </td>
          </tr>
          <tr class="crm-relationship-form-block-is_permission_b_a">
            <td class="label"></td>
            <td>
              {$form.relationships.$relblockId.is_permission_b_a.html}
              {ts 1=$contact_b|ucfirst 2=$display_name_a}<strong>%1</strong> can view and update information about %2.{/ts}
            </td>
          </tr>
          <tr class="crm-relationship-form-block-is_active">
            <td class="label">{$form.relationships.$relblockId.is_active.label}</td>
            <td>{$form.relationships.$relblockId.is_active.html}</td>
          </tr>
        </table>
        <div id="custom_group_0_{$relblockId}"></div>
        <div class="spacer"></div>
        <div id="addMoreRelationship{$relblockId}" class="crm-add-address-wrapper">
      <a href="#" class="button" onclick="buildAdditionalBlocks( 'Relationship', '{$className}	' );return false;"><span><div class="icon ui-icon-circle-plus"></div>{ts}Another Relationship{/ts}</span></a>
        </div>
      </div>
    </div>
{if $title and $className eq 'CRM_Contact_Form_Contact'}
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{/if}
{include file="CRM/common/customData.tpl" includeWysiwygEditor=TRUE}

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    var
      $form = $("form.{/literal}{$form.formClass}{literal}"),
      relationshipData = {/literal}{$relationshipData|@json_encode}{literal};
    $('[name="relationships[{/literal}{$relblockId}{literal}][relationship_type_id]"]', $form).change(function() {
    var
      val = $(this).val(),
      $contactField = $('#relationships_{/literal}{$relblockId}{literal}_related_contact_id[type=text]', $form);
    if (!val && $contactField.length) {
      $contactField
        .prop('disabled', true)
        .attr('placeholder', {/literal}'{ts escape='js'}- first select relationship type -{/ts}'{literal})
        .change();
    }
    else if (val) {
      var
        pieces = val.split('_'),
        rType = pieces[0],
        source = pieces[1], // a or b
        target = pieces[2], // b or a
        contact_type = relationshipData[rType]['contact_type_' + target],
        contact_sub_type = relationshipData[rType]['contact_sub_type_' + target];
        // ContactField only exists for ADD action, not update
        if ($contactField.length) {
          var api = {params: {}};
          if (contact_type) {
            api.params.contact_type = contact_type;
          }
          if (contact_sub_type) {
            api.params.contact_sub_type = contact_sub_type;
          }
          $contactField
            .val('')
            .prop('disabled', false)
            .data('api-params', api)
            .data('user-filter', {})
            .attr('placeholder', relationshipData[rType]['placeholder_' + target])
            .change();
        }
        // Show/hide employer field
        $('.crm-relationship-form-block-is_current_employer-{/literal}{$relblockId}{literal}', $form).toggle(rType === {/literal}'{$employmentRelationship}'{literal});

        $('#Relationship_Block_{/literal}{$relblockId}{literal} .crm-relationship-form-block-is_permission_a_b input', $form).attr('name', 'relationships[{/literal}{$relblockId}{literal}][is_permission_' + source + '_' + target + ']');
        $('#Relationship_Block_{/literal}{$relblockId}{literal} .crm-relationship-form-block-is_permission_b_a input', $form).attr('name', 'relationships[{/literal}{$relblockId}{literal}][is_permission_' + target + '_' + source + ']');
	CRM.buildCustomData('Relationship', rType, false, {/literal}{$relblockId}{literal}, 0, true);
      }
    }).change();
    $( document ).ajaxComplete(function(event, xhr, settings) {
      var str = settings.url;
      var getVar = str.split("cgcount=");
      var getVar = getVar[1].split("&");
      if (str.indexOf("civicrm/custom?type=Relationship&subType=") >= 0
        && (getVar[0]-1) == {/literal}{$relblockId}{literal}
      ) {
        var eleName = '';
	var elementName = 'relationships[{/literal}{$relblockId}{literal}]';
	var stringIndex;
	$.each(['input', 'select', 'textarea'], function (index, value) {
	  $("#custom_group_0_{/literal}{$relblockId}{literal} " + value).each(function () {
	    eleName = $(this).attr('name');
	    if ($(this).attr('type') == 'checkbox'
              || ($(this).is('select') && eleName.indexOf("[") > -1)
            ) {
	      stringIndex = eleName.indexOf("[");
	      eleName = '[' + eleName.slice(0, stringIndex) + ']' + eleName.slice(stringIndex);
	    }
	    else {
	      eleName = '[' + eleName + ']';
	    }
	    $(this).attr('name', elementName + eleName);
          });
        });
      }
    });
  });
  {/literal}
</script>
