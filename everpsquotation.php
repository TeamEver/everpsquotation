<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCart.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationCartProduct.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';

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
        $this->version = '2.2.21';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ever PS Quotation');
        $this->description = $this->l('Simply accept quotations on your Prestashop !');
        $this->confirmUninstall = $this->l('Do you REALLY want to uninstall this awesome module ?');
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * The install method
     *
     * @see prestashop/classes/Module#install()
     */
    public function install()
    {
        // Install SQL
        $sql = array();
        include(dirname(__FILE__).'/sql/install.php');
        foreach ($sql as $s) {
            if (!Db::getInstance()->execute($s)) {
                return false;
            }
        }
        if ($this->isSeven) {
            $paymenthook = 'paymentOptions';
        } else {
            $paymenthook = 'payment';
        }
        return (parent::install()
            && $this->registerHook('header')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('LeftColumn')
            && $this->registerHook('RightColumn')
            && $this->registerHook('displayReassurance')
            && $this->registerHook($paymenthook)
            && $this->installModuleTab('AdminEverPsQuotation'));
    }

    /**
     * The uninstall method
     *
     * @see prestashop/classes/Module#uninstall()
     */
    public function uninstall()
    {
        // Uninstall SQL
        $sql = array();
        include(dirname(__FILE__).'/sql/uninstall.php');
        foreach ($sql as $s) {
            if (!Db::getInstance()->execute($s)) {
                return false;
            }
        }

        return (parent::uninstall()
            && Configuration::deleteByName('EVERPSQUOTATION_ACCOUNT_EMAIL')
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

        $this->context->smarty->assign(array(
            'everpsquotation_dir' => $this->_path,
            'rewrite_mode' => $rewriteMode,
        ));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
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
                        'type' => 'categories',
                        'name' => 'EVERPSQUOTATION_CATEGORIES',
                        'label' => $this->l('Category'),
                        'required' => true,
                        'hint' => 'Only products in selected categories will be allowed for quotes',
                        'tree' => $tree,
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Allowed customer groups',
                        'hint' => 'Choose allowed groups, customers must be logged',
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
                        'type' => 'switch',
                        'label' => $this->l('Enable quotes creation on product pages'),
                        'name' => 'EVERPSQUOTATION_PRODUCT',
                        'is_bool' => true,
                        'desc' => $this->l('Will show a "download quotation" on product page'),
                        'hint' => 'Will show a button next to "Add to cart".',
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
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Admin email for quotation mails copy'),
                        'name' => 'EVERPSQUOTATION_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                        'hint' => 'Leave empty for no use',
                    ),
                    // multilingual
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-download"></i>',
                        'desc' => $this->l('Please specify quotation prefix'),
                        'name' => 'EVERPSQUOTATION_PREFIX',
                        'label' => $this->l('Quotation prefix'),
                        'hint' => 'Every quote will start with this prefix',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'lang' => true,
                        'desc' => $this->l('Please specify subject of mails send'),
                        'name' => 'EVERPSQUOTATION_MAIL_SUBJECT',
                        'label' => $this->l('Quotation mail subject'),
                        'hint' => 'Quotations will be sent by email using tihs subject',
                    ),
                    array(
                        'type' => 'textarea',
                        'lang' => true,
                        'desc' => $this->l('PDF filename'),
                        'name' => 'EVERPSQUOTATION_FILENAME',
                        'label' => $this->l('File name for quotations'),
                        'hint' => 'Every quote file will have this name. Required.',
                    ),
                    array(
                        'type' => 'textarea',
                        'autoload_rte' => true,
                        'lang' => true,
                        'desc' => $this->l('Please specify quotation text on footer'),
                        'name' => 'EVERPSQUOTATION_TEXT',
                        'label' => $this->l('Quotation text on footer'),
                        'hint' => 'Add more informations, like SIRET, APE...',
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
            if (Tools::getValue('EVERPSQUOTATION_PRODUCT')
                && !Validate::isBool(Tools::getValue('EVERPSQUOTATION_PRODUCT'))
            ) {
                $this->postErrors[] = $this->l('Error: allow on product page is not valid');
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
            'EVERPSQUOTATION_CATEGORIES',
            Tools::jsonEncode(Tools::getValue('EVERPSQUOTATION_CATEGORIES')),
            true
        );

        Configuration::updateValue(
            'EVERPSQUOTATION_GROUPS',
            Tools::jsonEncode(Tools::getValue('EVERPSQUOTATION_GROUPS')),
            true
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
        $this->postSuccess[] = $this->l('All settings have been saved :-)');
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $groupShop = Shop::getGroupFromShop((int)$this->context->shop->id);
        $everpsquotation_subject = array();
        $everpsquotation_filename = array();
        $everpsquotation_text = array();
        foreach (Language::getLanguages(false) as $lang) {
            $everpsquotation_subject[$lang['id_lang']] = (Tools::getValue(
                'EVERPSQUOTATION_MAIL_SUBJECT_'.$lang['id_lang']
            )) ? Tools::getValue(
                'EVERPSQUOTATION_MAIL_SUBJECT_'.$lang['id_lang']
            ) : '';
            $everpsquotation_filename[$lang['id_lang']] = (Tools::getValue(
                'EVERPSQUOTATION_FILENAME_'.$lang['id_lang']
            )) ? Tools::getValue(
                'EVERPSQUOTATION_FILENAME_'.$lang['id_lang']
            ) : '';
            $everpsquotation_text[$lang['id_lang']] = (Tools::getValue(
                'EVERPSQUOTATION_TEXT_'.$lang['id_lang']
            )) ? Tools::getValue(
                'EVERPSQUOTATION_TEXT_'.$lang['id_lang']
            ) : '';
        }

        return array(
            'EVERPSQUOTATION_CATEGORIES' => Tools::getValue(
                'EVERPSQUOTATION_CATEGORIES',
                Tools::jsonDecode(
                    Configuration::get(
                        'EVERPSQUOTATION_CATEGORIES'
                    )
                )
            ),
            'EVERPSQUOTATION_GROUPS[]' => Tools::getValue(
                'EVERPSQUOTATION_GROUPS',
                Tools::jsonDecode(
                    Configuration::get(
                        'EVERPSQUOTATION_GROUPS',
                        (int)$this->context->language->id
                    )
                )
            ),
            'EVERPSQUOTATION_PRODUCT' => Tools::getValue(
                'EVERPSQUOTATION_PRODUCT',
                Configuration::get(
                    'EVERPSQUOTATION_PRODUCT',
                    (int)$this->context->language->id
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
            'EVERPSQUOTATION_MAIL_SUBJECT' => (!empty(
                $everpsquotation_subject[(int)Configuration::get('PS_LANG_DEFAULT')]
            )) ? $everpsquotation_subject : Configuration::getInt(
                'EVERPSQUOTATION_MAIL_SUBJECT'
            ),
            'EVERPSQUOTATION_FILENAME' => (!empty(
                $everpsquotation_filename[(int)Configuration::get('PS_LANG_DEFAULT')]
            )) ? $everpsquotation_filename : Configuration::getInt(
                'EVERPSQUOTATION_FILENAME'
            ),
            'EVERPSQUOTATION_TEXT' => (!empty(
                $everpsquotation_text[(int)Configuration::get('PS_LANG_DEFAULT')]
            )) ? $everpsquotation_text : Configuration::getInt(
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
        $cartproducts = $cart->getProducts();
        $customerGroups = Customer::getGroupsStatic((int)$cart->id_customer);
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

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Request for a quote'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation(
                $this->fetch('module:everpsquotation/views/templates/front/payment_infos.tpl')
            );

        return array($newOption);
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        $cart = $this->context->cart;
        $cartproducts = $cart->getProducts();
        $customerGroups = Customer::getGroupsStatic((int)$cart->id_customer);
        $selected_cat = $this->getAllowedCategories();
        $allowed_groups = $this->getAllowedGroups();
        foreach ($cartproducts as $cartproduct) {
            $product = new Product((int)$cartproduct['id_product']);
            if (!in_array((int)$product->id_category_default, $selected_cat)) {
                return;
            }
        }
        if (!array_intersect($allowed_groups, $customerGroups)
            || empty($allowed_groups)
        ) {
            return;
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookHeader($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product') {
            $this->context->controller->addJs($this->_path.'views/js/createProductQuote.js');
            $this->context->controller->addCss($this->_path.'views/css/everpsquotation.css');
            $product = new Product(
                (int)Tools::getValue('id_product'),
                (int)$this->context->shop->id,
                (int)$this->context->language->id
            );

            if (Tools::isSubmit('everpsproductquotation')) {
                $this->createEverQuoteCart((int)$this->context->cart->id);
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
                // Todo, get id_customization per product and cart
                $this->updateEverQuoteCart(
                    (int)Tools::getValue('id_product'),
                    (int)$id_attribute,
                    (int)Tools::getValue('id_customization'),
                    (int)$quantity,
                    (int)$this->context->cart->id
                );
                $this->addQuote();
            }
        }
    }

    public function hookDisplayShoppingCart()
    {
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
        $link = new Link();
        $selected_cat = $this->getAllowedCategories();
        $allowed_groups = $this->getAllowedGroups();
        $product = new Product(
            (int)Tools::getValue('id_product')
        );
        if ($product->getCustomizationFieldIds()) {
            return;
        }
        $customerGroups = Customer::getGroupsStatic((int)$this->context->customer->id);
        $address = Address::getFirstCustomerAddressId((int)$this->context->customer->id);

        if (!in_array($product->id_category_default, $selected_cat)
            || !Configuration::get('PS_REWRITING_SETTINGS')
        ) {
            return;
        }
        if ((bool)Configuration::get('EVERPSQUOTATION_PRODUCT')) {
            if (Configuration::isCatalogMode()) {
                $catalogMode = true;
            } else {
                $catalogMode = false;
            }

            $this->context->smarty->assign(array(
                'cart_url' => $link->getPageLink('cart', true),
                'my_account_url' => $link->getPageLink('my-account', true),
                'address_url' => $link->getPageLink('address', true),
                'catalogMode' => $catalogMode,
                'selected_cat' => $selected_cat,
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
        if ($this->isSeven) {
            return $this->display(__FILE__, 'views/templates/front/myaccount.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/front/myaccount16.tpl');
        }
    }

    public function createEverQuoteCart($id_cart)
    {
        $cart = new Cart((int)$id_cart);

        $cartdetails = $cart->getSummaryDetails();

        $quote = new EverpsquotationCart();

        $quote->reference = Configuration::get('EVERPSQUOTATION_PREFIX');
        $quote->id_shop_group = $cart->id_shop_group;
        $quote->id_shop = (int)$cart->id_shop;
        $quote->id_carrier = (int)$cart->id_carrier;
        $quote->id_lang = (int)$cart->id_lang;
        $quote->id_customer = (int)$cart->id_customer;
        $quote->id_cart = (int)$cart->id;
        $quote->id_currency = (int)$cart->id_currency;
        $quote->id_address_delivery = (int)$cart->id_address_delivery;
        $quote->id_address_invoice = (int)$cart->id_address_invoice;
        $quote->secure_key = $cart->secure_key;
        $quote->recyclable = $cart->recyclable;
        $quote->total_discounts = $cartdetails['total_discounts'];
        $quote->total_discounts_tax_incl = $cartdetails['total_discounts_tax_exc'];
        $quote->total_discounts_tax_excl = $cartdetails['total_discounts_tax_exc'];
        $quote->total_paid_tax_incl = $cartdetails['total_price'];
        $quote->total_paid_tax_excl = $cartdetails['total_price_without_tax'];
        $quote->total_products = $cartdetails['total_products'];
        $quote->total_products_wt = $cartdetails['total_products_wt'];
        $quote->total_shipping = $cartdetails['total_shipping'];
        $quote->total_shipping_tax_incl = $cartdetails['total_shipping_tax_exc'];
        $quote->total_shipping_tax_excl = $cartdetails['total_shipping_tax_exc'];
        $quote->total_wrapping = $cartdetails['total_wrapping'];
        $quote->total_wrapping_tax_incl = $cartdetails['total_wrapping_tax_exc'];
        $quote->total_wrapping_tax_excl = $cartdetails['total_wrapping_tax_exc'];
        $quote->date_add = $cart->date_add;
        $quote->date_upd = $cart->date_upd;
        if (!$quote->save()) {
            die('can\'t add to quotation cart');
        }
        return (int)Db::getInstance()->Insert_ID();
    }

    private function updateEverQuoteCart(
        $id_product,
        $id_product_attribute,
        $id_customization,
        $qty,
        $cart
    ) {
        $id_evercart = EverpsquotationCart::getEvercartByCustomerId(
            (int)$this->context->customer->id,
            (int)$this->context->shop->id,
            (int)$this->context->language->id
        );
        $evercartProduct = EverpsquotationCartProduct::getEverCartByIdProduct(
            (int)$id_evercart,
            (int)$id_product,
            (int)$id_product_attribute,
            (int)$id_customization
        );
        $cart = new Cart($cart);
        if ($evercartProduct) {
            $quoteProducts = new EverpsquotationCartProduct((int)$evercartProduct);
        } else {
            $quoteProducts = new EverpsquotationCartProduct();
            $quoteProducts->id_everpsquotation_cart = (int)$id_evercart;
            $quoteProducts->id_product = (int)$id_product;
            $quoteProducts->id_address_delivery = (int)$cart->id_address_delivery;
            $quoteProducts->id_shop = (int)$cart->id_shop;
            if ($id_product_attribute) {
                $quoteProducts->id_product_attribute = (int)$id_product_attribute;
            }
            $quoteProducts->id_customization = (int)$id_customization;
        }
        $quoteProducts->quantity = (int)$qty;
        $quoteProducts->save();
    }

    private function addQuote()
    {
        $id_evercart = EverpsquotationCart::getEvercartByCustomerId(
            (int)$this->context->customer->id,
            (int)$this->context->shop->id,
            (int)$this->context->language->id
        );
        $cartproducts = EverpsquotationCartProduct::getEverCartByIdEvercart((int)$id_evercart);

        $total_products = 0;
        $total_products_wt = 0;
        foreach ($cartproducts as $quoteProduct) {
            $product = new Product(
                (int)$quoteProduct['id_product'],
                (int)$this->context->language->id,
                (int)$this->context->shop->id
            );
            $productPrice = Product::getPriceStatic(
                (int)$product->id
            );
            $total_products += ($productPrice * $quoteProduct['quantity']);
            $total_products_wt += ($product->price * $quoteProduct['quantity']);
        }

        $cart = $this->context->cart;
        $cartdetails = $cart->getSummaryDetails();
        $cart = new EverpsquotationCart((int)$id_evercart);
        $quote = new EverpsquotationClass();
        $quote->reference = Configuration::get('EVERPSQUOTATION_PREFIX');

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
        $quote->total_discounts = (float)$cartdetails['total_discounts'];
        $quote->total_discounts_tax_incl = (float)$cartdetails['total_discounts_tax_exc'];
        $quote->total_discounts_tax_excl = (float)$cartdetails['total_discounts_tax_exc'];
        $quote->total_paid_tax_incl = $total_products + $cartdetails['total_shipping_tax_exc'];
        $quote->total_paid_tax_excl = (float)$total_products_wt + $cartdetails['total_shipping_tax_exc'];
        $quote->total_products = (float)$total_products;
        $quote->total_products_wt = (float)$total_products_wt;
        $quote->total_shipping = (float)$cartdetails['total_shipping'];
        $quote->total_shipping_tax_incl = (float)$cartdetails['total_shipping_tax_exc'];
        $quote->total_shipping_tax_excl = (float)$cartdetails['total_shipping_tax_exc'];
        $quote->total_wrapping = (float)$cartdetails['total_wrapping'];
        $quote->total_wrapping_tax_incl = (float)$cartdetails['total_wrapping_tax_exc'];
        $quote->total_wrapping_tax_excl = (float)$cartdetails['total_wrapping_tax_exc'];
        $quote->valid = 0;
        $quote->date_add = $cart->date_add;
        $quote->date_upd = $cart->date_upd;
        $quote->save();
        $quoteid = (int)Db::getInstance()->Insert_ID();

        //Now create new Everpsquotationdetail object
        foreach ($cartproducts as $cartproduct) {
            $product = new Product(
                (int)$cartproduct['id_product'],
                (int)$this->context->language->id,
                (int)$this->context->shop->id
            );
            $quotedetail = new EverpsquotationDetail();
            $quotedetail->id_everpsquotation_quotes = (int)$quoteid;
            $quotedetail->id_shop = (int)$cartproduct['id_shop'];
            $quotedetail->product_id = (int)$product->id;
            $quotedetail->product_attribute_id = (int)$cartproduct['id_product_attribute'];
            $quotedetail->id_customization = (int)$cartproduct['id_customization'];
            $quotedetail->product_name = (string)$product->name;
            $quotedetail->product_quantity = $cartproduct['quantity'];
            $quotedetail->product_price = (float)$product->price;
            $quotedetail->product_ean13 = (string)$product->ean13;
            $quotedetail->product_isbn = (string)$product->isbn;
            $quotedetail->product_upc = (string)$product->upc;
            $quotedetail->product_reference = (string)$product->reference;
            $quotedetail->product_supplier_reference = (string)$product->supplier_reference;
            $quotedetail->product_weight = $product->weight;
            $quotedetail->tax_name = $product->tax_name;
            $quotedetail->ecotax = $product->ecotax;
            $quotedetail->unit_price_tax_excl = (float)$product->price;
            $quotedetail->total_price_tax_incl = (float)$product->price * (int)$cartproduct['quantity'];
            $quotedetail->total_price_tax_excl = (float)$product->price * (int)$cartproduct['quantity'];
            $quotedetail->add();
        }
        EverpsquotationCart::deleteEverQuoteCart((int)$id_evercart);

        //Preparing emails
        require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';
        if (Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL')) {
            $everShopEmail = Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL');
        } else {
            $everShopEmail = Configuration::get('PS_SHOP_EMAIL');
        }
        // Subject
        $ever_subject = Configuration::getInt('EVERPSQUOTATION_MAIL_SUBJECT');
        $subject = $ever_subject[(int)Context::getContext()->language->id];
        // Filename
        $filename = Configuration::getInt('EVERPSQUOTATION_FILENAME');
        $ever_filename = $filename[(int)Context::getContext()->language->id];

        $id_shop = (int)Context::getContext()->shop->id;
        $mailDir = _PS_MODULE_DIR_.'everpsquotation/mails/';
        $mailpdf = new PDF($quoteid, 'EverQuotationPdf', Context::getContext()->smarty);
        $customer = new Customer(
            (int)Context::getContext()->customer->id
        );
        $customerNames = $customer->firstname.' '.$customer->lastname;
        $attachment = array();
        $attachment['content'] = $mailpdf->render(false);
        $attachment['name'] = $ever_filename;
        $attachment['mime'] = 'application/pdf';

        //Send customer email
        $sent = Mail::send(
            (int)Context::getContext()->language->id,
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
        $pdf = new PDF($quoteid, 'EverQuotationPdf', Context::getContext()->smarty);
        $pdf->render();
    }

    private function getAllowedCategories()
    {
        $selected_cat = Tools::jsonDecode(
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
        $groupShop = Shop::getGroupFromShop((int)$this->context->shop->id);
        $allowed_groups = Tools::jsonDecode(
            Configuration::get(
                'EVERPSQUOTATION_GROUPS'
            )
        );
        if (!is_array($allowed_groups)) {
            $allowed_groups = array($allowed_groups);
        }
        return $allowed_groups;
    }
}
