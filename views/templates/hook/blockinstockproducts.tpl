yast{**
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
 *}

<!-- MODULE Block in stock products -->
<div id="in-stock-products_block_right" class="block products_block">
  <h4 class="title_block">
    <a href="{$link->getPageLink('in-stock')|escape:'htmlall':'UTF-8'}"
       title="{l s='View in stock products' mod='blockinstockproducts'}">
      {l s='In Stock' mod='blockinstockproducts'}
    </a>
  </h4>

  <div class="block_content">
    {if $in_stock_products && $in_stock_products|@count > 0}
      <ul class="product_images">
        {foreach from=$in_stock_products item=product name=myLoop}
          <li class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} clearfix">
            <a href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.legend|escape:'html':'UTF-8'}"
               class="content_img clearfix">
              <span class="number">{$smarty.foreach.myLoop.iteration}</span>
              <img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small_default')|escape:'htmlall':'UTF-8'}"
                   height="{$smallSize.height}" width="{$smallSize.width}"
                   alt="{$product.legend|escape:'htmlall':'UTF-8'}"/>

            </a>
            {if !$PS_CATALOG_MODE}
              <p>
                <a href="{$product.link|escape:'htmlall'}" title="{$product.legend|escape:'htmlall':'UTF-8'}">
                  {$product.name|strip_tags:'UTF-8'|escape:'htmlall':'UTF-8'}<br/>
                  {if !$PS_CATALOG_MODE}
                    <span class="price">{$product.price}</span>
                    {hook h="displayProductPriceBlock" product=$product type="price"}
                  {/if}
                </a>
              </p>
            {/if}
          </li>
        {/foreach}
      </ul>
      <p class="lnk"><a href="{$link->getPageLink('best-sales')|escape:'htmlall':'UTF-8'}"
                        title="{l s='All in stock products' mod='blockinstockproducts'}"
                        class="button_large">
          &raquo; {l s='All in stock products' mod='blockinstockproducts'}
        </a>
      </p>
    {else}
      <p>{l s='No products in stock at this time' mod='blockinstockproducts'}</p>
    {/if}
  </div>
</div>
<!-- /MODULE Block in stock products -->
