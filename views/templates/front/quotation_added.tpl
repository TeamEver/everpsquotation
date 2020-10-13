{*
* Project : everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

{extends file='page.tpl'}

{block name="page_content"}
<div class="content">
    <div class="row">
        <p>{l s='Your quote has been sent by email.' mod='everpsquotation'}</p>
        <p>{l s='You can order whenever on our website and see all your quotations on your Customer Account.' mod='everpsquotation'}</p>
        <p>{l s='Feel free to contact us by phone at' mod='everpsquotation'} <a href="tel:{$shop_phone|escape:'htmlall':'UTF-8'}">{$shop_phone|escape:'htmlall':'UTF-8'}</a> {l s='or by email at' mod='everpsquotation'} <a href="mailto:{$shop_email|escape:'htmlall':'UTF-8'}">{$shop_email|escape:'htmlall':'UTF-8'}</a></p>
    </div>
</div>
{/block}
