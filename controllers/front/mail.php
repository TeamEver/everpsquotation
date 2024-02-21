<?php
/**
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
 */

/**
 * @since 1.5.0
 */
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

class EverpsquotationMailModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        return Context::getContext()->getTranslator()->trans(
            $string,
            [],
            'Modules.Everpsquotation.quotations'
        );
    }

    public function displayAjaxSetRequest()
    {
        $token = Tools::encrypt($this->module->name . '_token/setquote');
        if (!Tools::getValue('token') || $token != Tools::getValue('token')) {
            Tools::redirect('index.php');
        }
        if (!Tools::getValue('dataPolicyConsent')) {
            die(json_encode([
                'error' => true,
                'message' => $this->l('You have to consent our data policy terms'),
            ]));
        }
        $firstName = Tools::getValue('quotefirstName');
        $lastName = Tools::getValue('quotelastName');
        $email = Tools::getValue('quoteemail');
        $phone = Tools::getValue('quotephone');
        $contacted = Tools::getValue('contacted');
        $sent = $this->sendQuoteAdminMail($firstName, $lastName, $email, $phone, $contacted);
        die(json_encode([
            'sent' => $sent,
            'confirmModal' => $this->renderModal($sent),
        ]));
    }

    private function sendQuoteAdminMail($firstName, $lastName, $email, $phone, $contacted)
    {
        $mailTemplate = 'request';
        $mailSubject = $this->l('New quote request');
        if (Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL')) {
            $to = Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL');
        } else {
            $to = Configuration::get('PS_SHOP_EMAIL');
        }
        $toName = Configuration::get('PS_SHOP_NAME');
        $templateVars = array(
            '{firstname}' => $firstName,
            '{lastname}' => $lastName,
            '{email}' => $email,
            '{phone}' => $phone,
            '{contacted}' => $contacted == 'yes' ? $this->l('Yes') : $this->l('No'),
            '{shop_name}' => $toName,
            '{shop_logo}'=>_PS_IMG_DIR_ . Configuration::get(
                'PS_LOGO',
                null,
                null,
                (int) $this->context->shop->id
            ),
        );
        $mailDir = _PS_MODULE_DIR_ . $this->module->name . '/mails/';

        // Envoi de l'e-mail
        $result = Mail::Send(
            (int)Context::getContext()->language->id,
            $mailTemplate,
            $mailSubject,
            $templateVars,
            $to,
            $toName,
            null,
            null,
            null,
            null,
            $mailDir,
            false,
            (int) Context::getContext()->shop->id
        );
        return $result;
    }

    private function renderModal($success = true)
    {
        if ((bool) $success === true) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/success.tpl');
        } else {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/error.tpl');
        }
    }
}
