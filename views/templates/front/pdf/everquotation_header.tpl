{*
* Project : Everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

<table width="100%" id="header" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
    <tr>
        <td align="left" style="width: 20%;">
            {if $logo_path}
                <img src="{$logo_path|escape:'htmlall':'UTF-8'}" style="width:{$width_logo|escape:'htmlall':'UTF-8'}px; height:{$height_logo|escape:'htmlall':'UTF-8'}px;" />
            {/if}
        </td>
        <td style="width: 50%" align="center" valign="middle">
            <h3>{l s='Quotation' mod='everpsquotation'} {if $prefix}{$prefix|escape:'htmlall':'UTF-8'}{/if}_{$id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}</h3>
            <h4>{$date_add|date_format:"%D"|escape:'htmlall':'UTF-8'}</h4>
            <h4>{$shop_name|escape:'htmlall':'UTF-8'}</h4>
            <span>{$shop_address|escape:'htmlall':'UTF-8'}</span>
        </td>
    </tr>
</table>
