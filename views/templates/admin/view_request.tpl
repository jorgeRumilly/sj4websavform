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
    <!-- Left Column -->
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Customer Information' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                {if $displayData.id_customer.value > 0}
                    <p><strong>{$displayData.id_customer.label}</strong> <a href="{$link->getAdminLink('AdminCustomers', true, [], ['id_customer' => $displayData.id_customer.value|intval, 'viewcustomer' => 1])|escape:'html':'UTF-8'}" target="_blank">{$displayData.id_customer.value}</a></p>
                {/if}
                <p><strong>{l s='Name' d='Modules.Sj4websavform.Admin'}</strong> {$displayData.firstname.value} {$displayData.lastname.value}</p>
                <p><strong>{$displayData.email.label}</strong> {$displayData.email.value}</p>
                <p><strong>{$displayData.phone.label}</strong> {$displayData.phone.value}</p>
                <p><strong>{$displayData.intervention_address.label}</strong> {$displayData.intervention_address.value}</p>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Request Information' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <p>
                    <strong>{$displayData.id_order.label}</strong>
                    {if $order_id > 0}
                        <a href="{$order_link}" target="_blank">{$order_id}</a>
                    {else}
                        N/A
                    {/if}
                </p>
                <p>
                    <strong>{$displayData.order_reference.label}</strong>
                    {if $order_ref != ''}
                        {$order_ref}
                    {elseif $displayData.order_reference.value != ''}
                        {$displayData.order_reference.value}
                    {else}
                        N/A
                    {/if}
                </p>
                <p><strong>{$displayData.product_types.label}</strong> {if $displayData.product_types.value != ''}{$displayData.product_types.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.nature.label}</strong> {if $displayData.nature.value != ''}{$displayData.nature.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.nature_other.label}</strong> {if $displayData.nature_other.value != ''}{$displayData.nature_other.value}{else}N/A{/if}</p>
                <p><strong>{$displayData.delai.label}</strong> {if $displayData.delai.value != ''}{$displayData.delai.value}{else}N/A{/if}</p>
            </div>
        </div>
        <div class="panel">
            <div class="panel-heading">
                <p>{l s='Technical Information' d='Modules.Sj4websavform.Admin'}</p>
            </div>
            <div class="panel-body">
                <p><strong>{$displayData.sent.label}</strong> {if $displayData.sent.value == 1}<span style="color:green;font-size:large;">✔</span>{else}<span style="color:red;font-size:large;">✖</span>{/if}</p>
                <p><strong>{$displayData.processed.label}</strong> {if $displayData.processed.value == 1}<span style="color:green;font-size:large;">✔</span>{else}<span style="color:red;font-size:large;">✖</span>{/if}</p>
                <p><strong>{$displayData.date_add.label}</strong> {if $displayData.date_add.value != ''}{$displayData.date_add.value|date_format:"%d/%m/%Y %H:%M:%S"}{/if}</p>
            </div>
            <div class="panel-footer text-right">
                <a href="{$back_url}" class="btn btn-default">
                    <i class="fas fa-arrow-left"></i> {l s='Back to Requests' d='Modules.Sj4websavform.Admin'}
                </a>
                <a href="{$process_url}" class="btn btn-success">
                    <i class="fas fa-check"></i> {l s='Process Request' d='Modules.Sj4websavform.Admin'}
                </a>
            </div>
        </div>

    </div>
    <!-- Right Column -->
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
{/block}
