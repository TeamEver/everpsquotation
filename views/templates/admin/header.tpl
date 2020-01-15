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
    <div class="col-md-6">
        <p class="alert alert-warning">
            {l s='This module is free and will always be ! You can support our free modules by making a donation by clicking the button below' mod='everpsquotation'}
        </p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick" />
        <input type="hidden" name="hosted_button_id" value="3LE8ABFYJKP98" />
        <input type="image" src="https://www.team-ever.com/wp-content/uploads/2019/06/appel_a_dons-1.jpg" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Bouton Faites un don avec PayPal" />
        <img alt="" border="0" src="https://www.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1" />
        </form>
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