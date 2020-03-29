<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    NaturePinks <naturepinks@gmail.com>
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2020 NaturePinks
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class BlockInStockProducts
 */
class BlockInStockProducts extends Module
{
    const CACHE_TTL = 'BLOCK_INSTOCK_PRODUCTS_TTL';
    const CACHE_TIMESTAMP = 'BLOCK_INSTOCK_PRODUCTS_TIMESTAMP';

    const NUMBER = 'BLOCK_INSTOCK_PRODUCTS_NUM_TO_DISPLAY';
    //const NUMBER_OF_DAYS = 'PS_NB_DAYS_NEW_PRODUCT';
    const ALWAYS_DISPLAY = 'BLOCK_INSTOCK_PRODUCTS_ALWAYS_DISPLAY';

    // @codingStandardsIgnoreStart
    /** @var array $cache_instock_products */
    protected static $cache_instock_products;
    // @codingStandardsIgnoreEnd

    /**
     * BlockInStockProducts constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'blockinstockproducts';
        $this->tab = 'front_office_features';
        $this->version = '2.2.2';
        $this->author = 'NaturePinks';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block In Stock Products');
        $this->description = $this->l('Displays a block displaying your store\'s in stock products.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';

        if (Configuration::get(static::CACHE_TIMESTAMP) < (time() - Configuration::get(static::CACHE_TTL))) {
            $this->clearCache();
        }
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('header');
        $this->registerHook('leftColumn');
        $this->registerHook('addproduct');
        $this->registerHook('updateproduct');
        $this->registerHook('deleteproduct');
        $this->registerHook('displayHomeTab');
        $this->registerHook('displayHomeTabContent');

        //defaults        
        Configuration::updateValue('BLOCK_INSTOCK_PRODUCTS_NUM_TO_DISPLAY', 12);
        Configuration::updateValue('BLOCK_INSTOCK_PRODUCTS_TTL', 300);
        Configuration::updateValue('BLOCK_INSTOCK_PRODUCTS_ALWAYS_DISPLAY', 0);
        
        $this->clearCache();

        return true;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $this->clearCache();

        Configuration::deleteByName('BLOCK_INSTOCK_PRODUCTS_NUM_TO_DISPLAY');
        //Configuration::deleteByName(static::NUMBER_OF_DAYS);        
        Configuration::deleteByName(static::ALWAYS_DISPLAY);
        Configuration::deleteByName(static::NUMBER);
        Configuration::deleteByName(static::CACHE_TTL);
        Configuration::deleteByName(static::CACHE_TIMESTAMP);
        Configuration::deleteByName('BLOCK_INSTOCK_PRODUCTS_TIMESTAMP');

        return parent::uninstall();
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        //All except number of days field
        $output = '';
        if (Tools::isSubmit('submitInStockProducts')) {
            if (!($productNbr = Tools::getValue(static::NUMBER)) || empty($productNbr)) {
                $output .= $this->displayError($this->l('Please enter a valid number of "products to display".'));
            } elseif ((int) ($productNbr) == 0) {
                $output .= $this->displayError($this->l('Invalid number.'));
            } else {
                Configuration::updateValue(
                    static::ALWAYS_DISPLAY,
                    (int) (Tools::getValue(static::ALWAYS_DISPLAY))
                );
                Configuration::updateValue(static::NUMBER, (int) ($productNbr));
                Configuration::updateValue(
                    static::CACHE_TTL,
                    (int) (Tools::getValue(static::CACHE_TTL) * 60)
                );
                $this->clearCache();
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->renderForm();    }

    /**
     * getInStockProducts() is a replacement for local getNewProducts() 
     * getProductsInStock() is based on Product::getNewProducts()
     * @return array
     * @throws PrestaShopException
     */
    protected function getInStockProducts()
    {
        if (!Configuration::get(static::NUMBER)) {
            return [];
        }

        $inStockProducts = false;
        //if (Configuration::get(static::NUMBER_OF_DAYS)) {
            $inStockProducts = Self::getProductsInStock(
                (int) $this->context->language->id, 0,
                (int) Configuration::get(static::NUMBER)
            );
        //}

        if (!$inStockProducts && Configuration::get(static::ALWAYS_DISPLAY)) {
            return [];
        }

        return $inStockProducts;
    }

    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookRightColumn()
    {
        if (!$this->isCached('blockinstockproducts.tpl', $this->getCacheId())) {
            if (!isset(BlockInStockProducts::$cache_instock_products)) {
                BlockInStockProducts::$cache_instock_products = $this->getInStockProducts();
            }
            $this->smarty->assign([
                    'instock_products' => BlockInStockProducts::$cache_instock_products,
                // Retrocompatibility with < 1.1.1.
                'mediumSize'   => Image::getSize(ImageType::getFormatedName('medium')),
                ]);
        }

        if (!BlockInStockProducts::$cache_instock_products) {
            return false;
        }

        return $this->display(__FILE__, 'blockinstockproducts.tpl', $this->getCacheId());
    }

