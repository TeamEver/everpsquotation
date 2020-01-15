/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

/*https://webkul.com/blog/get-action-change-product-combination-product-page-front-end/*/
$(document).ready(function() {
    $(document).on('change', '.product-variants [data-product-attribute], #quantity_wanted, #ever_quantity_wanted', function (event) {
        var query = $(event.target.form).serialize() + '&ajax=1&action=productrefresh';
        var actionURL = $(event.target.form).attr('action');
        $.post(actionURL, query, null, 'json').then(function (resp) {
            var productUrl = resp.productUrl;
            $.post(productUrl, {ajax: '1',action: 'refresh' }, null, 'json').then(function (resp) {
                var idProductAttribute = resp.id_product_attribute;
                $('#add-to-cart-or-refresh input, #add-to-cart-or-refresh select').each(
                    function(index){
                        var input = $(this);
                        var everInputId = 'ever_' + input.attr('name');
                        if (typeof input.attr('name') !== 'undefined') {
                            if ($(this).is(':checked') || $(this).is('select') || $(this).is('input:text')) {
                                $('#everpsquotationform [name="' + everInputId + '"]').val(input.val());
                            }
                        }
                    }
                );
                $('#everidCombination').val(idProductAttribute);
            });
        });
    });
});