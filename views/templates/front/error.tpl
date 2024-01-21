{*
 * 2019-2024 Team Ever
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
 *  @copyright 2019-2023 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<!-- Modale Bootstrap -->
<div class="modal fade" id="quotationConfirmModal" tabindex="-1" role="dialog" aria-labelledby="quotationConfirmModal" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <!-- En-tête de la modale -->
      <div class="modal-header">
        <h5 class="modal-title">{l s='An error has occured' mod='everpsquotation'}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <!-- Contenu de la modale (Votre formulaire) -->
      <div class="modal-body">
        <p class="alert alert-warning">{l s='An error has occurred, please try again later' mod='everpsquotation'}</p>
      </div>
    </div>
  </div>
</div>
