
{**
* Quotation Template
* 
* @author Empty
* @copyright  Empty
* @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<table style="width: 100%;">
    <tr>
        <td style="width: 100%; font-size: 9pt; font-color: #404040;">{$before|unescape:'htmlall'}<br /><br />
            
            <table style="width: 100%" border="1" cellpadding="5">
                <thead>
                    <tr>
                        <td width="13%" valign="middle" style="font-weight: bold; color: #fff; background-color: #595959; text-align: center;">
                            Ref
                        </td>
                        <td width="47%" valign="middle" style="font-weight: bold; color: #fff; background-color: #595959; text-align: center;">
                            Name
                        </td>
                        <td width="8%" valign="middle" style="font-weight: bold; color: #fff; background-color: #595959; text-align: center;">
                            Qty
                        </td>
                        <td width="16%" valign="middle" style="font-weight: bold; color: #fff; background-color: #595959; text-align: center;">
                            Unit Price Tax Excl.
                        </td>
                        <td width="16%" valign="middle" style="font-weight: bold; color: #fff; background-color: #595959; text-align: center;">
                            Total Tax Excl.
                        </td>
                    </tr>
                </thead>
                <tbody height="1000">
                    {foreach $products as $product}
                        <tr>
                            <td>{$product['reference']|escape:'htmlall':'UTF-8'}</td>
                            <td>{$product['name']|escape:'htmlall':'UTF-8'}{if !empty($product['features_name'])} ({$product['features_name']|escape:'htmlall':'UTF-8'}) {/if}{if !empty($product['combination'])} ({$product['combination']|escape:'htmlall':'UTF-8'}) {/if}</td>
                            <td>{$product['quantity']|escape:'htmlall':'UTF-8'}</td>
                            <td>{displayPrice price=$product['price']}</td>
                            <td>{displayPrice price=$product['total']}</td>
                        </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" rowspan="6">&nbsp;</td>
                        <td colspan="2">Total Products</td>
                        <td>{displayPrice price=$cart_info['total_products']}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Shipping</td>
                        <td>{displayPrice price=$cart_info['total_shipping_tax_exc']}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Total Tax</td>
                        <td>{displayPrice price=$cart_info['total_tax']}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Total products With Tax</td>
                        <td>{displayPrice price=$cart_info['total_products_wt']}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Discount With Tax</td>
                        <td>-{displayPrice price=$cart_info['total_discounts']}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold; color: #fff; background-color: #595959;">TOTAL</td>
                        <td>{displayPrice price=$cart_info['total_price']}</td>
                    </tr>
                </tfoot>
            </table>
            {$after|unescape:'htmlall'}{* HTML CONTENT *}
        </td>
    </tr>
</table>

