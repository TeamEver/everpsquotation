$(document).ready(function() {
    $('#everpsquotationform').on('submit', function(event) {
        event.preventDefault();

        // Serialiser les données du formulaire
        var formData = $(this).serialize();

        // Requête AJAX initiale
        $.ajax({
            url: everpsquotation_quote_link, // Remplacez par l'URL correcte
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Insérer le tag GTM dans le DOM
                if (response.gtm) {
                    // Vérification et traitement des données pour GTM
                    if (response.quoteEvent && response.quoteId && response.quoteCustomer && response.quoteCurrency && response.quoteShopName && response.quoteProducts) {
                        var quoteProductsArray = response.quoteProducts.map(function(product) {
                            return {
                                'productId': product.product_id,
                                'productAttributeId': product.product_attribute_id,
                                'productCustomizationId': product.id_customization,
                                'productName': product.name,
                                'productReference': product.product_reference,
                                'productEan13': product.product_ean13,
                                'productQuantity': product.quantity,
                                'productWeight': product.product_weight,
                                'productSupplierReference': product.product_supplier_reference,
                                'productPrice': product.product_price,
                                'productTotalWithoutTaxes': product.total_price_tax_excl,
                                'productTotalWithTaxes': product.total_price_tax_incl,
                                'productTotalTaxName': product.tax_name,
                                'productTotalTaxRate': product.tax_rate,
                                'productReductionAmount': product.reduction_amount,
                                'productReductionAmountTaxIncluded': product.reduction_amount_tax_incl,
                                'productReductionAmountTaxExcluded': product.reduction_amount_tax_excl
                            };
                        });

                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push({
                            'event': response.quoteEvent,
                            'quoteId': response.quoteId,
                            'customer_email': response.quoteCustomer.email,
                            'currency': response.quoteCurrency.name,
                            'quoteShopName': response.quoteShopName,
                            'quoteProducts': quoteProductsArray
                        });
                    }
                }
                if (response.quoteId && response.downloadLink) {
                    window.location.href = response.downloadLink;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur dans la première requête AJAX:', textStatus, errorThrown);
            }
        });
    });
    $('#everpscartquotation').click(function(e){
        event.preventDefault();
        // Requête AJAX initiale
        $.ajax({
            url: everpsquotation_quote_link, // Remplacez par l'URL correcte
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                // Insérer le tag GTM dans le DOM
                if (response.gtm) {
                    // Vérification et traitement des données pour GTM
                    if (response.quoteEvent && response.quoteId && response.quoteCustomer && response.quoteCurrency && response.quoteShopName && response.quoteProducts) {
                        var quoteProductsArray = response.quoteProducts.map(function(product) {
                            return {
                                'productId': product.product_id,
                                'productAttributeId': product.product_attribute_id,
                                'productCustomizationId': product.id_customization,
                                'productName': product.name,
                                'productReference': product.product_reference,
                                'productEan13': product.product_ean13,
                                'productQuantity': product.quantity,
                                'productWeight': product.product_weight,
                                'productSupplierReference': product.product_supplier_reference,
                                'productPrice': product.product_price,
                                'productTotalWithoutTaxes': product.total_price_tax_excl,
                                'productTotalWithTaxes': product.total_price_tax_incl,
                                'productTotalTaxName': product.tax_name,
                                'productTotalTaxRate': product.tax_rate,
                                'productReductionAmount': product.reduction_amount,
                                'productReductionAmountTaxIncluded': product.reduction_amount_tax_incl,
                                'productReductionAmountTaxExcluded': product.reduction_amount_tax_excl
                            };
                        });

                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push({
                            'event': response.quoteEvent,
                            'quoteId': response.quoteId,
                            'customer_email': response.quoteCustomer.email,
                            'currency': response.quoteCurrency.name,
                            'quoteShopName': response.quoteShopName,
                            'quoteProducts': quoteProductsArray
                        });
                    }
                }
                if (response.quoteId && response.downloadLink) {
                    window.location.href = response.downloadLink;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur dans la première requête AJAX:', textStatus, errorThrown);
            }
        });
    });
});
