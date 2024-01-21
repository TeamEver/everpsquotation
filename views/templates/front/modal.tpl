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
<div class="modal fade" id="customerInfoModal" tabindex="-1" role="dialog" aria-labelledby="customerInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <!-- En-tête de la modale -->
      <div class="modal-header">
        <h5 class="modal-title" id="customerInfoModalLabel">{l s='Ask for a quote' mod='everpsquotation'}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <!-- Contenu de la modale (Votre formulaire) -->
      <div class="modal-body">
          <form id="everquotationAskForQuote">
              <!-- Champ prénom -->
              <div class="form-group">
                  <label for="firstName">{l s='Firstname' mod='everpsquotation'}</label>
                  <input type="text" class="form-control" id="quotefirstName" name="quotefirstName" required>
              </div>

              <!-- Champ nom -->
              <div class="form-group">
                  <label for="lastName">{l s='Lastname' mod='everpsquotation'}</label>
                  <input type="text" class="form-control" id="quotelastName" name="quotelastName" required>
              </div>

              <!-- Champ email -->
              <div class="form-group">
                  <label for="email">{l s='Email' mod='everpsquotation'}</label>
                  <input type="email" class="form-control" id="quoteemail" name="quoteemail" required>
              </div>

              <!-- Champ téléphone -->
              <div class="form-group">
                  <label for="phone">{l s='Phone' mod='everpsquotation'}</label>
                  <input type="text" class="form-control" id="quotephone" name="quotephone">
              </div>

              <!-- Boutons radio pour être recontacté -->
              <div class="form-group">
                  <label>{l s='Be contacted by our advisors' mod='everpsquotation'}</label>
                  <div>
                      <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="contacted" id="contactYes" value="yes" checked>
                          <label class="form-check-label" for="contactYes">{l s='Yes' mod='everpsquotation'}</label>
                      </div>
                      <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="contacted" id="contactNo" value="no">
                          <label class="form-check-label" for="contactNo">{l s='No' mod='everpsquotation'}</label>
                      </div>
                  </div>
              </div>
              <button type="submit" class="btn btn-primary">{l s='Submit' mod='everpsquotation'}</button>
          </form>
      </div>
    </div>
  </div>
</div>
