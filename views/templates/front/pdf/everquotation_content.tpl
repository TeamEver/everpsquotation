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
<table id="spacer" cellspacing="0" cellpadding="0">
    <tr>
        <td width="50%"><span class="bold"></span><br /><br />
        </td>
        <td width="50%"><span class="bold"></span><br /><br />
        </td>
    </tr>
</table>
<table>
    <tr>
        <td align="left" style="width: 50%;">
            <table style="width: 100%">
                <tr>
                    <td style="font-weight: bold;">{l s='Invoice information' mod='everpsquotation'}</td>
                </tr>
                <tr>
                    <td>{$customerInfos->firstname|escape:'htmlall':'UTF-8'}
                        {$customerInfos->lastname|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerInfos->email|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddress->address1|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerAddress->address2|escape:'htmlall':'UTF-8'}
                <tr>
                    <td>{$customerAddress->address2|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
                <tr>
                    <td>{$customerAddress->postcode|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddress->city|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddress->country|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerInfos->company}
                <tr>
                    <td>{$customerInfos->company|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
                {if $customerInfos->siret}
                <tr>
                    <td>{$customerInfos->siret|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
            </table>
        </td>
        <td align="left" style="width: 50%;">
            <table style="width: 100%">
                <tr>
                    <td style="font-weight: bold;">{l s='Delivery information' mod='everpsquotation'}</td>
                </tr>
                <tr>
                    <td>{$customerInfos->firstname|escape:'htmlall':'UTF-8'}
                        {$customerInfos->lastname|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerInfos->email|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->address1|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerAddressDelivery->address2|escape:'htmlall':'UTF-8'}
                <tr>
                    <td>{$customerAddressDelivery->address2|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
                <tr>
                    <td>{$customerAddressDelivery->postcode|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->city|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->country|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerInfos->siret}
                <tr>
                    <td>{$customerInfos->siret|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
            </table>
        </td>
        <td align="right" style="width: 50%;">
            <table style="width: 100%">
                <tr>
                    <td>{$customerInfos->firstname|escape:'htmlall':'UTF-8'}
                        {$customerInfos->lastname|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerInfos->email|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->address1|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerAddressDelivery->address2|escape:'htmlall':'UTF-8'}
                <tr>
                    <td>{$customerAddressDelivery->address2|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
                <tr>
                    <td>{$customerAddressDelivery->postcode|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->city|escape:'htmlall':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{$customerAddressDelivery->country|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {if $customerInfos->siret}
                <tr>
                    <td>{$customerInfos->siret|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/if}
            </table>
        </td>
    </tr>
</table>
<table id="spacer" cellspacing="0" cellpadding="0">
    <tr>
        <td width="50%"><span class="bold"></span><br /><br />
        </td>
        <td width="50%"><span class="bold"></span><br /><br />
        </td>
    </tr>
</table>
{if $details}
<!-- Products list -->
<table width="100%" id="content" border="0" cellpadding="2" cellspacing="0" style="margin:0;border:1px solid #808080;">
    <thead style="border-bottom:1px solid #808080;">
        <tr style="color:#000; background-color: #DCDCDC;border-bottom:1px solid #808080;">
            <th class="header small" align="left">{l s='Product reference' mod='everpsquotation'}</th>
            <th class="header small" align="left">{l s='Product name' mod='everpsquotation'}</th>
            <th class="header small" align="right">{l s='Product quantity' mod='everpsquotation'}</th>
            <th class="header small" align="right">{l s='Product price wt' mod='everpsquotation'}</th>
            <th class="header small" align="right">{l s='Total product price wt' mod='everpsquotation'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$details item=value}
        {math assign='product_total' equation='x*y' x=$value.product_price y=$value.product_quantity}
        <tr>
            <td align="left" class="details-{$value.id_everpsquotation_quote_detail|escape:'htmlall':'UTF-8'}"
                id="product_reference">
                {$value.product_reference|escape:'htmlall':'UTF-8'}
            </td>
            <td align="left" class="details-{$value.id_everpsquotation_quote_detail|escape:'htmlall':'UTF-8'}"
                id="product_name">
                {$value.product_name|escape:'htmlall':'UTF-8'}<br>
                {$value.name|escape:'htmlall':'UTF-8'}<br>
                {if isset($customizations) && $customizations}
                {l s='Customization :' mod='everpsquotation'}<br>
                {foreach from=$customizations item=customization}
                {if $customization.product_id == $value.product_id}
                {foreach from=$customization.customizations item=$cust}
                {$cust|escape:'htmlall':'UTF-8'}<br>
                {/foreach}
                {/if}
                {/foreach}
                {/if}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quote_detail|escape:'htmlall':'UTF-8'}"
                id="product_quantity">
                {$value.product_quantity|escape:'htmlall':'UTF-8'}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quote_detail|escape:'htmlall':'UTF-8'}"
                id="product_price">
                {convertPrice price=$value.product_price}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quote_detail|escape:'htmlall':'UTF-8'}"
                id="total_price_tax_excl">
                {convertPrice price=$product_total}
            </td>
        </tr>
        {/foreach}
    </tbody>
</table>
<!-- //Products list -->
<!-- Table total -->
<table width="100%" id="content" border="0" cellpadding="2" cellspacing="0" style="0;">
    <tbody>
        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0;border-top:1px solid #808080; background-color: #DCDCDC;">
                {l s='Total products wt' mod='everpsquotation'}
            </td>
            <td align="right" style="border-top:1px solid #808080;">
                {convertPrice price=$everpsquotation->total_products}
            </td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0; background-color: #DCDCDC;">
                {l s='Total carrier wt' mod='everpsquotation'}
            </td>
            <td align="right" style="">
                {convertPrice price=$everpsquotation->total_shipping}
            </td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0; background-color: #DCDCDC;">
                {l s='Total discount wt' mod='everpsquotation'}
            </td>
            <td align="right" style="">
                {convertPrice price=$everpsquotation->total_discounts_tax_excl}
            </td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0; background-color: #DCDCDC;">
                {l s='Total wt' mod='everpsquotation'}
            </td>
            <td align="right" style="">
                {convertPrice price=$everpsquotation->total_paid_tax_excl}
            </td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0; background-color: #DCDCDC;">
                {l s='Total taxes' mod='everpsquotation'}
            </td>
            <td align="right" style="">
                {convertPrice price=$total_taxes}
            </td>
        </tr>

        <tr>
            <td colspan="3"></td>
            <td align="right" style="margin:0; border-bottom:1px solid #808080; background-color: #DCDCDC;">
                {l s='Total tax incl.' mod='everpsquotation'}
            </td>
            <td align="right" style="border-bottom:1px solid #808080;">
                {convertPrice price=$everpsquotation->total_paid_tax_incl}
            </td>
        </tr>
    </tbody>
</table>
<!-- //Table total -->
{/if}