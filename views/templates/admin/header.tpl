{*
* Project : everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

<div class="panel row">
	<h3><i class="icon icon-credit-card"></i> {l s='Ever Quotations' mod='everpsquotation'}</h3>
    <div class="col-md-6">
		<img id="everlogo" src="{$everpsquotation_dir|escape:'htmlall':'UTF-8'}/logo.png" style="max-width: 120px;">
        <p>
            <strong>{l s='Please enable rewrite rules on your shop' mod='everpsquotation'}</strong>
        </p>
		<p>
			<strong>{l s='Welcome to Ever Quotations module !' mod='everpsquotation'}</strong><br />
			{l s='Thanks for using Team Ever\'s module' mod='everpsquotation'}
		</p>
        <h4>{l s='How to be first on Google pages ?' mod='everpsquotation'}</h4>
        <p>{l s='We have created the best SEO module, by working with huge websites and SEO societies' mod='everpsquotation'}</p>
        <p>
            <a href="https://addons.prestashop.com/fr/seo-referencement-naturel/39489-ever-ultimate-seo.html" target="_blank">{l s='See the best SEO module on Prestashop Addons' mod='everpsquotation'}</a>
        </p>
    </div>
    <div class="col-md-6 alert alert-warning">
        <p>
            {l s='Do you need more functions for your quotes on your shop ?' mod='everpsquotation'}
        </p>
            <a href="https://www.store-opart.fr/p/25-devis.html#ae35-4" target="_blank">{l s='Have a look on our partner module, you will be able to create quotes from your back-office !' mod='everpsquotation'}</a>
        </p>
    </div>
    <div class="col-md-12">
		<p class="alert alert-info">
			{l s='Please make sure shop phone and shop emails are defined in "Shop Parameters" => "Contact" and "Shops"' mod='everpsquotation'}
		</p>
        {if isset($rewrite_mode) && $rewrite_mode}
        <p class="alert alert-warning">
            {l s='Dont forget to allow rewrite rules in "Shop Parameters" => "SEO" to make this module works fine' mod='everpsquotation'}
        </p>
        {/if}
    </div>
</div>
