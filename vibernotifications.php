<?php
/**
* 2007-2016 PrestaShop
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
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/*
 * Author: pligin
 * Site: psweb.ru
 * Telegram: t.me/pligin
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/vibernotificationsdata.php');

class ViberNotifications extends Module
{
    public function __construct()
    {
        $this->name = 'vibernotifications';
        $this->tab = 'emailing';
        $this->bootstrap = true;
        $this->version = '1.0.0';
        $this->author = '<a href="https://psweb.ru" target="_blank">PSWeb</a>';
        $this->need_instance = 0;
        $this->module_key = '3250c1a0c23b2f6e7531630fbd2c4210';
        $this->author_address = '0x6C727449f395E8457b002c3dDFD2941816A6f6Cb';
        parent::__construct();
        if ($this->id) {
            $this->refreshSettings();
        }
        $this->displayName = $this->l('Viber Notifications');
        $this->description = $this->l('The module helps to receive notifications about different events: new orders, registrations, returns, out of stock items.');
    }
    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('actionOrderReturn') ||
            !$this->registerHook('actionUpdateQuantity') ||
            !$this->registerHook('actionCustomerAccountAdd')) {
            return false;
        }
        if (!function_exists('curl_init')) {
            return false;
        }
        Configuration::updateValue('VIBER_TOKEN', '');
        Configuration::updateValue('VIBER_BOT_NAME', '');
        Configuration::updateValue('VIBER_RECEIVER_IDs', '');
        Configuration::updateValue('VIBER_LAST_TS', '');
        Configuration::updateValue('TELNOT_ADVANCED_SETTINGS', false);
        Configuration::updateValue('TELNOT_NTF_NEW_ORDER', true);
        Configuration::updateValue('TELNOT_NTF_NEW_ORDER_URL', true);
        Configuration::updateValue('TELNOT_NTF_NEW_CUSTOMER', true);
        Configuration::updateValue('TELNOT_NTF_RETURN', true);
        Configuration::updateValue('TELNOT_NTF_RETURN_URL', true);
        Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK', true);
        Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK_NUM', 5);
        $this->installTemplates();
        return true;
    }
    public function uninstall()
    {
        Configuration::deleteByName('VIBER_TOKEN');
        Configuration::deleteByName('VIBER_BOT_NAME');
        Configuration::deleteByName('VIBER_RECEIVER_IDs');
        Configuration::deleteByName('VIBER_LAST_TS');
        Configuration::deleteByName('TELNOT_ADVANCED_SETTINGS');
        Configuration::deleteByName('TELNOT_NTF_NEW_ORDER');
        Configuration::deleteByName('TELNOT_NTF_NEW_ORDER_URL');
        Configuration::deleteByName('TELNOT_NTF_NEW_ORDER_TPL');
        Configuration::deleteByName('TELNOT_NTF_NEW_CUSTOMER');
        Configuration::deleteByName('TELNOT_NTF_NEW_CUSTOMER_TPL');
        Configuration::deleteByName('TELNOT_NTF_RETURN');
        Configuration::deleteByName('TELNOT_NTF_RETURN_URL');
        Configuration::deleteByName('TELNOT_NTF_RETURN_TPL');
        Configuration::deleteByName('TELNOT_NTF_OUT_OF_STOCK');
        Configuration::deleteByName('TELNOT_NTF_OUT_OF_STOCK_NUM');
        Configuration::deleteByName('TELNOT_NTF_OUT_OF_STOCK_TPL');
        return parent::uninstall();
    }
    public function getContent()
    {
        $this->html = '';
        $this->postProcess();
        $this->html .= $this->renderFormViber();
        $this->html .= $this->renderFormNotifications();
        return $this->html;
    }

    private function postProcess()
    {
        $post_errors = array();
        if (Tools::isSubmit('btnSubmit')) {
            $token = Tools::getValue('VIBER_TOKEN');
            $bot_name = Tools::getValue('VIBER_BOT_NAME');
            if (!empty($token)) {
                Configuration::updateValue('VIBER_TOKEN', Tools::getValue('VIBER_TOKEN'));
                //var_dump($viber);
                if (!empty($bot_name)) {
                    Configuration::updateValue('VIBER_BOT_NAME', Tools::getValue('VIBER_BOT_NAME'));
                    $viber = new ViberNotificationsData($token,$bot_name);
                    $response = $viber->setWebHook($this->context->link->getModuleLink($this->name,'callback'));
                    if($response['status'] != 0){
                        $post_errors[] = $this->l($response['status_message']);
                    }
                } else {
                    $post_errors[] = $this->l('Field "Viber Bot Name" cannot be empty!');
                }
            } else {
                $post_errors[] = $this->l('Field "Viber Auth token" cannot be empty!');
            }
            if (count($post_errors)) {
                foreach ($post_errors as $err) {
                    $this->html .= $this->displayError($err);
                }
            } else {
                $this->html .= $this->displayConfirmation($this->l('Settings updated successfully'));
            }
        }
        if (Tools::isSubmit('btnSubmittpls')) {
            Configuration::updateValue('TELNOT_ADVANCED_SETTINGS', (int)Tools::getValue('TELNOT_ADVANCED_SETTINGS'));

            Configuration::updateValue('TELNOT_NTF_NEW_ORDER', (int)Tools::getValue('TELNOT_NTF_NEW_ORDER'));
            Configuration::updateValue('TELNOT_NTF_NEW_ORDER_URL', (int)Tools::getValue('TELNOT_NTF_NEW_ORDER_URL'));
            Configuration::updateValue('TELNOT_NTF_NEW_ORDER_TPL', (string)Tools::getValue('TELNOT_NTF_NEW_ORDER_TPL'));

            Configuration::updateValue('TELNOT_NTF_NEW_CUSTOMER', (int)Tools::getValue('TELNOT_NTF_NEW_CUSTOMER'));
            Configuration::updateValue('TELNOT_NTF_NEW_CUSTOMER_TPL', (string)Tools::getValue('TELNOT_NTF_NEW_CUSTOMER_TPL'));

            Configuration::updateValue('TELNOT_NTF_RETURN', (int)Tools::getValue('TELNOT_NTF_RETURN'));
            Configuration::updateValue('TELNOT_NTF_RETURN_URL', (int)Tools::getValue('TELNOT_NTF_RETURN_URL'));
            Configuration::updateValue('TELNOT_NTF_RETURN_TPL', (string)Tools::getValue('TELNOT_NTF_RETURN_TPL'));

            Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK', (int)Tools::getValue('TELNOT_NTF_OUT_OF_STOCK'));
            Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK_NUM', (int)Tools::getValue('TELNOT_NTF_OUT_OF_STOCK_NUM'));
            Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK_TPL', (string)Tools::getValue('TELNOT_NTF_OUT_OF_STOCK_TPL'));

            $this->html .= $this->displayConfirmation($this->l('Notifications settings updated successfully'));
        }
        $this->refreshSettings();
    }
    protected function refreshSettings()
    {
        $this->token = Configuration::get('VIBER_TOKEN');
        $this->bot_name = Configuration::get('VIBER_BOT_NAME');
        $this->receiver_ids = Configuration::get('VIBER_RECEIVER_IDs');

        $this->advanced = Configuration::get('TELNOT_ADVANCED_SETTINGS');
        $this->ntf_new_order = Configuration::get('TELNOT_NTF_NEW_ORDER');
        $this->ntf_new_order_url = Configuration::get('TELNOT_NTF_NEW_ORDER_URL');
        $this->ntf_new_order_tpl  = Configuration::get('TELNOT_NTF_NEW_ORDER_TPL');

        $this->ntf_new_customer = Configuration::get('TELNOT_NTF_NEW_CUSTOMER');
        $this->ntf_new_customer_tpl = Configuration::get('TELNOT_NTF_NEW_CUSTOMER_TPL');

        $this->ntf_return = Configuration::get('TELNOT_NTF_RETURN');
        $this->ntf_return_url = Configuration::get('TELNOT_NTF_RETURN_URL');
        $this->ntf_return_tpl = Configuration::get('TELNOT_NTF_RETURN_TPL');

        $this->ntf_out_of_stock = Configuration::get('TELNOT_NTF_OUT_OF_STOCK');
        $this->ntf_out_of_stock_num = Configuration::get('TELNOT_NTF_OUT_OF_STOCK_NUM');
        $this->ntf_out_of_stock_tpl = Configuration::get('TELNOT_NTF_OUT_OF_STOCK_TPL');
    }
    private function installTemplates()
    {
        Configuration::updateValue('TELNOT_NTF_NEW_ORDER_TPL', $this->l("A new order *№{order_name}* was placed in your store.\nCustomer: *{firstname} {lastname} ({invoice_phone})*\nPayment: *{payment}*\nOrder status: *{order_status}*\n\nProducts: {products}\n\nTotal paid: *{total_paid}*\nCustomer message: *{message}*"));
        Configuration::updateValue('TELNOT_NTF_NEW_CUSTOMER_TPL', $this->l('*{firstname} {lastname}* ({email}) has registered in your store.'));
        Configuration::updateValue('TELNOT_NTF_RETURN_TPL', $this->l("You have received a new return request.\nOrder: {order_name} Placed on {date}\nCustomer: *{firstname} {lastname}* ({email})\n\nProducts: {products}\n\nCustomer message: *{message}*"));
        Configuration::updateValue('TELNOT_NTF_OUT_OF_STOCK_TPL', $this->l("*{product}* is nearly out of stock.\nRemaining stock: {quantity}\n{product_url}"));
    }
    public function renderFormViber()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $this->token ? '' : $this->l('You need to create bot using https://partners.viber.com/ for receiving token. More information can be found in the detailed instructions.'),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Viber bot token'),
                        'name' => 'VIBER_TOKEN',
                        'col' => 4,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Bot Name'),
                        'name' => 'VIBER_BOT_NAME',
                        'class' => 'fixed-width-md',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            ),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsViber(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }
    public function getConfigFieldsViber()
    {
        $config_fields = array(
            'VIBER_TOKEN' => $this->token,
            'VIBER_BOT_NAME' => $this->bot_name,
        );
        return $config_fields;
    }
    public function renderFormNotifications()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Notifications settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $this->l(''),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'TELNOT_ADVANCED_SETTINGS',
                        'label' => $this->l('Show advanced settings'),
                        'is_bool' => true,
                        'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ),
                            ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('New order'),
                        'desc' => $this->l('Notify about new orders'),
                        'name' => 'TELNOT_NTF_NEW_ORDER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                        'type' => $this->advanced ? 'switch' : 'hidden',
                        'label' => $this->l('Products links'),
                        'desc' => $this->l('Enable this option, if you want to include product link to variable {products} in notification template about new orders'),
                        'name' => 'TELNOT_NTF_NEW_ORDER_URL',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                         'type'  => $this->advanced ? 'textarea' : 'hidden',
                        'label' => $this->l('Notification template of new order'),
                        'name'  => 'TELNOT_NTF_NEW_ORDER_TPL',
                        'desc'  => $this->l('This template supports variables. Look at list of variables in instructions'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('New customer'),
                        'desc' => $this->l('Notify about new customer registration'),
                        'name' => 'TELNOT_NTF_NEW_CUSTOMER',
                        'is_bool' => true,
                        'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ),
                            ),
                    ),
                    array(
                         'type'  => $this->advanced ? 'textarea' : 'hidden',
                        'label' => 'Notification template of new customer registration',
                        'name'  => 'TELNOT_NTF_NEW_CUSTOMER_TPL',
                        'desc'  => $this->l('This template supports variables. Look at list of variables in instructions'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Returns'),
                        'desc' => $this->l('Notify about new product return request'),
                        'name' => 'TELNOT_NTF_RETURN',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                        'type' =>  $this->advanced ? 'switch' : 'hidden',
                        'label' => $this->l('Products links'),
                        'desc' => $this->l('Enable this option, if you want to include product link to variable {products} in notification template about new product return request'),
                        'name' => 'TELNOT_NTF_RETURN_URL',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                         'type'  => $this->advanced ? 'textarea' : 'hidden',
                        'label' => $this->l('Notification template of new product return request'),
                        'name'  => 'TELNOT_NTF_RETURN_TPL',
                        'desc'  => $this->l('This template supports variables. Look at list of variables in instructions'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Out of stock'),
                        'desc' => $this->l('Notify when a product is (almost) out of stock'),
                        'name' => 'TELNOT_NTF_OUT_OF_STOCK',
                        'is_bool' => true,
                        'values' => array(
                                array(
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ),
                                array(
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ),
                            ),
                    ),
                    array(
                        'type' => $this->advanced ? 'text' : 'hidden',
                        'label' => $this->l('Threshold'),
                        'desc' => $this->l('Quantity for which a product is regarded as almost out of stock'),
                        'name' => 'TELNOT_NTF_OUT_OF_STOCK_NUM',
                        'class' => 'fixed-width-md',
                    ),
                    array(
                         'type'  => $this->advanced ? 'textarea' : 'hidden',
                        'label' => 'Notification template of a product is (almost) out of stock',
                        'name'  => 'TELNOT_NTF_OUT_OF_STOCK_TPL',
                        'desc'  => $this->l('This template supports variables. Look at list of variables in instructions'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            ),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmittpls';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsNotifications(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }
    public function getConfigFieldsNotifications()
    {
        $config_fields = array(
            'TELNOT_ADVANCED_SETTINGS' => $this->advanced,
            'TELNOT_NTF_NEW_ORDER' => $this->ntf_new_order,
            'TELNOT_NTF_NEW_ORDER_URL' => $this->ntf_new_order_url,
            'TELNOT_NTF_NEW_ORDER_TPL' => $this->ntf_new_order_tpl,
            'TELNOT_NTF_NEW_CUSTOMER' => $this->ntf_new_customer,
            'TELNOT_NTF_NEW_CUSTOMER_TPL' => $this->ntf_new_customer_tpl,
            'TELNOT_NTF_RETURN' => $this->ntf_return,
            'TELNOT_NTF_RETURN_URL'=> $this->ntf_return_url,
            'TELNOT_NTF_RETURN_TPL' => $this->ntf_return_tpl,
            'TELNOT_NTF_OUT_OF_STOCK' => $this->ntf_out_of_stock,
            'TELNOT_NTF_OUT_OF_STOCK_NUM' => $this->ntf_out_of_stock_num,
            'TELNOT_NTF_OUT_OF_STOCK_TPL' => $this->ntf_out_of_stock_tpl,
        );
        return $config_fields;
    }

    public function hookActionValidateOrder($params)
    {
        if ((bool)$this->ntf_new_order && !empty($this->token) && !empty($this->bot_name) && !empty($this->ntf_new_order_tpl)) {
            $items = '';
            $vouchers = '';
            $context = Context::getContext();
            $id_lang = (int)$context->language->id;
            $currency = $params['currency'];
            $order = $params['order'];
            $customer = $params['customer'];
            $invoice = new Address((int)$order->id_address_invoice);
            $delivery = new Address((int)$order->id_address_delivery);
            $products = $params['order']->getProducts();
            $message = $order->getFirstMessage();
            $status = $params['orderStatus'];
            if ($delivery->id_state) {
                $delivery_state = new State((int)$delivery->id_state);
            }
            if ($invoice->id_state) {
                $invoice_state = new State((int)$invoice->id_state);
            }
            if (!$message || empty($message)) {
                $message = $this->l('No message');
            }
            foreach ($products as $product) {
                $items .= sprintf(
                    "\n - (x%d) %s %s",
                    $product['product_quantity'],
                    $product['product_name'],
                    ($this->ntf_new_order_url ? "\n".$context->link->getProductLink((int)$product['id_product']) : '')
                );
            }
            foreach ($params['order']->getDiscounts() as $discount) {
                $vouchers .= $discount['name'].' - '.Tools::displayPrice($discount['value'], $currency, false)."\n";
            }
            $find = array(
                '{order_name}',
                '{order_status}',
                '{shop_name}',
                '{shop_url}',
                '{firstname}',
                '{lastname}',
                '{email}',
                '{products}',
                '{message}',
                '{delivery_company}',
                '{delivery_firstname}',
                '{delivery_lastname}',
                '{delivery_address1}',
                '{delivery_address2}',
                '{delivery_city}',
                '{delivery_post_code}',
                '{delivery_country}',
                '{delivery_state}',
                '{delivery_phone}',
                '{delivery_other}',
                '{invoice_company}',
                '{invoice_firstname}',
                '{invoice_lastname}',
                '{invoice_address1}',
                '{invoice_address2}',
                '{invoice_city}',
                '{invoice_post_code}',
                '{invoice_country}',
                '{invoice_state}',
                '{invoice_phone}',
                '{invoice_other}',
                '{total_paid}',
                '{total_products}',
                '{total_discounts}',
                '{total_shipping}',
                '{total_wrapping}',
                '{vouchers}',
                '{date}',
                '{currency}',
                '{payment}',
                '{carrier}'
                );
            $replace = array(
                '{order_name}' => $order->id,
                '{order_status}' => $status->name,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_url}' => Tools::getShopDomain(true, true).__PS_BASE_URI__,
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}'  => $customer->email,
                '{products}' => $items,
                '{message}' => $message,
                '{delivery_company}' => $delivery->company,
                '{delivery_firstname}' => $delivery->firstname,
                '{delivery_lastname}' => $delivery->lastname,
                '{delivery_address1}' => $delivery->address1,
                '{delivery_address2}' => $delivery->address2,
                '{delivery_city}' => $delivery->city,
                '{delivery_post_code}' => $delivery->postcode,
                '{delivery_country}' => $delivery->country,
                '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
                '{delivery_phone}' => $delivery->phone,
                '{delivery_other}' => $delivery->other,
                '{invoice_company}' => $invoice->company,
                '{invoice_firstname}' => $invoice->firstname,
                '{invoice_lastname}' => $invoice->lastname,
                '{invoice_address1}' => $invoice->address1,
                '{invoice_address2}' => $invoice->address2,
                '{invoice_city}' => $invoice->city,
                '{invoice_post_code}' => $invoice->postcode,
                '{invoice_country}' => $invoice->country,
                '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
                '{invoice_phone}' => $invoice->phone,
                '{invoice_other}' => $invoice->other,
                '{total_paid}' => Tools::displayPrice($order->total_paid, $currency),
                '{total_products}' => Tools::displayPrice($order->getTotalProductsWithTaxes(), $currency),
                '{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency),
                '{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency),
                '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency),
                '{vouchers}' => $vouchers,
                '{date}' => Tools::displayDate($order->date_add, (int)$id_lang),
                '{currency}' => $currency->sign,
                '{payment}' => Tools::substr($order->payment, 0, 32),
                '{carrier}' => (((new Carrier((int)$order->id_carrier))->name == '0') ? Configuration::get('PS_SHOP_NAME') : (new Carrier((int)$order->id_carrier))->name),
            );
            $telmsg = str_replace($find, $replace, $this->ntf_new_order_tpl);
            $this->sendViber($telmsg);
        } else {
            return;
        }
    }
    public function hookActionCustomerAccountAdd($params)
    {
        if ((bool)$this->ntf_new_customer && !empty($this->token) && !empty($this->bot_name) && !empty($this->ntf_new_customer)) {
            $customer = $params['newCustomer'];

            $find = array(
                '{shop_url}',
                '{shop_name}',
                '{id}',
                '{firstname}',
                '{lastname}',
                '{email}'
            );
            $replace = array(

                '{shop_url}' => Tools::getShopDomain(true, true).__PS_BASE_URI__,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{id}' => $customer->id,
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
            );

            $telmsg = str_replace($find, $replace, $this->ntf_new_customer_tpl);
            $this->sendViber($telmsg);
        } else {
            return;
        }
    }
    public function hookActionUpdateQuantity($params)
    {
        if ((bool)$this->ntf_out_of_stock && !empty($this->token) && !empty($this->bot_name) && !empty($this->ntf_out_of_stock_tpl) && !defined('PS_MASS_PRODUCT_CREATION')) {
            $id_product = (int)$params['id_product'];
            $quantity = (int)$params['quantity'];
            $context = Context::getContext();
            $id_shop = (int)$context->shop->id;
            $id_lang = (int)$context->language->id;
            $product = new Product($id_product, false, $id_lang, $id_shop, $context);
            $has_attr = $product->hasAttributes();
            $id_attr = (int)$params['id_product_attribute'];
            $check_attr = ($has_attr && $id_attr) || (!$has_attr && !$id_attr);

            if ($product->active == 1 && $check_attr && $quantity <= (int)$this->ntf_out_of_stock_num) {
                $find = array(
                    '{shop_url}',
                    '{shop_name}',
                    '{quantity}',
                    '{product}',
                    '{product_url}'
                );

                $replace = array(

                    '{shop_url}' => Tools::getShopDomain(true, true).__PS_BASE_URI__,
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                    '{quantity}' => $quantity,
                    '{product}' => Product::getProductName($id_product, $id_attr, $id_lang),
                    '{product_url}' => $context->link->getProductLink((int)$id_product),
                );

                $telmsg = str_replace($find, $replace, $this->ntf_out_of_stock_tpl);
                $this->sendViber($telmsg);
            }
        } else {
            return;
        }
    }
    public function hookActionOrderReturn($params)
    {
        if ((bool)$this->ntf_return && !empty($this->token) && !empty($this->bot_name) && !empty($this->ntf_return_tpl)) {
            $items = '';
            $context = Context::getContext();
            $order = new Order((int)$params['orderReturn']->id_order);
            $customer = new Customer((int)$params['orderReturn']->id_customer);
            $delivery = new Address((int)$order->id_address_delivery);
            $invoice = new Address((int)$order->id_address_invoice);

            $order_date_text = Tools::displayDate($order->date_add);

            if ($delivery->id_state) {
                $delivery_state = new State((int)$delivery->id_state);
            }
            if ($invoice->id_state) {
                $invoice_state = new State((int)$invoice->id_state);
            }
            $products = OrderReturn::getOrdersReturnProducts($params['orderReturn']->id, $order);
            foreach ($products as $product) {
                $items .= sprintf(
                    "\n - (x%d) %s %s",
                    $product['product_quantity'],
                    $product['product_name'],
                    ($this->ntf_return_url ? "\n".$context->link->getProductLink((int)$product['id_product']) : '')
                );
            }
            $find = array(
                '{order_name}',
                '{shop_name}',
                '{shop_url}',
                '{firstname}',
                '{lastname}',
                '{email}',
                '{products}',
                '{message}',
                '{delivery_company}',
                '{delivery_firstname}',
                '{delivery_lastname}',
                '{delivery_address1}',
                '{delivery_address2}',
                '{delivery_city}',
                '{delivery_post_code}',
                '{delivery_country}',
                '{delivery_state}',
                '{delivery_phone}',
                '{delivery_other}',
                '{invoice_company}',
                '{invoice_firstname}',
                '{invoice_lastname}',
                '{invoice_address1}',
                '{invoice_address2}',
                '{invoice_city}',
                '{invoice_post_code}',
                '{invoice_country}',
                '{invoice_state}',
                '{invoice_phone}',
                '{invoice_other}',
                '{date}',
            );
            $replace = array(

                '{order_name}' => $order->id,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_url}' => Tools::getShopDomain(true, true).__PS_BASE_URI__,
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{products}' => $items,
                '{message}' => Tools::purifyHTML($params['orderReturn']->question),

                '{delivery_company}' => $delivery->company,
                '{delivery_firstname}' => $delivery->firstname,
                '{delivery_lastname}' => $delivery->lastname,
                '{delivery_address1}' => $delivery->address1,
                '{delivery_address2}' => $delivery->address2,
                '{delivery_city}' => $delivery->city,
                '{delivery_post_code}'=> $delivery->postcode,
                '{delivery_country}' => $delivery->country,
                '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
                '{delivery_phone}' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
                '{delivery_other}' => $delivery->other,

                '{invoice_company}' => $invoice->company,
                '{invoice_firstname}' => $invoice->firstname,
                '{invoice_lastname}' => $invoice->lastname,
                '{invoice_address1}' => $invoice->address1,
                '{invoice_address2}' => $invoice->address2,
                '{invoice_city}' => $invoice->city,
                '{invoice_post_code}' => $invoice->postcode,
                '{invoice_country}' => $invoice->country,
                '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
                '{invoice_phone}' => $invoice->phone ? $invoice->phone : $invoice->phone_mobile,
                '{invoice_other}' => $invoice->other,
                '{date}' => $order_date_text,
            );
            $telmsg = str_replace($find, $replace, $this->ntf_return_tpl);
            $this->sendViber($telmsg);
        } else {
            return;
        }
    }
    
    public function sendViber($msg)
    {
        $ai = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
        foreach($ai as $receiver_id => $name){
            $viber = new ViberNotificationsData($this->token,$this->bot_name);
            return $viber->sendMessage($receiver_id, $msg, $tracking_data = Null);
        }
    }
}
