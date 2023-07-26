{*
 * 2019-2023 Team Ever
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
 *  @copyright 2019-2023 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<div class="panel row" id="admineverheader">
	<h3><i class="icon icon-smile"></i> {l s='Ever Quotations' mod='everpsquotation'}</h3>
    <div class="col-md-12">
        <a href="#admineverfooter">
            <img id="everlogo" src="{$everpsquotation_dir|escape:'htmlall':'UTF-8'}/logo.png" style="max-width: 120px;">
        </a>
        <p>
            <strong>{l s='Please enable rewrite rules on your shop' mod='everpsquotation'}</strong>
        </p>
		<p>
			<strong>{l s='Welcome to Ever Quotations module !' mod='everpsquotation'}</strong><br />
			{l s='Thanks for using Team Ever\'s module' mod='everpsquotation'}
		</p>
        <p class="alert alert-info">
            {l s='Do you need more functions for your quotes on your shop ?' mod='everpsquotation'}
            <br>
            <a href="https://www.store-opart.fr/?opaffi=527bc8ee40" target="_blank">{l s='Have a look on our partner module, you will be able to create quotes from your back-office !' mod='everpsquotation'}</a>
        </p>
        {if isset($moduleConfUrl) && $moduleConfUrl}
        <a href="{$moduleConfUrl|escape:'htmlall':'UTF-8'}" class="btn btn-success">{l s='Direct link to module configuration' mod='everpsquotation'}</a>
        {/if}
        {if isset($quote_controller_link) && $quote_controller_link}
        <a href="{$quote_controller_link|escape:'htmlall':'UTF-8'}" class="btn btn-success">{l s='Direct link to quotations list' mod='everpsquotation'}</a>
        {/if}
    </div>
</div>
