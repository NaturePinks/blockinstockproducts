{**
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
* @copyright 2017-2020 NaturePinks 
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 *}

<!-- MODULE Block in stock products -->
<div id="instock-products_block_right" class="block products_block">
  <h4 class="title_block"><a href="{$link->getPageLink('in-stock')|escape:'html'}"title="{l s='In stock products' mod='blockinstockproducts'}">{l s='In Stock' mod='blockinstockproducts'}</a></h4>
  <div class="block_content">
    {if $instock_products !== false}
      <ul class="product_images clearfix">
		{$imageSize=Image::getSize('medium')}      
        {foreach from=$instock_products item='product' name='instockProducts'}
			{if $smarty.foreach.instockProducts.index < 2}
				<li{if $smarty.foreach.instockProducts.first} class="first"{/if}><a href="{$product.link|escape:'html'}" title="{$product.legend|escape:html:'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'medium')|escape:'html'}" height="{$imageSize.height}" width="{$imageSize.width}" alt="{$product.legend|escape:html:'UTF-8'}" /></a></li>
			{/if}
        {/foreach}
      </ul>
		<dl class="products">
		{foreach from=$instock_products item=instockproduct name=myLoop}
			<dt class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if}"><a href="{$instockproduct.link|escape:'html'}" title="{$instockproduct.name|escape:html:'UTF-8'}">{$instockproduct.name|strip_tags|escape:html:'UTF-8'}</a></dt>
			{if $instockproduct.description_short}<dd class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if}"><a href="{$instockproduct.link|escape:'html'}">{$instockproduct.description_short|strip_tags:'UTF-8'|truncate:75:'...'}</a><br /><a href="{$instockproduct.link}" class="lnk_more">{l s='Read more' mod='blockinstockproducts'}</a></dd>{/if}
		{/foreach}
		</dl>
		<p><a href="{$link->getPageLink('instock-products')|escape:'html'}" title="{l s='In Stock products' mod='blocknewproducts'}" class="button_large">&raquo; {l s='All in stock products' mod='blockinstockproducts'}</a></p>
	{else}
		<p>&raquo; {l s='No products in stock at this time.' mod='blockinstockproducts'}</p>
	{/if}
	</div>
</div>      
<!-- /MODULE Block in stock products -->
