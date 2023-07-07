<?php
/**
 * 2019-2023 Team Ever
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

// use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCart.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCartProduct.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

class Everpsquotation extends PaymentModule
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = 'everpsquotation';
        $this->tab = 'payments_gateways';
        $this->version = '4.1.2';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ever PS Quotation');
        $this->description = $this->l('Simply accept quotations on your Prestashop !');
        $this->confirmUninstall = $this->l('Do you REALLY want to uninstall this awesome module ?');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
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
        $sql = array();
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
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('displayReassurance')
            && $this->registerHook('paymentOptions')
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
        if ((bool)Configuration::get('EVERPSQUOTATION_DROP_SQL') === true) {
            // Uninstall SQL
            $sql = array();
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
            'id_module = '.(int)$this->id
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
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->position = Tab::getNewLastPosition($tab->id_parent);
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int)$lang['id_lang']] = 'Devis';
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
        $tab = new Tab((int)Tab::getIdFromClassName($tabClass));
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

        $this->context->smarty->assign(array(
            'quote_controller_link' => $quote_controller_link,
            'everpsquotation_dir' => $this->_path,
            'rewrite_mode' => $rewriteMode,
        ));

        if ($this->checkLatestEverModuleVersion($this->name, $this->version)) {
            $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/upgrade.tpl');
        }
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

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
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $selected_cat = json_decode(
            Configuration::get(
                'EVERPSQUOTATION_CATEGORIES'
            )
        );
        if (!is_array($selected_cat)) {
            $selected_cat = array($selected_cat);
        }
        $tree = array(
            'selected_categories' => $selected_cat,
            'use_search' => true,
            'use_checkbox' => true,
            'id' => 'id_category_tree',
        );

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Drop all quotations on module uninstall ?'),
                        'desc' => $this->l('Will delete all quotations on module uninstall'),
                        'hint' => $this->l('Else all quotations will be keeped on module uninstall'),
                        'name' => 'EVERPSQUOTATION_DROP_SQL',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'categories',
                        'name' => 'EVERPSQUOTATION_CATEGORIES',
                        'label' => $this->l('Category'),
                        'desc' => $this->l('Allow only these categories on quotations'),
                        'hint' => $this->l('Only products in selected categories will be allowed for quotes'),
                        'required' => true,
                        'tree' => $tree,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Allowed customer groups'),
                        'desc' => $this->l('Choose allowed groups, customers must be logged'),
                        'hint' => $this->l('Customers must be logged and have a registered address'),
                        'name' => 'EVERPSQUOTATION_GROUPS[]',
                        'class' => 'chosen',
                        'identifier' => 'name',
                        'multiple' => true,
                        'options' => array(
                            'query' => Group::getGroups(
                                (int)Context::getContext()->cookie->id_lang,
                                (int)$this->context->shop->id
                            ),
                            'id' => 'id_group',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Quotation minimum amount'),
                        'desc' => $this->l('Minimum amount without taxes to allow quotations'),
                        'hint' => $this->l('Leave empty for no use'),
                        'name' => 'EVERPSQUOTATION_MIN_AMOUNT',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable quotes creation on product pages'),
                        'desc' => $this->l('Will show a "download quotation" on product page'),
                        'hint' => $this->l('Will show a button next to "Add to cart".'),
                        'name' => 'EVERPSQUOTATION_PRODUCT',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Logo width'),
                        'desc' => $this->l('Logo width (pixel value) on quotes'),
                        'hint' => $this->l('Will define your logo width on quotes'),
                        'name' => 'EVERPSQUOTATION_LOGO_WIDTH',
                        'required' => true,
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'label' => $this->l('Email'),
                        'desc' => $this->l('Admin email for quotation mails copy'),
                        'hint' => $this->l('Leave empty for no use'),
                        'name' => 'EVERPSQUOTATION_ACCOUNT_EMAIL',
                    ),
                    // multilingual
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-download"></i>',
                        'label' => $this->l('Quotation prefix'),
                        'desc' => $this->l('Please specify quotation prefix'),
                        'hint' => $this->l('Every quote will start with this prefix'),
                        'name' => 'EVERPSQUOTATION_PREFIX',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Quotation mail subject'),
                        'desc' => $this->l('Please specify subject of mails send'),
                        'hint' => $this->l('Quotations will be sent by email using tihs subject'),
                        'name' => 'EVERPSQUOTATION_MAIL_SUBJECT',
                    ),
                    array(
                        'type' => 'textarea',
                        'lang' => true,
                        'label' => $this->l('File name for quotations'),
                        'desc' => $this->l('PDF filename'),
                        'hint' => $this->l('Every quote file will have this name. Required.'),
                        'name' => 'EVERPSQUOTATION_FILENAME',
                        'required' => true,
                    ),
                    array(
                        'type' => 'textarea',
                        'autoload_rte' => true,
                        'lang' => true,
                        'label' => $this->l('Quotation text on footer'),
                        'desc' => $this->l('Please specify quotation text on footer'),
                        'hint' => $this->l('Add more informations, like SIRET, APE...'),
                        'name' => 'EVERPSQUOTATION_TEXT',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Render PDF on validation page ?'),
                        'desc' => $this->l('Will download PDF on validation page without redirection'),
                        'hint' => $this->l('Else PDF will be sent by email only, validation page will be shown'),
                        'name' => 'EVERPSQUOTATION_RENDER_ON_VALIDATION',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
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

            // Multilingual validation
            foreach (Language::getLanguages(false) as $lang) {
                if (!Tools::getIsset('EVERPSQUOTATION_TEXT_'.$lang['id_lang'])
                    || !Validate::isCleanHtml(Tools::getValue('EVERPSQUOTATION_TEXT_'.$lang['id_lang']))
                ) {
                    $this->postErrors[] = $this->l(
                        'Error: text on footer is not valid for lang '
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
        $everpsquotation_subject = array();
        $everpsquotation_filename = array();
        $everpsquotation_text = array();
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
        }
        Configuration::updateValue(
            'EVERPSQUOTATION_DROP_SQL',
            Tools::getValue('EVERPSQUOTATION_DROP_SQL')
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
            'EVERPSQUOTATION_RENDER_ON_VALIDATION',
            Tools::getValue('EVERPSQUOTATION_RENDER_ON_VALIDATION')
        );

        $this->postSuccess[] = $this->l('All settings have been saved :-)');
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
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
            'EVERPSQUOTATION_MAIL_SUBJECT' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_MAIL_SUBJECT'
            ),
            'EVERPSQUOTATION_FILENAME' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_FILENAME'
            ),
            'EVERPSQUOTATION_TEXT' => self::getConfigInMultipleLangs(
                'EVERPSQUOTATION_TEXT'
            ),
        );
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
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation(
                $this->fetch('module:everpsquotation/views/templates/front/payment_infos.tpl')
            );

        return array($newOption);
    }
    
    public function hookHeader($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product') {
            $this->context->controller->addJs($this->_path.'views/js/createProductQuote.js');
            $this->context->controller->addCss($this->_path.'views/css/everpsquotation.css');
            $product = new Product(
                (int)Tools::getValue('id_product'),
                false,
                (int)$this->context->shop->id,
                (int)$this->context->language->id
            );

            if (Tools::isSubmit('everpsproductquotation')) {
                if (Tools::getValue('everid_product_attribute')) {
                    $id_attribute = (int)Tools::getValue('everid_product_attribute');
                } else {
                    $id_attribute = (int)Tools::getValue('id_product_attribute');
                }
                if (!$id_attribute) {
                    $id_attribute = 0;
                }
                if ((int)Tools::getValue('ever_qty') < (int)$product->minimal_quantity) {
                    $quantity = (int)$product->minimal_quantity;
                } else {
                    $quantity = (int)Tools::getValue('ever_qty');
                }
                $this->createSimpleProductQuote(
                    (int)Tools::getValue('id_product'),
                    (int)$id_attribute,
                    (int)Tools::getValue('id_customization'),
                    (int)$quantity
                );
            }
        }
    }

    public function hookDisplayShoppingCartFooter()
    {
        return $this->hookDisplayShoppingCart();
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
            array(),
            true
        );
        $this->context->smarty->assign(array(
            'validationUrl' => $validationUrl,
        ));
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
        $customerGroups = Customer::getGroupsStatic((int)$this->context->customer->id);
        $address = Address::getFirstCustomerAddressId((int)$this->context->customer->id);
        $id_shop = (int)Context::getContext()->shop->id;
        $my_quotations_link = Context::getContext()->link->getModuleLink(
            'everpsquotation',
            'quotations',
            array(),
            true
        );
        if (!in_array($product->id_category_default, $selected_cat)
            || !Configuration::get('PS_REWRITING_SETTINGS')
        ) {
            return;
        }
        if ((bool)Configuration::get('EVERPSQUOTATION_PRODUCT') === true) {
            if (Configuration::isCatalogMode()) {
                $catalogMode = true;
            } else {
                $catalogMode = false;
            }
            $this->context->smarty->assign(array(
                'my_quotations_link' => $my_quotations_link,
                'cart_url' => $link->getPageLink('cart', true),
                'my_account_url' => $link->getPageLink('my-account', true),
                'address_url' => $link->getPageLink('address', true),
                'catalogMode' => $catalogMode,
                'selected_cat' => $selected_cat,
                'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
                'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
            ));

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
        $selected_cat = json_decode(
            Configuration::get(
                'EVERPSQUOTATION_CATEGORIES'
            )
        );
        if (!is_array($selected_cat)) {
            $selected_cat = array($selected_cat);
        }
        return $selected_cat;
    }

    private function getAllowedGroups()
    {
        $allowed_groups = json_decode(
            Configuration::get(
                'EVERPSQUOTATION_GROUPS'
            )
        );
        if (!is_array($allowed_groups)) {
            $allowed_groups = array($allowed_groups);
        }
        return $allowed_groups;
    }

    private function createSimpleProductQuote($id_product, $id_product_attribute, $id_customization, $qty)
    {
        $cart = Context::getContext()->cart;
        Hook::exec('actionBeforeCreateEverQuote');

        // First create Quote Cart
        $ever_cart = new EverpsquotationCart();
        $ever_cart->id_shop_group = $cart->id_shop_group;
        $ever_cart->id_shop = $cart->id_shop;
        $ever_cart->id_carrier = $cart->id_carrier;
        $ever_cart->delivery_option = $cart->delivery_option;
        $ever_cart->id_lang = $cart->id_lang;
        $ever_cart->id_address_delivery = $cart->id_address_delivery;
        $ever_cart->id_address_invoice = $cart->id_address_invoice;
        $ever_cart->id_currency = $cart->id_currency;
        $ever_cart->id_customer = $cart->id_customer;
        $ever_cart->id_guest = $cart->id_guest;
        $ever_cart->secure_key = $cart->secure_key;
        $ever_cart->recyclable = $cart->recyclable;
        $ever_cart->allow_seperated_package = $cart->allow_seperated_package;
        $ever_cart->date_add = $cart->date_add;
        $ever_cart->date_upd = $cart->date_upd;
        $ever_cart->save();

        // Then add product
        $ever_cart->addProductToQuoteCart(
            (int)$id_product,
            (int)$id_product_attribute,
            (int)$id_customization,
            (int)$qty
        );

        // Get ever cart informations
        $cart_details = $ever_cart->getSummaryDetails(
            (int)$cart->id
        );
        $cart_products = $ever_cart->getProducts(
            (int)$cart->id
        );

        // Now create quotation
        $quote = new EverpsquotationClass();
        $quote->reference = (string)Configuration::get('EVERPSQUOTATION_PREFIX');
        $quote->id_shop_group = (int)$cart->id_shop_group;
        $quote->id_shop = (int)$cart->id_shop;
        $quote->id_carrier = (int)$cart->id_carrier;
        $quote->id_lang = (int)$cart->id_lang;
        $quote->id_customer = (int)$cart->id_customer;
        $quote->id_cart = (int)$cart->id;
        $quote->id_currency = (int)$cart->id_currency;
        $quote->id_address_delivery = (int)$cart->id_address_delivery;
        $quote->id_address_invoice = (int)$cart->id_address_invoice;
        $quote->secure_key = (string)$cart->secure_key;
        $quote->recyclable = (int)$cart->recyclable;
        $quote->total_discounts = (float)$cart_details['total_discounts'];
        $quote->total_discounts_tax_incl = (float)$cart_details['total_discounts'];
        $quote->total_discounts_tax_excl = (float)$cart_details['total_discounts_tax_exc'];
        $quote->total_paid_tax_incl = (float)$cart_details['total_price'];
        $quote->total_paid_tax_excl = (float)$cart_details['total_price_without_tax'];
        $quote->total_products = (float)$cart_details['total_products'];
        $quote->total_products_wt = (float)$cart_details['total_products_wt'];
        $quote->total_shipping = (float)$cart_details['total_shipping'];
        $quote->total_shipping_tax_incl = (float)$cart_details['total_shipping'];
        $quote->total_shipping_tax_excl = (float)$cart_details['total_shipping_tax_exc'];
        $quote->total_wrapping = (float)$cart_details['total_wrapping'];
        $quote->total_wrapping_tax_incl = (float)$cart_details['total_wrapping'];
        $quote->total_wrapping_tax_excl = (float)$cart_details['total_wrapping_tax_exc'];
        $quote->valid = 0;
        $quote->date_add = $cart->date_add;
        $quote->date_upd = $cart->date_upd;
        $quote->save();

        // Add ever cart to quotation details
        foreach ($cart_products as $cart_product) {
            $product_stock = StockAvailable::getQuantityAvailableByProduct(
                (int)$cart_product['id_product'],
                (int)$cart_product['id_product_attribute']
            );
            $price_with_tax = Product::getPriceStatic(
                (int)$cart_product['id_product'],
                true,
                (int)$cart_product['id_product_attribute']
            );
            $price_without_tax = Product::getPriceStatic(
                (int)$cart_product['id_product'],
                false,
                (int)$cart_product['id_product_attribute']
            );
            $total_wt = (float)$price_with_tax * (int)$cart_product['cart_quantity'];
            $total = (float)$price_without_tax * (int)$cart_product['cart_quantity'];
            // $product_taxes = $price_with_tax - $price_without_tax;
            // $total_product_taxes = $total_wt - $total;
            // die(var_dump($price_without_tax));
            $quotedetail = new EverpsquotationDetail();
            $quotedetail->id_everpsquotation_quotes = (int)$quote->id;
            // $quotedetail->id_warehouse = (int)$cart_details['total_discounts']['id_warehouse'];
            $quotedetail->id_shop = (int)$cart_product['id_shop'];
            $quotedetail->product_id = (int)$cart_product['id_product'];
            $quotedetail->product_attribute_id = (int)$cart_product['id_product_attribute'];
            $quotedetail->id_customization = (int)$cart_product['id_customization'];
            $quotedetail->product_name = (string)$cart_product['name'];
            $quotedetail->product_quantity = (int)$cart_product['cart_quantity'];
            $quotedetail->product_quantity_in_stock = (int)$product_stock;
            $quotedetail->product_price = (float)$price_without_tax;
            $quotedetail->product_ean13 = (string)$cart_product['ean13'];
            $quotedetail->product_isbn = (string)$cart_product['isbn'];
            $quotedetail->product_upc = (string)$cart_product['upc'];
            $quotedetail->product_reference = (string)$cart_product['reference'];
            $quotedetail->product_supplier_reference = (string)$cart_product['supplier_reference'];
            $quotedetail->product_weight = (float)$cart_product['weight'];
            // $quotedetail->tax_name = (string)$cart_product['tax_name'];
            $quotedetail->ecotax = (float)$cart_product['ecotax'];
            $quotedetail->unit_price_tax_excl = (float)$price_without_tax;
            $quotedetail->total_price_tax_incl = (float)$total_wt;
            $quotedetail->total_price_tax_excl = (float)$total;
            $quotedetail->add();
        }
        Hook::exec('actionAfterCreateEverQuote');
        
        //Preparing emails
        if (Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL')) {
            $everShopEmail = Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL');
        } else {
            $everShopEmail = Configuration::get('PS_SHOP_EMAIL');
        }

        // Subject
        $ever_subject = self::getConfigInMultipleLangs('EVERPSQUOTATION_MAIL_SUBJECT');
        $subject = $ever_subject[(int)Context::getContext()->language->id];
        // Filename
        $filename = self::getConfigInMultipleLangs('EVERPSQUOTATION_FILENAME');
        $ever_filename = $filename[(int)Context::getContext()->language->id];

        $id_shop = (int)Context::getContext()->shop->id;
        $mailDir = _PS_MODULE_DIR_.'everpsquotation/mails/';
        $pdf = new PDF($quote->id, 'EverQuotationPdf', Context::getContext()->smarty);
        $customer = Context::getContext()->customer;
        $customerNames = $customer->firstname.' '.$customer->lastname;
        $attachment = array();
        $attachment['content'] = $pdf->render(false);
        $attachment['name'] = $ever_filename;
        $attachment['mime'] = 'application/pdf';
        Mail::send(
            (int)$this->context->language->id,
            'everquotecustomer',
            (string)$subject,
            array(
                '{shop_name}'=>Configuration::get('PS_SHOP_NAME'),
                '{shop_logo}'=>_PS_IMG_DIR_.Configuration::get(
                    'PS_LOGO',
                    null,
                    null,
                    (int)$id_shop
                ),
                '{firstname}' => (string)$customer->firstname,
                '{lastname}' => (string)$customer->lastname,
            ),
            (string)$customer->email,
            (string)$customerNames,
            (string)$everShopEmail,
            Configuration::get('PS_SHOP_NAME'),
            $attachment,
            null,
            $mailDir,
            false,
            null,
            (string)$everShopEmail,
            (string)$everShopEmail,
            Configuration::get('PS_SHOP_NAME')
        );

        // Render PDF for direct download
        $pdf = new PDF($quote->id, 'EverQuotationPdf', Context::getContext()->smarty);
        $pdf->render();
    }

    public function checkLatestEverModuleVersion($module, $version)
    {
        $upgrade_link = 'https://upgrade.team-ever.com/upgrade.php?module='
        .$module
        .'&version='
        .$version;
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
            if ($module_version && $module_version > $version) {
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
