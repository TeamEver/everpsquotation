{*
* Project : Everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

{extends file='page.tpl'}

{block name="page_content"}
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
                    <i class="material-icons">picture_as_pdf</i>
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
                        <i class="material-icons">done_outline</i>
                    {else}
                        <a href="{$link->getModuleLink('everpsquotation', 'quotations',['id_everpsquotation'=>$value.id_everpsquotation_quotes,'action'=>validate])|escape:'htmlall':'UTF-8'}" class="btn {if $value.valid == 1}btn-info{else}btn-warning{/if}" role="button">
                            {l s='Validate' mod='everpsquotation'}
                        </a>
                    {/if}
            </td>
            <td align="right" class="details-{$value.id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}" id="add_quote_to_cart">
                <a href="{$link->getModuleLink('everpsquotation', 'quotations',['id_everpsquotation'=>$value.id_everpsquotation_quotes,'action'=>addtocart])|escape:'htmlall':'UTF-8'}" class="btn btn-success" role="button" data-idquote="{$value.id_everpsquotation_quotes}">
                    {l s='Add to cart' mod='everpsquotation'}
                    <i class="material-icons">shopping_basket</i>
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
<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Back to my account' mod='everpsquotation'}" class="account" rel="nofollow"><span>{l s='Back to my account' mod='everpsquotation'}</span></a>
{/block}
