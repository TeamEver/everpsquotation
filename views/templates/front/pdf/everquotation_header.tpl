{*
* Project : Everpsquotation
* @author Team EVER
* @copyright Team EVER
* @license Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

<table width="100%" id="header" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
    <tr>
        <td align="left" style="width: 50%;">
            {if $logo_path}
                <img src="{$logo_path|escape:'htmlall':'UTF-8'}" style="width:{$width_logo|escape:'htmlall':'UTF-8'}px;" />
            {/if}
            {* <div>{$shop_address|escape:'htmlall':'UTF-8'}</div> *}
        </td>
        <td style="width: 50%" align="right" valign="middle">
            <h3>{l s='Quotation' mod='everpsquotation'} : {if
                $prefix}{$prefix|escape:'htmlall':'UTF-8'}{/if}{$id_everpsquotation_quotes|escape:'htmlall':'UTF-8'}
            </h3>
            {* <h4>{$shop_name|escape:'htmlall':'UTF-8'}</h4> *}
            <h4>{l s='Date :' mod='everpsquotation'} {$date_add|date_format:"%d/%m/%Y"|escape:'htmlall':'UTF-8'}</h4>
            {if isset($deadline) && $deadline}
            <h4>{l s='Valid until' mod='everpsquotation'} {$deadline|date_format:"%d/%m/%Y"|escape:'htmlall':'UTF-8'}</h4>
            {/if}
        </td>
    </tr>
</table>