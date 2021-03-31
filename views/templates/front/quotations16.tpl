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

{if $quotationsList}
<div class="content">
	<div class="row">
		<p>{l s='Here is a list of all your quotations.' mod='everpsquotation'}</p>
		<p>{l s='You can order whenever on our website.' mod='everpsquotation'}</p>
        <p>{l s='Feel free to contact us by phone at' mod='everpsquotation'} <a href="+tel{$shop_phone|escape:'htmlall':'UTF-8'}">{$shop_phone|escape:'htmlall':'UTF-8'}</a> {l s='or by email at' mod='everpsquotation'} <a href="{$shop_email|escape:'htmlall':'UTF-8'}">{$shop_email|escape:'htmlall':'UTF-8'}</a></p>
	</div>
</div>
<div class="table-responsive" id="everquotations">          
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>{l s='Download link' mod='everpsquotation'}</th>
                <th>{l s='Creation date' mod='everpsquotation'}</th>
                <th style="text-align: right;">{l s='Total Tax incl.' mod='everpsquotation'}</th>
                <th style="text-align: right;">{l s='Validate only' mod='everpsquotation'}</th>
                <th style="text-align: right;">{l s='Add to cart' mod='everpsquotation'}</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$quotationsList item=value}
        <tr>
            <td align="left" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="id_everpsquotation_quotes">
                {$prefix|escape:'htmlall':'UTF-8'}{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}
            </td>
            <td align="left" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="download_link">
                <a href="{$link->getModuleLink('everpsquotation', 'quotations',['id_everpsquotation'=>$value.id_everpsquotation_quotes,'action'=>pdf])|escape:'htmlall':'UTF-8'}" class="btn btn-info renderPdf" role="button">
                    {$prefix|escape:'htmlall':'UTF-8'}{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}.pdf
                </a>
            </td>
            <td align="left" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="date_add">
                {$value.date_add|date_format:"%A %e %B %Y"|escape:'htmlall':'UTF-8'}

            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="total_paid_tax_incl">
                {Tools::displayPrice($value.total_paid_tax_incl)|escape:'htmlall':'UTF-8'}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="valid">
                    {if $value.valid == 1}
                        {l s='Valid' mod='everpsquotation'}
                    {else}
                        <a href="{$link->getModuleLink('everpsquotation', 'quotations',['id_everpsquotation'=>$value.id_everpsquotation_quotes,'action'=>validate])|escape:'htmlall':'UTF-8'}" class="btn {if $value.valid == 1}btn-info{else}btn-warning{/if}" role="button">
                            {l s='Validate' mod='everpsquotation'}
                        </a>
                    {/if}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="add_quote_to_cart">
                <a href="{$link->getModuleLink('everpsquotation', 'quotations',['id_everpsquotation'=>$value.id_everpsquotation_quotes,'action'=>addtocart])|escape:'htmlall':'UTF-8'}" class="btn btn-success" role="button" data-idquote="{$value.id_everpsquotation_quotes}">
                    {l s='Add to cart' mod='everpsquotation'}
                </a>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{else}
<div class="content">
    <div class="row">
        <p>{l s='There\'s no quotations on your account. Feel free to ask some support !' mod='everpsquotation'}</p>
    </div>
</div>
{/if}
