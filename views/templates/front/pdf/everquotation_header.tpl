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

<table width="100%" id="header" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
    <tr>
        <td align="left" style="width: 50%;">
            {if $logo_path}
            <img src="{$logo_path|escape:'htmlall':'UTF-8'}"
                style="width:{$width_logo|escape:'htmlall':'UTF-8'}px; height:{$height_logo|escape:'htmlall':'UTF-8'}px;" />
            {/if}
            <span>{$shop_address|escape:'htmlall':'UTF-8'}</span>
        </td>
        <td style="width: 50%" align="right" valign="middle">
            <h4>{$shop_name|escape:'htmlall':'UTF-8'}</h4>
            <h4>{$date_add|date_format:"%D"|escape:'htmlall':'UTF-8'}</h4>
            <h3>{l s='Quotation' mod='everpsquotation'} {if
                $prefix}{$prefix|escape:'htmlall':'UTF-8'}{/if}_{$id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}
            </h3>



        </td>
    </tr>
</table>