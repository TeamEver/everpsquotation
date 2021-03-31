{*
 * 2019-2021 Team Ever
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2021 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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