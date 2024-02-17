$(document).ready(function() {
    $('#everpsquotationform').on('submit', function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: everpsquotation_quote_link,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#everpsquotationform').remove();
                // Insérer le tag GTM dans le DOM
                if (response.gtm) {
                    // Vérification et traitement des données pour GTM
                    if (response.gtm.quoteEvent && response.gtm.quoteId && response.gtm.quoteCustomer && response.gtm.quoteCurrency && response.gtm.quoteShopName && response.gtm.quoteProducts) {
                        var quoteProductsArray = response.gtm.quoteProducts.map(function(product) {
                            return {
                                'productId': product.product_id,
                                'productAttributeId': product.product_attribute_id,
                                'productCustomizationId': product.id_customization,
                                'productName': product.product_name,
                                'productReference': product.product_reference,
                                'productEan13': product.product_ean13,
                                'productQuantity': product.product_quantity,
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
                            'event': response.gtm.quoteEvent,
                            'quoteEventId': quotation_event_id,
                            'quoteId': response.gtm.quoteId,
                            // 'customer_email': response.gtm.quoteCustomer.email,
                            'currency': response.gtm.quoteCurrency.name,
                            'quoteShopName': response.gtm.quoteShopName,
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
        $.ajax({
            url: everpsquotation_quote_link,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#everpscartquotation').remove();
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

    // Gestionnaire d'événement pour la soumission du formulaire
    $(document).on('submit', '#everquotationAskForQuoteCart', function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); // Récupère les données du formulaire
        $.ajax({
            url: everpsquotation_quoterequest_link, // Remplacez par l'URL correcte
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#everquotationAskForQuoteCart').remove();
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    'event': 'requestForQuote',
                    'quoteEventId': quotation_event_id,
                });
                if (response.confirmModal) {
                    $('body').append(response.confirmModal);
                    $('#quotationConfirmModal').modal('show');
                    $('#quotationConfirmModal').on('hidden.bs.modal', function () {
                        $(this).modal('hide').remove();
                        $('.modal-backdrop').remove();
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur:', textStatus, errorThrown);
            }
        });
    });
});
