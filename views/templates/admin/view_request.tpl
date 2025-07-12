<style>
    .panel {
        margin-bottom: 20px;
    }

    .panel-heading {
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
    }

    .panel-body {
        padding: 15px;
    }

    .panel p {
        margin: 0 0 10px;
        font-size: 1rem;
    }

    .panel p strong {
        display: inline-block;
        width: 25%;
    }
    .message-content {
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>

{block name="content"}
<div class="row">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Customer Information' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <p>
                    <strong>{l s='Name' d='Modules.Sj4websavform.Admin'}</strong> {$displayData.firstname.value} {$displayData.lastname.value}
                </p>
                <p><strong>{$displayData.email.label}</strong> {$displayData.email.value}</p>
                <p><strong>{$displayData.phone.label}</strong> {$displayData.phone.value}</p>
                <p>
                    <strong>{$displayData.intervention_address.label}</strong> {$displayData.intervention_address.value}
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Client Message' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <p><strong>{$displayData.subject.label}</strong> {$displayData.subject.value}</p>
                <p><strong>{$displayData.message.label}</strong></p>
                <div class="message-content">
                    <p>{$displayData.message.value|nl2br}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Request Information' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <p>
                    <strong>{$displayData.id_order.label}</strong>
                    {if $displayData.id_order.value > 0}
                        <a href="{$link->getAdminLink('AdminOrders', true, [], ['id_order' => $displayData.id_order.value])}">{$displayData.id_order.value}</a>
                    {else}
                        N/A
                    {/if}
                </p>
                <p><strong>{$displayData.order_reference.label}</strong> {if $displayData.order_reference.value != ''}{$displayData.order_reference.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.product_types.label}</strong> {if $displayData.product_types.value != ''}{$displayData.product_types.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.nature.label}</strong> {if $displayData.nature.value != ''}{$displayData.nature.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.nature_other.label}</strong> {if $displayData.nature_other.value != ''}{$displayData.nature_other.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.delai.label}</strong> {if $displayData.delai.value != ''}{$displayData.delai.value}{else}N/A{/if}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-heading">
                <p>{$displayData.attachments.label}</p>
            </div>
            <div class="panel-body">
                {if $displayData.attachments.value|@json_decode}
                    <div class="row">
                        {foreach $displayData.attachments.value|@json_decode as $file}
                            <div class="col-md-3 text-center">
                                <a href="{$module_dir}uploads/{$file}" target="_blank">
                                    <img src="{$module_dir}uploads/{$file}" class="img-thumbnail"
                                         style="max-width:100px;">
                                </a>
                                <p><a href="{$module_dir}uploads/{$file}" download>{$file}</a></p>
                            </div>
                        {/foreach}
                    </div>
                {else}
                    {l s='No attachments' d='Modules.Sj4websavform.Admin'}
                {/if}
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="panel mt-4">
            <div class="panel-heading">
                <p>{l s='Request Details' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <table class="table">
                    {foreach from=$displayData item=row}
                        {if $row.field == 'attachments'}
                            <tr>
                                <th>{$row.label}</th>
                                <td>
                                    {if $row.value|@json_decode}
                                        <div class="row">
                                            {foreach $row.value|@json_decode as $file}
                                                <div class="col-md-3 text-center">
                                                    <a href="{$module_dir}uploads/{$file}" target="_blank">
                                                        <img src="{$module_dir}uploads/{$file}" class="img-thumbnail"
                                                             style="max-width:100px;">
                                                    </a>
                                                    <p><a href="{$module_dir}uploads/{$file}" download>{$file}</a></p>
                                                </div>
                                            {/foreach}
                                        </div>
                                    {else}
                                        {l s='No attachments' mod='sj4websavform'}
                                    {/if}
                                </td>
                            </tr>
                        {elseif $row.field == 'message'}
                            {* skip message for later in card *}
                        {elseif $row.field == 'subject'}
                            {* skip subject for later in card *}
                        {elseif $row.field == 'firstname' || $row.field == 'lastname' || $row.field == 'email' || $row.field == 'phone' || $row.field == 'intervention_address'}
                            {* skip customer info for card *}
                        {else}
                            <tr>
                                <th>{$row.label}</th>
                                <td>{$row.value}</td>
                            </tr>
                        {/if}
                    {/foreach}
                </table>
            </div>
        </div>
    </div>
    {/block}
