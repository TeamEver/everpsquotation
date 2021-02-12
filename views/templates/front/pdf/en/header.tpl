
{**
* Header Template
* 
* @author Empty
* @copyright  Empty
* @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<table style="width: 100%">
    <tr>
        <td style="width: 40%; font-size: 8pt;">
            {if $logo_path}
                <br /><br /><img src="{$logo_path|escape:'htmlall':'UTF-8'}" style="width:{$width_logo|escape:'htmlall':'UTF-8'}px; height:{$height_logo|escape:'htmlall':'UTF-8'}px;" alt="{$shop_name|escape:'html':'UTF-8'}" />
            {/if}
            <br />
            {$header|unescape:'htmlall'}{* HTML CONTENT *}
        </td>
        <td width="10%">
            <p>&nbsp;</p>
        </td>
        <td style="width: 45%;" valign="top">
            <table style="width: 100%" height="10">
                <tr>
                    <td valign="left" style="font-weight: bold; font-size: 5pt; width: 100%; text-align: left;">
                        <br /><br /><br /><br />
                        <strong style="font-size: 14pt; font-weight: normal; line-height: 2pt">Quotation : {$reference|escape:'htmlall':'UTF-8'}</strong><br />
                        <strong style="font-size: 10pt; font-weight: normal;">Name : {$first_name|escape:'htmlall':'UTF-8'} {$last_name|escape:'htmlall':'UTF-8'}</strong><br />
                        <strong style="font-size: 10pt; font-weight: normal;">Date : {$date|escape:'htmlall':'UTF-8'}</strong><br />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