    /**
     * @param string|null $name
     *
     * @return string
     */
    protected function getCacheId($name = null)
    {
        if ($name === null) {
            $name = 'blockinstockproducts';
        }

        return parent::getCacheId($name.'|'.date('Ymd'));
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHomeTab()
    {
        if (!$this->isCached('tab.tpl', $this->getCacheId('blockinstockproducts-tab'))) {
            BlockInStockProducts::$cache_instock_products = $this->getInStockProducts();
        }

        if (BlockInStockProducts::$cache_instock_products === false) {
            return false;
        }

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blockinstockproducts-tab'));
    }
    /**
     * @return bool|string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayHomeTabContent()
    {
        if (!$this->isCached(
            'blockinstockproducts_home.tpl',
            $this->getCacheId('blockinstockproducts-home'))
        ) {
            $this->smarty->assign([
                'instock_products' => BlockInStockProducts::$cache_instock_products,
                // Retrocompatibility with < 1.1.1.
                'mediumSize'   => Image::getSize(ImageType::getFormatedName('medium')),
            ]);
        }

        if (BlockInStockProducts::$cache_instock_products === false) {
            return false;
        }

        return $this->display(
            __FILE__,
            'blockinstockproducts_home.tpl',
            $this->getCacheId('blocknewproducts-home')
        );
    }

    public function hookHeader($params)
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index') {
            $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
        }
        
        $this->context->controller->addCSS($this->_path.'blockinstockproducts.css', 'all');
    }
    
    public function hookAddProduct()
    {
        $this->clearCache();
    }

    public function hookUpdateProduct()
    {
        $this->clearCache();
    }

    public function hookDeleteProduct()
    {
        $this->clearCache();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function clearCache()
    {
            $caches = [
                'blockinstockproducts.tpl'      => null,
                'blockinstockproducts_home.tpl' => 'blockinstockproducts-home',
                'tab.tpl'                   => 'blockinstockproducts-tab',
            ];

            foreach ($caches as $template => $cacheId) {
                Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath($template), $cacheId);
            }

            Configuration::updateValue(static::CACHE_TIMESTAMP, time());
        } 
    
    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Products to display'),
                        'name'  => static::NUMBER,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Number of products to display'),
                    ],
                    /* Not needed for this module
                    [
                        'type'  => 'text',
                        'label' => $this->l('Number of days for which the product is considered \'new\''),
                        'name'  => static::NUMBER_OF_DAYS,
                        'class' => 'fixed-width-xs',
                    ],
                    */
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Always display this block'),
                        'name'    => static::ALWAYS_DISPLAY,
                        'desc'    => $this->l('Show the block even if no in stock products are available'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type'   => 'text',
                        'label'  => $this->l('Cache lifetime'),
                        'name'   => static::CACHE_TTL,
                        'desc'   => $this->l('Determines how long this block stays cached'),
                        'suffix' => $this->l('Minutes'),
                        'class'  => 'fixed-width-xs',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitInStockProducts';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }    
    
    /**
     * @return array
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            /* Not needed for this module
            static::NUMBER_OF_DAYS => Tools::getValue(
                static::NUMBER_OF_DAYS,
                Configuration::get(static::NUMBER_OF_DAYS)
            ),     
            */
            static::ALWAYS_DISPLAY => Tools::getValue(
                static::ALWAYS_DISPLAY,
                Configuration::get(static::ALWAYS_DISPLAY)
            ),
            static::NUMBER         => Tools::getValue(
                static::NUMBER,
                Configuration::get(static::NUMBER)
            ),
            static::CACHE_TTL      => Tools::getValue(
                    static::CACHE_TTL,
                    Configuration::get(static::CACHE_TTL) / 60
                ),
        ];
    }

    /** -----------
     * NP Local Function: getProductsInStock - based on Product::getNewProducts core function
     * Date difference check is replaced with stock quantity check
     * OrderBy, OrderWay, nbProducts are changed from original 
     *
     * @param int     $idLang     Language id
     * @param int     $pageNumber Start from (optional)
     * @param int     $nbProducts Number of products to return (optional)
     * @param bool    $count
     * @param null    $orderBy
     * @param null    $orderWay
     * @param Context $context
     *
     * @return array In Stock products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function getProductsInStock($idLang, $pageNumber = 0, $nbProducts = 12, $count = false, $orderBy = 'name', $orderWay = 'ASC', Context $context = null)
    {       
        if (!$context) {
            $context = Context::getContext();
        }
        
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        if ($nbProducts < 1) {
            $nbProducts = 12;
        }
        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'date_add';
        }
        if (empty($orderWay)) {
            $orderWay = 'DESC';
        }
        if ($orderBy == 'id_product' || $orderBy == 'price' || $orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderByPrefix = 'product_shop';
        } elseif ($orderBy == 'name') {
            $orderByPrefix = 'pl';
        }
        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)';
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderByPrefix = $orderBy[0];
            $orderBy = $orderBy[1];
        }

        if ($count) {
            $sql = 'SELECT COUNT(p.`id_product`) AS nb
					FROM `'._DB_PREFIX_.'product` p
					'.Shop::addSqlAssociation('product', 'p').'
					WHERE product_shop.`active` = 1
					AND product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"
					'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
					'.$sqlGroups;

            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        $sql = new DbQuery();
        $sql->select(
            'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
			pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
			product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new'
        );
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id);
        $sql->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang);
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $sql->where('product_shop.`active` = 1');
        if ($front) {
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        }
        // NP: Date condition for new products is replaced with stock condition
        $sql->where('stock.`quantity` > 0');
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql->where(
                'EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)'
            );
        }

        $sql->orderBy((isset($orderByPrefix) ? pSQL($orderByPrefix).'.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay));
        $sql->limit($nbProducts, $pageNumber * $nbProducts);

        if (Combination::isFeatureActive()) {
            $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id);
        }
        //static -> Product
        $sql->join(Product::sqlStock('p', 0));
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        $productsIds = [];
        foreach ($result as $row) {
            $productsIds[] = $row['id_product'];
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheFrontFeatures($productsIds, $idLang);
        
        //static -> Product
        return Product::getProductsProperties((int) $idLang, $result);
    }
    
}
