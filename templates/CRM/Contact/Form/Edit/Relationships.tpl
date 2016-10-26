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
<div class="crm-accordion-wrapper crm-relationships-accordion collapsed">
  <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
  <div id="relationships" class="crm-accordion-body">
    <div class="crm-block crm-form-block crm-relationship-form-block">
      <table class="form-layout-compressed">
        <tr class="crm-relationship-form-block-relationship_type_id">
          <td class="label">{$form.relationship_type_id.label}</td>
          <td>{$form.relationship_type_id.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-related_contact_id">
          <td class="label">{$form.related_contact_id.label}</td>
          <td>{$form.related_contact_id.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-is_current_employer" style="display:none;">
          <td class="label">{$form.is_current_employer.label}</td>
          <td>{$form.is_current_employer.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-start_date">
          <td class="label">{$form.start_date.label}</td>
          <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}<span>{$form.end_date.label} {include file="CRM/common/jcalendar.tpl" elementName=end_date}</span><br />
            <span class="description">{ts}If this relationship has start and/or end dates, specify them here.{/ts}</span></td>
        </tr>
        <tr class="crm-relationship-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-is_permission_a_b">
          {capture assign="contact_b"}{ts}selected contact(s){/ts}{/capture}
          {capture assign="display_name_a"}{ts}This new Contact{/ts}{/capture}
          <td class="label"><label>{ts}Permissions{/ts}</label></td>
          <td>
            {$form.is_permission_a_b.html}
            {ts 1=$display_name_a 2=$contact_b}<strong>%1</strong> can view and update information about %2.{/ts}
          </td>
        </tr>
        <tr class="crm-relationship-form-block-is_permission_b_a">
          <td class="label"></td>
          <td>
            {$form.is_permission_b_a.html}
            {ts 1=$contact_b|ucfirst 2=$display_name_a}<strong>%1</strong> can view and update information about %2.{/ts}
          </td>
        </tr>
        <tr class="crm-relationship-form-block-is_active">
          <td class="label">{$form.is_active.label}</td>
          <td>{$form.is_active.html}</td>
        </tr>
      </table>
      <div id="custom_group_0_1"></div>
      <div class="spacer"></div>
    </div>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{include file="CRM/common/customData.tpl" includeWysiwygEditor=TRUE}

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    var
      $form = $("form.{/literal}{$form.formClass}{literal}"),
      relationshipData = {/literal}{$relationshipData|@json_encode}{literal};
    $('[name=relationship_type_id]', $form).change(function() {
    var
      val = $(this).val(),
      $contactField = $('#related_contact_id[type=text]', $form);
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
        $('.crm-relationship-form-block-is_current_employer', $form).toggle(rType === {/literal}'{$employmentRelationship}'{literal});

        // Swap the permission checkboxes to match selected relationship direction
        $('#is_permission_a_b', $form).attr('name', 'is_permission_' + source + '_' + target);
        $('#is_permission_b_a', $form).attr('name', 'is_permission_' + target + '_' + source);
	 CRM.buildCustomData('Relationship', rType, false, 0, 0, true);
      }
    }).change();
  });
  {/literal}
</script>
