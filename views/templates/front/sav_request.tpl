<h1>{l s='Support Request Form' d='Modules.Sj4websavform.Shop'}</h1>

<form method="post" action="{$form_action}" enctype="multipart/form-data" class="sav-form">
    <input type="hidden" name="token" value="{$token}">

    {* --- Bloc identité client --- *}
    <div class="form-group">
        <label>{l s='First name' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="firstname" class="form-control" value="{$form_data.firstname|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label>{l s='Last name' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="lastname" class="form-control" value="{$form_data.lastname|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label>{l s='Email address' d='Modules.Sj4websavform.Shop'}</label>
        <input type="email" name="email" class="form-control" value="{$form_data.email|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label>{l s='Phone number' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="phone" class="form-control" value="{$form_data.phone|escape:'htmlall':'UTF-8'}">
    </div>

    {* --- Lieu d'intervention --- *}
    <div class="form-group">
        <label>{l s='Intervention address' d='Modules.Sj4websavform.Shop'}</label>
        <textarea name="intervention_address" class="form-control" required>{$form_data.intervention_address|escape:'htmlall':'UTF-8'}</textarea>
    </div>

    {* --- Commande associée --- *}
    {if $is_logged}
        <div class="form-group">
            <label>{l s='Associated order' d='Modules.Sj4websavform.Shop'}</label>
            <select name="order_reference" class="form-control">
                <option value="">{l s='Choose an order (optional)' d='Modules.Sj4websavform.Shop'}</option>
                {foreach from=$customer_orders item=order}
                    <option value="{$order.reference|escape:'htmlall':'UTF-8'}">{$order.reference} - {$order.date_add|date_format:"%d/%m/%Y"}</option>
                {/foreach}
            </select>
        </div>
    {else}
        <div class="form-group">
            <label>{l s='Order reference (if available)' d='Modules.Sj4websavform.Shop'}</label>
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
    </div>


    {* --- Objet et message --- *}
    <div class="form-group">
        <label>{l s='Subject' d='Modules.Sj4websavform.Shop'}</label>
        <input type="text" name="subject" class="form-control" value="{$form_data.subject|escape:'htmlall':'UTF-8'}" required>
    </div>

    <div class="form-group">
        <label>{l s='Message' d='Modules.Sj4websavform.Shop'}</label>
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
