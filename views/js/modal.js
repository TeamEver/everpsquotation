$(document).ready(function() {
    $('#everquotationModal').click(function(e){
        event.preventDefault();
        // Requête AJAX initiale
        $.ajax({
            url: everpsquotation_quote_link, // Remplacez par l'URL correcte
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.modal) {
                    $('body').append(response.modal);
                    $('#customerInfoModal').modal('show');
                    $('#customerInfoModal').on('hidden.bs.modal', function () {
                        $(this).modal('hide').remove();
                        $('.modal-backdrop').remove();
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur dans la première requête AJAX:', textStatus, errorThrown);
            }
        });
    });

    // Gestionnaire d'événement pour la soumission du formulaire
    $(document).on('submit', '#everquotationAskForQuote', function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); // Récupère les données du formulaire
        $.ajax({
            url: everpsquotation_quoterequest_link, // Remplacez par l'URL correcte
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#customerInfoModal').modal('hide').remove();
                if (response.confirmModal) {
                    $('body').append(response.confirmModal);
                    $('#quotationConfirmModal').modal('show');
                    $('#quotationConfirmModal').on('hidden.bs.modal', function () {
                        $('#quotationConfirmModal').modal('hide').remove();
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
