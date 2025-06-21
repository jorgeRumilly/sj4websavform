{extends file='page.tpl'}
{block name='page_content'}

<h1>{l s='Support Request Form' d='Modules.Sj4websavform.Shop'}</h1>


{if isset($confirmation) && $confirmation}
    <div class="alert alert-success">
        {l s='Your request has been sent successfully. Our team will get back to you shortly.' d='Modules.Sj4websavform.Shop'}
    </div>
{/if}


<form method="post" action="{$form_action}" enctype="multipart/form-data" class="sav-form">
    <input type="hidden" name="token" value="{$token}">

    {* --- Bloc identité client --- *}
    <div class="form-group">
        <label for="firstname">{l s='First name' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="firstname" class="form-control" value="{$form_data.firstname|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label for="lastname">{l s='Last name' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="lastname" class="form-control" value="{$form_data.lastname|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label for="email">{l s='Email address' d='Modules.Sj4websavform.Shop'}</label>
        <input type="email" name="email" class="form-control" value="{$form_data.email|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label for="phone">{l s='Phone number' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="phone" class="form-control" value="{$form_data.phone|escape:'htmlall':'UTF-8'}">
    </div>

    {* --- Lieu d'intervention --- *}
    <div class="form-group">
        <label for="intervention_address">{l s='Intervention address' d='Modules.Sj4websavform.Shop'}</label>
        <textarea name="intervention_address" class="form-control" required>{$form_data.intervention_address|escape:'htmlall':'UTF-8'}</textarea>
    </div>

    {* --- Commande associée --- *}
    {if $is_logged}
        <div class="form-group">
            <label for="order_reference">{l s='Associated order' d='Modules.Sj4websavform.Shop'}</label>
            <select name="order_reference" class="form-control">
                <option value="">{l s='Choose an order (optional)' d='Modules.Sj4websavform.Shop'}</option>
                {foreach from=$customer_orders item=order}
                    <option value="{$order.reference|escape:'htmlall':'UTF-8'}">{$order.reference} - {$order.date_add|date_format:"%d/%m/%Y"}</option>
                {/foreach}
            </select>
        </div>
    {else}
        <div class="form-group">
            <label for="order_reference">{l s='Order reference (if available)' d='Modules.Sj4websavform.Shop'}</label>
            <input type="text" name="order_reference" class="form-control" value="{$form_data.order_reference|escape:'htmlall':'UTF-8'}">
        </div>
    {/if}

    {* --- Type de produit --- *}
    <div class="form-group">
        <label>{l s='Concerned products' d='Modules.Sj4websavform.Shop'}</label>
        <div class="product-type-grid">
            {foreach from=$product_types item=label key=value}
                <label class="product-type-item type-{$value|escape:'htmlall':'UTF-8'}">
                    <input type="checkbox" name="product_types[]" value="{$value}" {if in_array($value, $form_data.product_types)}checked{/if}>
                    <span class="product-type-label">{$label}</span>
                </label>
            {/foreach}
        </div>
        <div class="form-group mt-2">
            <label>{l s='Other product type (if not listed)' d='Modules.Sj4websavform.Shop'}</label>
            <input type="text" name="product_type_other" class="form-control" value="{$form_data.product_type_other|escape:'htmlall':'UTF-8'}">
        </div>
    </div>

    {* --- Nature de la demande --- *}
    <div class="form-group">
        <label>{l s='Nature of the request' d='Modules.Sj4websavform.Shop'}</label>
        <div class="radio-inline-group">
            {foreach from=$natures key=key item=label}
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="nature" value="{$key}" id="nature_{$key}" {if $form_data.nature == $key}checked{/if}>
                    <label class="form-check-label" for="nature_{$key}">{$label}</label>
                </div>
            {/foreach}
        </div>
        <div id="nature_other_container" class="mt-2" {if $form_data.nature != 'autre'}style="display:none"{/if}>
            <input type="text" name="nature_other" class="form-control" placeholder="{l s='Please specify' d='Modules.Sj4websavform.Shop'}" value="{$form_data.nature_other|escape:'htmlall':'UTF-8'}">
        </div>
    </div>


    {*-- Urgence de la demande --*}
    <div class="form-group">
        <label>{l s='Preferred delay' d='Modules.Sj4websavform.Shop'}</label>
        <div class="radio-inline-group">
            {foreach from=$delais key=key item=label}
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="delai" value="{$key}" id="delai_{$key}" {if $form_data.delai == $key}checked{/if}>
                    <label class="form-check-label" for="delai_{$key}">{$label}</label>
                </div>
            {/foreach}
        </div>
    </div>



    {* --- Objet et message --- *}
    <div class="form-group">
        <label for="subject">{l s='Subject' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="subject" class="form-control" value="{$form_data.subject|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label for="message">{l s='Message' d='Modules.Sj4websavform.Shop'}</label>
        <textarea name="message" class="form-control" required>{$form_data.message|escape:'htmlall':'UTF-8'}</textarea>
    </div>

    {* --- Upload fichiers --- *}
    <div class="form-group">
        <label>{l s='Attach up to 5 images' d='Modules.Sj4websavform.Shop'}</label>
        <input type="file" name="attachments[]" accept="image/*" multiple>
    </div>

    {* --- Anti-bot honeypot (champ caché) --- *}
    <div style="display:none;">
        <input type="text" name="website" value="">
    </div>

    <div class="form-group">
        <button type="submit" name="submit_savform" class="btn btn-primary">
            {l s='Send request' d='Modules.Sj4websavform.Shop'}
        </button>
    </div>
</form>
{/block}