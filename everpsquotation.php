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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCart.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCartProduct.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

class Everpsquotation extends PaymentModule
{
    private $html;
    private $postErrors = [];
    private $postSuccess = [];
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = 'everpsquotation';
        $this->tab = 'payments_gateways';
        $this->version = '5.1.1';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ever PS Quotation');
        $this->description = $this->l('Simply accept quotations on your Prestashop !');
        $this->confirmUninstall = $this->l('Do you REALLY want to uninstall this awesome module ?');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    /**
     * The install method
     *
     * @see prestashop/classes/Module#install()
     */
    public function install()
    {
        Configuration::updateValue(
            'EVERPSQUOTATION_LOGO_WIDTH',
            180
        );
        // Install SQL
        $sql = [];
        include(dirname(__FILE__).'/sql/install.php');
        foreach ($sql as $s) {
            if (!Db::getInstance()->execute($s)) {
                return false;
            }
        }
        $this->createQuoteHooks();
        return (parent::install()
            && $this->checkHooks()
            && $this->installModuleTab('AdminEverPsQuotation'));
    }

    public function checkHooks()
    {
        return ($this->registerHook('header')
            && $this->registerHook('displayAfterProductActions')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('displayReassurance')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('displayAdminEndContent')
            && $this->registerHook('displayCartModalFooter'));
    }

    public function createQuoteHooks()
    {
        $result = true;
        // Hook before quote creation
        if (!Hook::getIdByName('actionBeforeCreateEverQuote')) {
            $hook = new Hook();
            $hook->name = 'actionBeforeCreateEverQuote';
            $hook->title = 'Before quotation creation';
            $hook->description = 'This hook is triggered before quote is created';
            $result &= $hook->save();
        }
        // Hook after quote creation
        if (!Hook::getIdByName('actionAfterCreateEverQuote')) {
            $hook = new Hook();
            $hook->name = 'actionAfterCreateEverQuote';
            $hook->title = 'After quotation creation';
            $hook->description = 'This hook is triggered after quote is created';
            $result &= $hook->save();
        }
        return $result;
    }

    public function deleteQuoteHooks()
    {
        $result = true;
        // Hook before quote creation
        $actionBeforeCreateEverQuote = Hook::getIdByName('actionBeforeCreateEverQuote');
        if ($actionBeforeCreateEverQuote) {
            $hook = new Hook(
                (int)$actionBeforeCreateEverQuote
            );
            $result &= $hook->delete();
        }
        // Hook after quote creation
        $actionAfterCreateEverQuote = Hook::getIdByName('actionAfterCreateEverQuote');
        if ($actionAfterCreateEverQuote) {
            $hook = new Hook(
                (int)$actionAfterCreateEverQuote
            );
            $result &= $hook->delete();
        }
        return $result;
    }

    /**
     * The uninstall method
     *
     * @see prestashop/classes/Module#uninstall()
     */
    public function uninstall()
    {
        if ((bool) Configuration::get('EVERPSQUOTATION_DROP_SQL') === true) {
            // Uninstall SQL
            $sql = [];
            include(dirname(__FILE__).'/sql/uninstall.php');
            foreach ($sql as $s) {
                if (!Db::getInstance()->execute($s)) {
                    return false;
                }
            }
        }
        $this->deleteQuoteHooks();
        Db::getInstance()->delete(
            'hook_module',
            'id_module = ' . (int) $this->id
        );

        return (parent::uninstall()
            && Configuration::deleteByName('EVERPSQUOTATION_ACCOUNT_EMAIL')
            && Configuration::deleteByName('EVERPSQUOTATION_LOGO_WIDTH')
            && Configuration::deleteByName('EVERPSQUOTATION_PREFIX')
            && Configuration::deleteByName('EVERPSQUOTATION_TEXT')
            && Configuration::deleteByName('EVERPSQUOTATION_MAIL_SUBJECT')
            && $this->uninstallModuleTab('AdminEverPsQuotation'));
    }

