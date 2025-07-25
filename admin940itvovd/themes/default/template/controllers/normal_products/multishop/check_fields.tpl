{*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*}

{if isset($display_multishop_checkboxes) && $display_multishop_checkboxes}
	<div class="panel clearfix">
		<label class="control-label col-lg-3">
			<i class="icon-sitemap"></i> {l s='Multistore'}
		</label>
		<div class="col-lg-9">
			<div class="row">
				<div class="col-lg-4">
					<span class="switch prestashop-switch">
						<input type="radio" name="multishop_{$product_tab}" id="multishop_{$product_tab}_on" value="1" onclick="$('#product-tab-content-{$product_tab} input[name^=\'multishop_check[\']').attr('checked', true); ProductMultishop.checkAll{$product_tab}()">
						<label for="multishop_{$product_tab}_on">
							{l s='Yes'}
						</label>
						<input type="radio" name="multishop_{$product_tab}" id="multishop_{$product_tab}_off" value="0" checked="checked" onclick="$('#product-tab-content-{$product_tab} input[name^=\'multishop_check[\']').attr('checked', false); ProductMultishop.checkAll{$product_tab}()">
						<label for="multishop_{$product_tab}_off">
							{l s='No'}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<p class="help-block">
						<strong>{l s='Check / Uncheck all'}</strong> {l s='(If you are editing this page for several shops, some fields may be disabled. If you need to edit them, you will need to check the box for each field)'}
					</p>
				</div>
			</div>
		</div>
	</div>
{/if}
