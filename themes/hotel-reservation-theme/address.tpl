{*
* 2007-2017 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{block name='address'}
	{capture name=path}
		<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
			{l s='My account'}
		</a>
		<span class="navigation-pipe">
			{$navigationPipe}
		</span>
		<span class="navigation_page">
			{l s='Address'}
		</span>
	{/capture}
	
	{block name='address_heading'}
		<h1 class="page-heading">
			{if isset($id_address) && (isset($smarty.post.alias) || isset($address->alias))}
				{l s='Modify address'}
				{if isset($smarty.post.alias)}
					"{$smarty.post.alias}"
				{else}
					{if isset($address->alias)}"{$address->alias|escape:'html':'UTF-8'}"{/if}
				{/if}
			{else}
				{l s='To add an address, please fill out the form below.'}
			{/if}
		</h1>
	{/block}
	
	{block name='errors'}
		{include file="$tpl_dir./errors.tpl"}
	{/block}
	
	{block name='address_form'}
		<form action="{$link->getPageLink('address', true)|escape:'html':'UTF-8'}" method="post" class="std" id="add_address">
			<div class="box">
				<h3 class="page-subheading">
					{if isset($id_address) && (isset($smarty.post.alias) || isset($address->alias))}
						{l s='Modify address'}
					{else}
						{l s='To add an address, please fill out the form below.'}
					{/if}
				</h3>
				{include file="$tpl_dir./address-form.tpl"}
				<p class="submit2">
					{if isset($id_address)}<input type="hidden" name="id_address" value="{$id_address|intval}" />{/if}
					{if isset($back)}<input type="hidden" name="back" value="{$back}" />{/if}
					{if isset($mod)}<input type="hidden" name="mod" value="{$mod}" />{/if}
					{if isset($select_address)}<input type="hidden" name="select_address" value="{$select_address|intval}" />{/if}
					<input type="hidden" name="token" value="{$token}" />
					<button type="submit" name="submitAddress" id="submitAddress" class="btn btn-default button button-medium">
						<span>
							{l s='Save'}
							<i class="icon-chevron-right right"></i>
						</span>
					</button>
				</p>
			</div>
		</form>
	{/block}
	
	{block name='address_footer_links'}
		<ul class="footer_links clearfix">
			<li>
				<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
					<i class="icon-chevron-left"></i> {l s='Back to Your Account'}
				</a>
			</li>
			<li>
				<a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}">
					<i class="icon-chevron-left"></i> {l s='Home'}
				</a>
			</li>
		</ul>
	{/block}
{/block}