    /**
     * The installModuleTab method
     *
     * @param string $tabClass
     * @param string $tabName
     * @param integer $idTabParent
     * @return boolean
     */
    private function installModuleTab($tabClass)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $tabClass;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->position = Tab::getNewLastPosition($tab->id_parent);
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Devis';
        }
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * The uninstallModuleTab method
     *
     * @param string $tabClass
     * @return boolean
     */
    private function uninstallModuleTab($tabClass)
    {
        $tab = new Tab((int) Tab::getIdFromClassName($tabClass));
        return $tab->delete();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitEverpsquotationModule')) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }

        // Display errors
        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }

        // Display confirmations
        if (count($this->postSuccess)) {
            foreach ($this->postSuccess as $success) {
                $this->html .= $this->displayConfirmation($success);
            }
        }

        /**
         * Mod rewrite must be set to allows quotations
         */
        if (!Configuration::get('PS_REWRITING_SETTINGS')) {
            $rewriteMode = true;
        } else {
            $rewriteMode = false;
        }
        $quote_controller_link  = 'index.php?controller=AdminEverPsQuotation&token=';
        $quote_controller_link .= Tools::getAdminTokenLite('AdminEverPsQuotation');

        $this->context->smarty->assign([
            'quote_controller_link' => $quote_controller_link,
            'everpsquotation_dir' => $this->_path,
            'rewrite_mode' => $rewriteMode,
        ]);

        if ($this->checkLatestEverModuleVersion()) {
            $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/upgrade.tpl');
        }
        $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/header.tpl');
        $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpsquotationModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $selectedCategories = Configuration::get(
            'EVERPSQUOTATION_CATEGORIES'
        );
        if (!$selectedCategories) {
            $selectedCategories = [];
        } else {
            $selectedCategories = json_decode($selectedCategories);
        }
        $tree = [
            'selected_categories' => $selectedCategories,
            'use_search' => true,
            'use_checkbox' => true,
            'id' => 'id_category_tree',
        ];
        if (file_exists(_PS_MODULE_DIR_.'everpsquotation/views/img/quotation.jpg')) {
            $defaultUrlImage = $this->_path . '/views/img/quotation.jpg';
        } else {
            $defaultUrlImage = Tools::getHttpHost(true) . __PS_BASE_URI__ . 'img/' . Configuration::get(
                'PS_LOGO'
            );
        }
        $defaultImage = '<img src="' . $defaultUrlImage . '" style="max-width:150px;"/>';

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Drop all quotations on module uninstall ?'),
                        'desc' => $this->l('Will delete all quotations on module uninstall'),
                        'hint' => $this->l('Else all quotations will be keeped on module uninstall'),
                        'name' => 'EVERPSQUOTATION_DROP_SQL',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Allowed customer groups'),
                        'desc' => $this->l('Choose allowed groups, customers must be logged'),
                        'hint' => $this->l('Customers must be logged and have a registered address'),
                        'name' => 'EVERPSQUOTATION_GROUPS[]',
                        'class' => 'chosen',
                        'identifier' => 'name',
                        'multiple' => true,
                        'options' => [
                            'query' => Group::getGroups(
                                (int) $this->context->language->id,
                                (int) $this->context->shop->id
                            ),
                            'id' => 'id_group',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'lang' => false,
                        'label' => $this->l('Quotation duration'),
                        'desc' => $this->l('Duration in days of validity of the estimate'),
                        'hint' => $this->l('Will display a quote expiration date (leave empty for no use)'),
                        'name' => 'EVERPSQUOTATION_DURATION',
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Quotation minimum amount'),
                        'desc' => $this->l('Minimum amount without taxes to allow quotations'),
                        'hint' => $this->l('Leave empty for no use'),
                        'name' => 'EVERPSQUOTATION_MIN_AMOUNT',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable quotes creation on product pages'),
                        'desc' => $this->l('Will show a "download quotation" on product page'),
                        'hint' => $this->l('Will show a button next to "Add to cart".'),
                        'name' => 'EVERPSQUOTATION_PRODUCT',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Quotation logo'),
                        'desc' => $this->l('Quotation logo on PDF files'),
                        'hint' => $this->l('Default will be shop logo'),
                        'name' => 'image',
                        'display_image' => true,
                        'image' => $defaultImage,
                        'desc' => sprintf($this->l('
                            maximum image size: %s.'), ini_get('upload_max_filesize')),
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Logo width'),
                        'desc' => $this->l('Logo width (pixel value) on quotes'),
                        'hint' => $this->l('Will define your logo width on quotes'),
                        'name' => 'EVERPSQUOTATION_LOGO_WIDTH',
                        'required' => true,
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'label' => $this->l('Email'),
                        'desc' => $this->l('Admin email for quotation mails copy'),
                        'hint' => $this->l('Leave empty for no use'),
                        'name' => 'EVERPSQUOTATION_ACCOUNT_EMAIL',
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-download"></i>',
                        'label' => $this->l('Quotation prefix'),
                        'desc' => $this->l('Please specify quotation prefix'),
                        'hint' => $this->l('Every quote will start with this prefix'),
                        'name' => 'EVERPSQUOTATION_PREFIX',
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Quotation mail subject'),
                        'desc' => $this->l('Please specify subject of mails send'),
                        'hint' => $this->l('Quotations will be sent by email using tihs subject'),
                        'name' => 'EVERPSQUOTATION_MAIL_SUBJECT',
                    ],
                    [
                        'type' => 'textarea',
                        'lang' => true,
                        'label' => $this->l('File name for quotations'),
                        'desc' => $this->l('PDF filename'),
                        'hint' => $this->l('Every quote file will have this name. Required.'),
                        'name' => 'EVERPSQUOTATION_FILENAME',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'autoload_rte' => true,
                        'lang' => true,
                        'label' => $this->l('Quotation specific mentions'),
                        'desc' => $this->l('These mentions will be displayed at the bottom of the estimate, after the list of products and the totals'),
                        'hint' => $this->l('You can specify for example your bank details as well as the mention of good for agreement'),
                        'name' => 'EVERPSQUOTATION_MENTIONS',
                    ],
                    [
                        'type' => 'textarea',
                        'autoload_rte' => true,
                        'lang' => true,
                        'label' => $this->l('Quotation text on footer'),
                        'desc' => $this->l('Please specify quotation text on footer'),
                        'hint' => $this->l('Add more informations, like SIRET, APE...'),
                        'name' => 'EVERPSQUOTATION_TEXT',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Render PDF on validation page ?'),
                        'desc' => $this->l('Will download PDF on validation page without redirection'),
                        'hint' => $this->l('Else PDF will be sent by email only, validation page will be shown'),
                        'name' => 'EVERPSQUOTATION_RENDER_ON_VALIDATION',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Suggest unlogged users to log in ?'),
                        'desc' => $this->l('Suggests people who are not logged in to log in or create an account'),
                        'hint' => $this->l('Else a custom modal will be shown'),
                        'name' => 'EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'categories',
                        'name' => 'EVERPSQUOTATION_CATEGORIES',
                        'label' => $this->l('Category'),
                        'desc' => $this->l('Allow only these categories on quotations'),
                        'hint' => $this->l('Only products in selected categories will be allowed for quotes'),
                        'required' => true,
                        'tree' => $tree,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    private function postValidation()
    {
        if (Tools::isSubmit('submitEverpsquotationModule')) {
            if (Tools::getValue('EVERPSQUOTATION_DROP_SQL')
                && !Validate::isBool(Tools::getValue('EVERPSQUOTATION_DROP_SQL'))
            ) {
                $this->postErrors[] = $this->l('Error: drop quotations on uninstall is not valid');
            }

            if (!Tools::getValue('EVERPSQUOTATION_LOGO_WIDTH')
                || !Validate::isInt(Tools::getValue('EVERPSQUOTATION_LOGO_WIDTH'))
            ) {
                $this->postErrors[] = $this->l('Error: logo width is not valid');
            }

            if (!Tools::getIsset('EVERPSQUOTATION_ACCOUNT_EMAIL')
                || !Validate::isEmail(Tools::getValue('EVERPSQUOTATION_ACCOUNT_EMAIL'))
            ) {
                $this->postErrors[] = $this->l('Error: email is not valid');
            }
            if (!Tools::getIsset('EVERPSQUOTATION_CATEGORIES')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSQUOTATION_CATEGORIES'))
            ) {
                $this->postErrors[] = $this->l('Error: allowed categories is not valid');
            }
            if (!Tools::getIsset('EVERPSQUOTATION_GROUPS')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSQUOTATION_GROUPS'))
            ) {
                $this->postErrors[] = $this->l('Error: allowed groups is not valid');
            }
            if (!Tools::getIsset('EVERPSQUOTATION_PREFIX')
                || !Validate::isGenericName(Tools::getValue('EVERPSQUOTATION_PREFIX'))
            ) {
                $this->postErrors[] = $this->l('Error: allowed groups is not valid');
            }
            if (Tools::getValue('EVERPSQUOTATION_MIN_AMOUNT')
                && !Validate::isPrice(Tools::getValue('EVERPSQUOTATION_MIN_AMOUNT'))
            ) {
                $this->postErrors[] = $this->l('Error: minimum amount is not valid');
            }
            if (Tools::getValue('EVERPSQUOTATION_PRODUCT')
                && !Validate::isBool(Tools::getValue('EVERPSQUOTATION_PRODUCT'))
            ) {
                $this->postErrors[] = $this->l('Error: allow on product page is not valid');
            }
            if (Tools::getValue('EVERPSQUOTATION_RENDER_ON_VALIDATION')
                && !Validate::isBool(Tools::getValue('EVERPSQUOTATION_RENDER_ON_VALIDATION'))
            ) {
                $this->postErrors[] = $this->l('Error: render PDF on validation is not valid');
            }
            if (Tools::getValue('EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED')
                && !Validate::isBool(Tools::getValue('EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED'))
            ) {
                $this->postErrors[] = $this->l('Error: render PDF on validation is not valid');
            }

            // Multilingual validation
            foreach (Language::getLanguages(false) as $lang) {
                if (!Tools::getIsset('EVERPSQUOTATION_TEXT_'.$lang['id_lang'])
                    || !Validate::isCleanHtml(Tools::getValue('EVERPSQUOTATION_TEXT_'.$lang['id_lang']))
                ) {
                    $this->postErrors[] = $this->l(
                        'Error: text on footer is not valid for lang '
                    ).$lang['iso_code'];
                }
                if (!Tools::getIsset('EVERPSQUOTATION_MENTIONS_'.$lang['id_lang'])
                    || !Validate::isCleanHtml(Tools::getValue('EVERPSQUOTATION_MENTIONS_'.$lang['id_lang']))
                ) {
                    $this->postErrors[] = $this->l(
                        'Error: Mentions is not valid for lang '
                    ).$lang['iso_code'];
                }
                if (!Tools::getIsset('EVERPSQUOTATION_MAIL_SUBJECT_'.$lang['id_lang'])
                    || !Validate::isMailSubject(Tools::getValue('EVERPSQUOTATION_MAIL_SUBJECT_'.$lang['id_lang']))
                ) {
                    $this->postErrors[] = $this->l(
                        'Error: mail subject is not valid for lang '
                    ).$lang['iso_code'];
                }
                if (Tools::getIsset('EVERPSQUOTATION_FILENAME_'.$lang['id_lang'])
                    && !Validate::isGenericName(Tools::getValue('EVERPSQUOTATION_FILENAME_'.$lang['id_lang']))
                ) {
                    $this->postErrors[] = $this->l(
                        'Error: filename is not valid for lang '
                    ).$lang['iso_code'];
                }
            }
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $everpsquotation_subject = [];
        $everpsquotation_filename = [];
        $everpsquotation_text = [];
        foreach (Language::getLanguages(false) as $lang) {
            $everpsquotation_subject[$lang['id_lang']] = (
                Tools::getValue('EVERPSQUOTATION_MAIL_SUBJECT_'
                    .$lang['id_lang'])
            ) ? Tools::getValue(
                'EVERPSQUOTATION_MAIL_SUBJECT_'
                .$lang['id_lang']
            ) : '';
            $everpsquotation_filename[$lang['id_lang']] = (
                Tools::getValue('EVERPSQUOTATION_FILENAME_'
                    .$lang['id_lang'])
            ) ? Tools::getValue(
                'EVERPSQUOTATION_FILENAME_'
                .$lang['id_lang']
            ) : '';
            $everpsquotation_text[$lang['id_lang']] = (
                Tools::getValue('EVERPSQUOTATION_TEXT_'
                    .$lang['id_lang'])
            ) ? Tools::getValue(
                'EVERPSQUOTATION_TEXT_'
                .$lang['id_lang']
            ) : '';
            $everpsquotation_mentions[$lang['id_lang']] = (
                Tools::getValue('EVERPSQUOTATION_MENTIONS_'
                    .$lang['id_lang'])
            ) ? Tools::getValue(
                'EVERPSQUOTATION_MENTIONS_'
                .$lang['id_lang']
            ) : '';
        }
        
        Configuration::updateValue(
            'EVERPSQUOTATION_DROP_SQL',
            Tools::getValue('EVERPSQUOTATION_DROP_SQL')
        );
        Configuration::updateValue(
            'EVERPSQUOTATION_DURATION',
            Tools::getValue('EVERPSQUOTATION_DURATION')
        );
        Configuration::updateValue(
            'EVERPSQUOTATION_LOGO_WIDTH',
            Tools::getValue('EVERPSQUOTATION_LOGO_WIDTH')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_CATEGORIES',
            json_encode(Tools::getValue('EVERPSQUOTATION_CATEGORIES')),
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_GROUPS',
            json_encode(Tools::getValue('EVERPSQUOTATION_GROUPS')),
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_MIN_AMOUNT',
            Tools::getValue('EVERPSQUOTATION_MIN_AMOUNT')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_PRODUCT',
            Tools::getValue('EVERPSQUOTATION_PRODUCT')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_ACCOUNT_EMAIL',
            Tools::getValue('EVERPSQUOTATION_ACCOUNT_EMAIL')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_PREFIX',
            Tools::getValue('EVERPSQUOTATION_PREFIX')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_MAIL_SUBJECT',
            $everpsquotation_subject,
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_FILENAME',
            $everpsquotation_filename,
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_TEXT',
            $everpsquotation_text,
            true
        );
        Configuration::updateValue(
            'EVERPSQUOTATION_MENTIONS',
            $everpsquotation_mentions,
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_RENDER_ON_VALIDATION',
            Tools::getValue('EVERPSQUOTATION_RENDER_ON_VALIDATION')
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED',
            Tools::getValue('EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED')
        );

        /* Uploads image */
        $type = Tools::strtolower(Tools::substr(strrchr($_FILES['image']['name'], '.'), 1));
        $imagesize = @getimagesize($_FILES['image']['tmp_name']);
        if (isset($_FILES['image']) &&
            isset($_FILES['image']['tmp_name']) &&
            !empty($_FILES['image']['tmp_name']) &&
            !empty($imagesize) &&
            in_array(
                Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)),
                [
                    'jpg',
                    'gif',
                    'jpeg',
                    'png',
                ]
            ) &&
            in_array($type, ['jpg', 'gif', 'jpeg', 'png'])
        ) {
            $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');

            if ($error = ImageManager::validateUpload($_FILES['image'])) {
                $this->postErrors[] = $error;
            } elseif (!$temp_name
                || !move_uploaded_file($_FILES['image']['tmp_name'], $temp_name)
            ) {
                $this->postErrors[] = $this->l('An error occurred during the image upload process.');
            } elseif (!ImageManager::resize(
                $temp_name,
                dirname(__FILE__).'/views/img/quotation.jpg',
                null,
                null,
                $type
            )) {
                $this->postErrors[] = $this->l('An error occurred during the image upload process.');
            }

            if (isset($temp_name)) {
                @unlink($temp_name);
            }
        }
        $this->postSuccess[] = $this->l('All settings have been saved :-)');
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'EVERPSQUOTATION_CATEGORIES' => Tools::getValue(
                'EVERPSQUOTATION_CATEGORIES',
                json_decode(
                    Configuration::get(
                        'EVERPSQUOTATION_CATEGORIES'
                    )
                )
            ),
            'EVERPSQUOTATION_GROUPS[]' => Tools::getValue(
                'EVERPSQUOTATION_GROUPS',
                json_decode(
                    Configuration::get(
                        'EVERPSQUOTATION_GROUPS',
                        (int)$this->context->language->id
                    )
                )
            ),
            'EVERPSQUOTATION_MIN_AMOUNT' => Tools::getValue(
                'EVERPSQUOTATION_MIN_AMOUNT',
                Configuration::get(
                    'EVERPSQUOTATION_MIN_AMOUNT',
                    (int)$this->context->language->id
                )
            ),
            'EVERPSQUOTATION_PRODUCT' => Tools::getValue(
                'EVERPSQUOTATION_PRODUCT',
                Configuration::get(
                    'EVERPSQUOTATION_PRODUCT',
                    (int)$this->context->language->id
                )
            ),
            'EVERPSQUOTATION_DROP_SQL' => Tools::getValue(
                'EVERPSQUOTATION_DROP_SQL',
                Configuration::get(
                    'EVERPSQUOTATION_DROP_SQL'
                )
            ),
            'EVERPSQUOTATION_LOGO_WIDTH' => Tools::getValue(
                'EVERPSQUOTATION_LOGO_WIDTH',
                Configuration::get(
                    'EVERPSQUOTATION_LOGO_WIDTH'
                )
            ),
            'EVERPSQUOTATION_ACCOUNT_EMAIL' => Tools::getValue(
                'EVERPSQUOTATION_ACCOUNT_EMAIL',
                Configuration::get(
                    'EVERPSQUOTATION_ACCOUNT_EMAIL'
                )
            ),
            'EVERPSQUOTATION_PREFIX' => Tools::getValue(
                'EVERPSQUOTATION_PREFIX',
                Configuration::get(
                    'EVERPSQUOTATION_PREFIX'
                )
            ),
            'EVERPSQUOTATION_RENDER_ON_VALIDATION' => Tools::getValue(
                'EVERPSQUOTATION_RENDER_ON_VALIDATION',
                Configuration::get(
                    'EVERPSQUOTATION_RENDER_ON_VALIDATION'
                )
            ),
            'EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED' => Tools::getValue(
                'EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED',
                Configuration::get(
                    'EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED'
                )
            ),
            'EVERPSQUOTATION_MAIL_SUBJECT' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_MAIL_SUBJECT'
            ),
            'EVERPSQUOTATION_FILENAME' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_FILENAME'
            ),
            'EVERPSQUOTATION_TEXT' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_TEXT'
            ),
            'EVERPSQUOTATION_MENTIONS' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_MENTIONS'
            ),
            'EVERPSQUOTATION_DURATION' => Configuration::get(
                'EVERPSQUOTATION_DURATION'
            ),
        ];
    }

    /**
     * Hook payment, PS 1.7 only.
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $cart = $this->context->cart;
        $total_cart = $this->context->cart->getOrderTotal(
            false,
            Cart::BOTH_WITHOUT_SHIPPING,
            null,
            null,
            true
        );
        if ((float)Configuration::get('EVERPSQUOTATION_MIN_AMOUNT') > 0
            && $total_cart < Configuration::get('EVERPSQUOTATION_MIN_AMOUNT')) {
            return;
        }
        $cartproducts = $cart->getProducts();
        $customerGroups = Customer::getGroupsStatic((int)$cart->id_customer);
        $selected_cat = $this->getAllowedCategories();
        $allowed_groups = $this->getAllowedGroups();
        foreach ($cartproducts as $cartproduct) {
            $product = new Product((int)$cartproduct['id_product']);
            if (!in_array($product->id_category_default, $selected_cat)) {
                return;
            }
            if ($product->visibility == 'none'
                || (bool)$product->available_for_order === false
                || (bool)$product->show_price === false
            ) {
                return;
            }
        }
        if (!array_intersect($allowed_groups, $customerGroups)
            || empty($allowed_groups)
        ) {
            return;
        }

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Request for a quote'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation(
                $this->fetch('module:everpsquotation/views/templates/front/payment_infos.tpl')
            );

        return [$newOption];
    }
    
    public function hookHeader($params)
    {
        $controller_name = Tools::getValue('controller');
        $token = Tools::encrypt(
            $this->name . '_token/setquote'
        );
        $quoteAjaxLink = $this->context->link->getModuleLink(
            $this->name,
            'quote',
            [
                'action' => 'SetQuote',
                'token' => $token,
            ]
        );
        $quoteRequestAjaxLink = $this->context->link->getModuleLink(
            $this->name,
            'mail',
            [
                'action' => 'SetRequest',
                'token' => $token,
            ]
        );
        if (!$this->context->customer->isLogged()) {
            $this->context->controller->registerJavascript(
                'module-modal-' . $this->name,
                'modules/' . $this->name . '/views/js/modal.js',
                [
                    'attributes' => 'defer'
                ]
            );
        } else {
            $this->context->controller->registerJavascript(
                'module-' . $this->name,
                'modules/' . $this->name . '/views/js/' . $this->name . '.js',
                [
                    'attributes' => 'defer'
                ]
            );
        }
        Media::addJsDef([
            $this->name . '_quote_link ' => $quoteAjaxLink,
            $this->name . '_quoterequest_link ' => $quoteRequestAjaxLink,
        ]);
        if ($controller_name == 'product') {
            $this->context->controller->addJs($this->_path.'views/js/createProductQuote.js');
            $this->context->controller->addCss($this->_path.'views/css/everpsquotation.css');
        }
    }

    public function hookDisplayAdminEndContent($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'AdminCarts')
        {
            $token = Tools::getAdminToken('AdminEverPsQuotation'.(int)Tab::getIdFromClassName('AdminEverPsQuotation').(int)Context::getContext()->employee->id);
            $id_cart = Tools::getValue('id_cart');
            $cart = new Cart(
                (int) $id_cart
            );
            $cartproducts = $cart->getProducts();
            if (count($cartproducts) <= 0) {
                return;
            }
            $href = 'index.php?controller=AdminEverPsQuotation&transformThisCartId=' . $id_cart . '&token=' . $token;
            return '<a class="btn btn-default" href="' . $href . '"><i class="icon-shopping-cart"></i> ' . $this->l('Create a quotation from this cart') . '</a>';
        }
    }

    public function hookDisplayShoppingCartFooter()
    {
        $suggestLogIn = Configuration::get('EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED');
        if ((bool) $suggestLogIn === true && !$this->context->customer->isLogged()) {
            return;
        }
        return $this->display(__FILE__, 'views/templates/hook/form.tpl');
    }

    public function hookDisplayCartModalFooter()
    {
        return $this->hookDisplayShoppingCart();
    }

    public function hookDisplayLeftColumn()
    {
        return $this->hookDisplayShoppingCart();
    }

    public function hookDisplayRightColumn()
    {
        return $this->hookDisplayShoppingCart();
    }

    public function hookDisplayShoppingCart()
    {
        $total_cart = $this->context->cart->getOrderTotal(
            false,
            Cart::BOTH_WITHOUT_SHIPPING,
            null,
            null,
            true
        );
        if ($total_cart <= 0) {
            return;
        }
        if ((float)Configuration::get('EVERPSQUOTATION_MIN_AMOUNT') > 0
            && $total_cart < Configuration::get('EVERPSQUOTATION_MIN_AMOUNT')) {
            return;
        }
        $validationUrl = Context::getContext()->link->getModuleLink(
            $this->name,
            'validation',
            [],
            true
        );
        $this->context->smarty->assign([
            'validationUrl' => $validationUrl,
        ]);
        if (!$this->context->customer->isLogged()) {
            return $this->display(__FILE__, 'views/templates/hook/unlogged.tpl');
        }
        $address = Address::getFirstCustomerAddressId((int)$this->context->customer->id);
        if (!$address) {
            return $this->display(__FILE__, 'views/templates/hook/noaddress.tpl');
        }
        $cart = $this->context->cart;
        $cartproducts = $cart->getProducts();
        $customerGroups = Customer::getGroupsStatic((int)$this->context->customer->id);
        $selected_cat = $this->getAllowedCategories();
        $allowed_groups = $this->getAllowedGroups();
        foreach ($cartproducts as $cartproduct) {
            $product = new Product((int)$cartproduct['id_product']);
            if (!in_array($product->id_category_default, $selected_cat)) {
                return;
            }
        }
        if (!array_intersect($allowed_groups, $customerGroups)
            || empty($allowed_groups)
        ) {
            return;
        }

        return $this->display(__FILE__, 'views/templates/hook/cartbutton.tpl');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] != 'weight') {
            return;
        }
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayProductPriceRight()
    {
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayAfterProductActions()
    {
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayProductCenterColumn()
    {
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayReassurance()
    {
        $product = new Product(
            (int)Tools::getValue('id_product')
        );
        if ($product->visibility == 'none'
            || (bool)$product->available_for_order === false
            || (bool)$product->show_price === false
        ) {
            return;
        }
        if ((float)Configuration::get('EVERPSQUOTATION_MIN_AMOUNT') > 0
            && $product->price < Configuration::get('EVERPSQUOTATION_MIN_AMOUNT')) {
            return;
        }
        $link = new Link();
        $selected_cat = $this->getAllowedCategories();
        $allowed_groups = $this->getAllowedGroups();
        $customerGroups = Customer::getGroupsStatic((int) $this->context->customer->id);
        $address = Address::getFirstCustomerAddressId((int) $this->context->customer->id);
        $id_shop = (int) $this->context->shop->id;
        $my_quotations_link = Context::getContext()->link->getModuleLink(
            'everpsquotation',
            'quotations',
            [],
            true
        );
        if (!in_array($product->id_category_default, $selected_cat)
            || !Configuration::get('PS_REWRITING_SETTINGS')
        ) {
            return;
        }
        if ((bool) Configuration::get('EVERPSQUOTATION_PRODUCT') === true) {
            if (Configuration::isCatalogMode()) {
                $catalogMode = true;
            } else {
                $catalogMode = false;
            }
            $this->context->smarty->assign([
                'everid_product' => (int) Tools::getValue('id_product'),
                'my_quotations_link' => $my_quotations_link,
                'cart_url' => $link->getPageLink('cart', true),
                'my_account_url' => $link->getPageLink('my-account', true),
                'address_url' => $link->getPageLink('address', true),
                'catalogMode' => $catalogMode,
                'selected_cat' => $selected_cat,
                'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int) $id_shop),
                'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int) $id_shop),
            ]);
            if (!$this->context->customer->isLogged()) {
                return $this->display(__FILE__, 'views/templates/hook/unlogged.tpl');
            }
            if (!array_intersect($allowed_groups, $customerGroups)
                || empty($allowed_groups)
            ) {
                return;
            }
            if (!$address) {
                return $this->display(__FILE__, 'views/templates/hook/noaddress.tpl');
            } else {
                return $this->display(__FILE__, 'views/templates/hook/productbutton.tpl');
            }
        }
    }

    public function hookDisplayProductExtraContent()
    {
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayFooterProduct()
    {
        return $this->hookDisplayReassurance();
    }

    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/front/myaccount.tpl');
    }

    private function getAllowedCategories()
    {
        $selectedCategories = Configuration::get(
            'EVERPSQUOTATION_CATEGORIES'
        );
        if (!$selectedCategories) {
            $selectedCategories = [];
        } else {
            $selectedCategories = json_decode($selectedCategories);
        }
        return $selectedCategories;
    }

    private function getAllowedGroups()
    {
        $allowedGroups = Configuration::get(
            'EVERPSQUOTATION_GROUPS'
        );
        if (!$allowedGroups) {
            $allowedGroups = [];
        } else {
            $allowedGroups = json_decode($allowedGroups);
        }
        return $allowedGroups;
    }

    public function checkLatestEverModuleVersion()
    {
        $upgrade_link = 'https://upgrade.team-ever.com/upgrade.php?module='
        . $this->name
        .'&version='
        . $this->version;
        try {
            $handle = curl_init($upgrade_link);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            if ($httpCode != 200) {
                return false;
            }
            $module_version = Tools::file_get_contents(
                $upgrade_link
            );
            if ($module_version && $module_version > $this->version) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Unable to check Team Ever module upgrade');
            return false;
        }
    }

    public static function getConfigInMultipleLangs($key, $idShopGroup = null, $idShop = null)
    {
        $resultsArray = [];
        foreach (Language::getIDs() as $idLang) {
            $resultsArray[$idLang] = Configuration::get($key, $idLang, $idShopGroup, $idShop);
        }
        return $resultsArray;
    }
}
