{*
* Project : everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}
<form id="everpsquotationform">
    {foreach from=$groups key=id_attribute_group item=group}
        {foreach from=$group.attributes key=id_attribute item=group_attribute}
            {if $group_attribute.selected}
                <input type="hidden" name="ever_attr_group[{$id_attribute_group|escape:'htmlall':'UTF-8'}]" id="ever_attr_group{$id_attribute_group|escape:'htmlall':'UTF-8'}" value="{$id_attribute_group|escape:'htmlall':'UTF-8'}">
                <input type="hidden" name="ever_group[{$id_attribute_group|escape:'htmlall':'UTF-8'}]" id="ever_group_{$id_attribute_group|escape:'htmlall':'UTF-8'}" value="0">
                <input type="hidden" name="ever_attr[{$id_attribute|escape:'htmlall':'UTF-8'}]" id="ever_attr_{$id_attribute|escape:'htmlall':'UTF-8'}" value="{$id_attribute|escape:'htmlall':'UTF-8'}">
            {/if}
        {/foreach}
    {/foreach}
            
    {if $catalogMode && isset($catalogMode)}
        <div class="input-group bootstrap-touchspin">
            <input type="number" name="ever_qty" id="ever_quantity_wanted" value="1" class="input-group form-control" min="1" aria-label="{l s='Quantity' mod='everpsquotation'}" style="display: block;">
        </div>
    {else}
        <input type="hidden" name="ever_qty" id="ever_quantity_wanted" value="1">
    {/if}
    <input type="hidden" name="everid_product_attribute" id="everidCombination" value="" />
    <button class="btn btn-primary add-to-quote" id="everpsproductquotation" name="everpsproductquotation" type="submit">
        {l s='Download a quote' mod='everpsquotation'}
    </button>
</form>