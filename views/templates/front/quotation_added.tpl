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

{extends file='page.tpl'}

{block name="page_content"}
<div class="content">
    <div class="container flex-column">
        <p>{l s='Your quote has been sent by email.' mod='everpsquotation'} </p>
        <p><a href="{$my_quotations_link|escape:'htmlall':'UTF-8'}">{l s='You can order whenever on our website and see all your quotations on your Customer Account.' mod='everpsquotation'}</a></p>
        <p>{l s='Feel free to contact us by phone at' mod='everpsquotation'} <a href="tel:{$shop_phone|escape:'htmlall':'UTF-8'}">{$shop_phone|escape:'htmlall':'UTF-8'}</a> {l s='or by email at' mod='everpsquotation'} <a href="mailto:{$shop_email|escape:'htmlall':'UTF-8'}">{$shop_email|escape:'htmlall':'UTF-8'} </a></p>
    </div>
</div>
{/block}
