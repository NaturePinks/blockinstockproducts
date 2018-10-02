<?php
/**
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 NaturePinks
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BlockInStockProducts
 *
 * @since 1.0.0
 */
class BlockInStockProducts extends Module
{
    const CACHE_TTL = 'PS_BLOCK_INSTOCKPRODUCTS_TTL';
    const CACHE_TIMESTAMP = 'PS_BLOCK_INSTOCKPRODUCTS_TIMESTAMP';
    const INSTOCKPRODUCTS_DISPLAY = 'PS_BLOCK_INSTOCKPRODUCTS_DISPLAY';
    const INSTOCKPRODUCTS_TO_DISPLAY = 'PS_BLOCK_INSTOCKPRODUCTS_TO_DISPLAY';
	const INSTOCKPRODUCTS_RANDOM = 'PS_BLOCK_INSTOCKPRODUCTS_RANDOM';

    protected static $cacheInStockProducts;

    /**
     * BlockInStockProducts constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'blockinstockproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'NaturePinks';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Block In Stock Products');
        $this->description = $this->l('Adds a block displaying your store\'s in stock products.');
        $this->tb_versions_compliancy = '> 1.0.0';

        if (Configuration::get(static::CACHE_TIMESTAMP) < (time() - Configuration::get(static::CACHE_TTL))) {
            $this->clearCache();
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function install()
    {
        $this->clearCache();

        if (!parent::install()
            || !$this->registerHook('header')
            || !$this->registerHook('leftColumn')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('addproduct')
            || !$this->registerHook('updateproduct')
            || !$this->registerHook('deleteproduct')
            || !$this->registerHook('displayHomeTab')
            || !$this->registerHook('displayHomeTabContent')
            || !ProductSale::fillProductSales()
        ) {
            return false;
        }

        Configuration::updateValue(static::INSTOCKPRODUCTS_TO_DISPLAY, 10);

        return true;
    }

    /**
     * @since 1.0.0
     */
    public function clearCache()
    {
        try {
            $caches = [
                'blockinstockproducts.tpl'      => 'blockinstockproducts-col',
                'blockinstockproducts-home.tpl' => 'blockinstockproducts-home',
                'tab.tpl'                   => 'blockinstockproducts-tab',
            ];

            foreach ($caches as $template => $cacheId) {
                Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath($template), $cacheId);
            }

            Configuration::updateValue(static::CACHE_TIMESTAMP, time());
        } catch (Exception $e) {
            Logger::addLog("Block in stock products module: {$e->getMessage()}");
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function uninstall()
    {
        $this->clearCache();

        return parent::uninstall();
    }

    /**
     * @since 1.0.0
     */
    public function hookAddProduct()
    {
        $this->clearCache();
    }

    /**
     * @since 1.0.0
     */
    public function hookUpdateProduct()
    {
        $this->clearCache();
    }

    /**
     * @since 1.0.0
     */
    public function hookDeleteProduct()
    {
        $this->clearCache();
    }

    /**
     * @since 1.0.0
     */
    public function hookActionOrderStatusPostUpdate()
    {
        $this->clearCache();
    }

    /**
     * Called in administration -> module -> configure
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitInStockProducts')) {
            Configuration::updateValue(
                static::INSTOCKPRODUCTS_DISPLAY,
                (int) Tools::getValue(static::INSTOCKPRODUCTS_DISPLAY)
            );
            Configuration::updateValue(
                static::INSTOCKPRODUCTS_TO_DISPLAY,
                (int) Tools::getValue(static::INSTOCKPRODUCTS_TO_DISPLAY)
            );
			Configuration::updateValue(
                static::INSTOCKPRODUCTS_RANDOM,
                (int) Tools::getValue(static::INSTOCKPRODUCTS_RANDOM)
            );
            Configuration::updateValue(
                static::CACHE_TTL,
                (int) Tools::getValue(static::CACHE_TTL) * 60
            );
            $this->clearCache();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->renderForm();
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
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
                        'name'  => static::INSTOCKPRODUCTS_TO_DISPLAY,
                        'desc'  => $this->l('Determine the number of products to display, 0 for all'),
                        'class' => 'fixed-width-xs',
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Always display this block'),
                        'name'    => static::INSTOCKPRODUCTS_DISPLAY,
                        'desc'    => $this->l('Show the block even if no in stock products are available - possibly works in right column alone, for a future version'),
                        'is_bool' => true,
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
                        'type'    => 'switch',
                        'label'   => $this->l('Randomize products'),
                        'name'    => static::INSTOCKPRODUCTS_RANDOM,
                        'desc'    => $this->l('Randomize products positions on each cache rebuild.'),
                        'is_bool' => true,
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
                        'desc'   => $this->l('Determines for how long the instockproducts block stays cached'),
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
        $this->fields_form = [];

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
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            static::INSTOCKPRODUCTS_TO_DISPLAY => (int) Tools::getValue(
                static::INSTOCKPRODUCTS_TO_DISPLAY,
                Configuration::get(static::INSTOCKPRODUCTS_TO_DISPLAY)
            ),
            static::INSTOCKPRODUCTS_DISPLAY    => (int) Tools::getValue(
                static::INSTOCKPRODUCTS_DISPLAY,
                Configuration::get(static::INSTOCKPRODUCTS_DISPLAY)
            ),
			static::INSTOCKPRODUCTS_RANDOM    => (int) Tools::getValue(
                static::INSTOCKPRODUCTS_RANDOM,
                Configuration::get(static::INSTOCKPRODUCTS_RANDOM)
            ),
            static::CACHE_TTL              => (int) Tools::getValue(
                    static::CACHE_TTL,
                    Configuration::get(static::CACHE_TTL) / 60
                ),
        ];
    }

    /**
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function hookHeader()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index') {
            $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
        }
        $this->context->controller->addCSS($this->_path.'blockinstockproducts.css', 'all');
    }

    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookDisplayHomeTab()
    {
        if (!$this->isCached('tab.tpl', $this->getCacheId('blockinstockproducts-tab'))) {
            self::$cacheInStockProducts = $this->getInStockProducts();
            $this->smarty->assign('in_stock_products', self::$cacheInStockProducts);
        }

        if (self::$cacheInStockProducts === false) {
            return false;
        }

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blockinstockproducts-tab'));
    }

        /**
     * NP: Get In Stock products - based on getNewProducts standard function
     * Date difference check is replaced with stock quantity check
     * These parameters are all defined within the function 
     * Minimal changes from original function for future compatibility
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
    protected function getInStockProducts()
    {
		if (Configuration::get(static::INSTOCKPRODUCTS_RANDOM) == 1)
		{
			$orderWay = array('ASC', 'DESC')[mt_rand(0,1)];
			$orderBy = array(
				'price',
				'name',
				'date_add',
				'date_upd',
				'id_product',
				)[mt_rand(0,4)];
		}
		else
		{
			// NP: Params in original function getNewProducts are locally defined
			$orderWay = 'ASC';
			$orderBy = 'position';
		}
        $pageNumber = 0;
        $nbProducts = (int) Configuration::get(static::INSTOCKPRODUCTS_TO_DISPLAY);
        $idLang = (int) $this->context->language->id;
        
        $count = false;

        $front = true;
        if (!in_array($this->context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        // if ($pageNumber < 0) {
        //     $pageNumber = 0;
        // }
        // if ($nbProducts < 1) {
        //     $nbProducts = 10;
        // }
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
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $this->context->shop->id);
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
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $this->context->shop->id);
        }
        $sql->join(Product::sqlStock('p', 0));
		// print $sql;
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

        return Product::getProductsProperties((int) $idLang, $result);
    }


    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookDisplayHomeTabContent()
    {
        return $this->hookDisplayHome();
    }

    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookDisplayHome()
    {
        if (!$this->isCached('blockinstockproducts-home.tpl', $this->getCacheId('blockinstockproducts-home'))) {
            $this->smarty->assign(
                [
                    'in_stock_products' => self::$cacheInStockProducts,
                    'homeSize'     => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        }

        if (self::$cacheInStockProducts === false) {
            return false;
        }

        return $this->display(
            __FILE__,
            'blockinstockproducts-home.tpl',
            $this->getCacheId('blockinstockproducts-home')
        );
    }

    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @return bool|string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookRightColumn()
    {
        if (!$this->isCached('blockinstockproducts.tpl', $this->getCacheId('blockinstockproducts-col'))) {
            if (!isset(self::$cacheInStockProducts)) {
                self::$cacheInStockProducts = $this->getInStockProducts();
            }
            $this->smarty->assign(
                [
                    'in_stock_products'             => self::$cacheInStockProducts,
                    'display_link_instockproducts' => Configuration::get(static::INSTOCKPRODUCTS_DISPLAY),
                    'mediumSize'               => Image::getSize(ImageType::getFormatedName('medium')),
                    'smallSize'                => Image::getSize(ImageType::getFormatedName('small')),
                ]
            );
        }

        if (self::$cacheInStockProducts === false) {
            return false;
        }

        return $this->display(__FILE__, 'blockinstockproducts.tpl', $this->getCacheId('blockinstockproducts-col'));
    }

}
