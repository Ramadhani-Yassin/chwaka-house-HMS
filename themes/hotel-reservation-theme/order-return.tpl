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

{block name='order_return'}
	{capture name=path}
		<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
			{l s='My account'}
		</a>
		<span class="navigation-pipe">
			{$navigationPipe}
		</span>
		<a href="{$link->getPageLink('order-follow', true)|escape:'html':'UTF-8'}">
			{l s='Refund requests'}
		</a>
		<span class="navigation-pipe">
			{$navigationPipe}
		</span>
		<span class="navigation_page">
			{l s='Refund detail'}
		</span>
	{/capture}

	{block name='errors'}
		{include file="./errors.tpl"}
	{/block}
	<div class="panel card">
		{block name='order_return_heading'}
			<h1 class="page-heading bottom-indent">
				<i class="icon-tasks"></i> &nbsp;{l s='Refund Requests'}
			</h1>
		{/block}
		{block name='order_return_detail'}
			{if $refundReqBookings}
                {if isset($refundReqProducts) && $refundReqProducts}
                    <h1 class="page-subheading">{l s='Rooms refund requests'}</h1>
                {/if}
				<div class="table-responsive wk-datatable-wrapper">
					<table class="table table-bordered">
						<tr>
							<th>{l s='Rooms'}</th>
							<th>{l s='Room type'}</th>
							<th>{l s='Hotel'}</th>
							<th>{l s='Duration'}</th>
							<th>{l s='Total rooms price (tax incl.)'}</th>
							<th>{l s='Extra services price (tax incl.)'}</th>
							{if $isRefundCompleted}
								<th>{l s='Refund amount'}</th>
								<th>{l s='Refund Status'}</th>
							{/if}
						</tr>
						{foreach from=$refundReqBookings item=$booking name=refundRequest}
							<tr>
								<td>{l s='Room'} - {$smarty.foreach.refundRequest.iteration|string_format:'%02d'}</td>
								<td>{$booking['room_type_name']|escape:'htmlall':'UTF-8'}</td>
								<td>{$booking['hotel_name']|escape:'htmlall':'UTF-8'}</td>
								{assign var="is_full_date" value=($show_full_date && ($booking['date_from']|date_format:'%D' == $booking['date_to']|date_format:'%D'))}
								<td>{dateFormat date=$booking['date_from'] full=$is_full_date} {l s='To'} {dateFormat date=$booking['date_to'] full=$is_full_date}</td>
								<td>{displayPrice price=$booking['total_price_tax_incl'] currency=$orderCurrency['id']}</td>
								<td>{displayPrice price=$booking['extra_service_total_price_tax_incl'] currency=$orderCurrency['id']}</td>
								{if $isRefundCompleted}
									<td>
										{displayPrice price=$booking['refunded_amount'] currency=$orderCurrency['id']}
									</td>
									<td>
										{if $booking['is_cancelled']}
											<span class="badge badge-danger">{l s='Cancelled' mod='hotelreservationsystem'}</span>
										{* used id_customization to check if in this request which bookings are refunded or not*}
										{else if $booking['id_customization']}
											<span class="badge badge-success">{l s='Refunded' mod='hotelreservationsystem'}</span>
										{else}
											<span class="badge badge-danger">{l s='Denied' mod='hotelreservationsystem'}</span>
										{/if}
									</td>
								{/if}
							</tr>
						{/foreach}
					</table>
				</div>
			{/if}
			{if $refundReqProducts}
                <h1 class="page-subheading">{l s='Products refund requests'}</h1>
				<div class="table-responsive wk-datatable-wrapper">
					<table class="table table-bordered">
						<tr>
							<th>{l s='Product name'}</th>
							<th>{l s='Quantity'}</th>
							<th>{l s='Total price (tax incl.)'}</th>
							{if $isRefundCompleted}
								<th>{l s='Refund amount'}</th>
								<th>{l s='Refund Status'}</th>
							{/if}
						</tr>
						{foreach from=$refundReqProducts item=$product name=refundRequest}
							<tr>
								<td>{$product['name']|escape:'htmlall':'UTF-8'}{if isset($product['option_name']) && $product['option_name']} : {$product['option_name']}{/if}</td>
								<td>{if $product['allow_multiple_quantity']}{$product['quantity']|escape:'htmlall':'UTF-8'}{else}--{/if}</td>
								<td>{displayPrice price=$product['total_price_tax_incl'] currency=$orderCurrency['id']}</td>
								{if $isRefundCompleted}
									<td>
										{displayPrice price=$product['refunded_amount'] currency=$orderCurrency['id']}
									</td>
									<td>
										{if $product['is_cancelled']}
											<span class="badge badge-danger">{l s='Cancelled' mod='hotelreservationsystem'}</span>
										{* used id_customization to check if in this request which bookings are refunded or not*}
										{else if $product['id_customization']}
											<span class="badge badge-success">{l s='Refunded' mod='hotelreservationsystem'}</span>
										{else}
											<span class="badge badge-danger">{l s='Denied' mod='hotelreservationsystem'}</span>
										{/if}
									</td>
								{/if}
							</tr>
						{/foreach}
					</table>
				</div>
			{/if}
		{/block}

		{block name='order_return_current_status'}
			<div class="form-group row">
				<div class="col-md-2 col-sm-3">
					<strong>{l s='Current refund state'} </strong>
				</div>
				<div class="col-sm-9 col-md-10">
						<span class="badge wk-badge" style="background-color:{$currentStateInfo['color']|escape:'html':'UTF-8'}">{$currentStateInfo['name']|escape:'html':'UTF-8'}
					</span>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-md-2 col-sm-3">
					<strong>{l s='Way of payment'} </strong>
				</div>
				<div class="col-sm-9 col-md-10">
					{if $orderInfo['is_advance_payment']}{l s='Advance Payment'}{else}{l s='Full Payment'}{/if}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-md-2 col-sm-3">
					<strong>{l s='Total order amount'} </strong>
				</div>
				<div class="col-sm-9 col-md-10">
					{displayPrice price=$orderInfo['total_paid_tax_incl'] currency=$orderInfo['id_currency']}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-md-2 col-sm-3">
					<strong>{l s='Request date'} </strong>
				</div>
				<div class="col-sm-9 col-md-10">
					{$orderReturnInfo['date_add']|date_format:"%d-%m-%Y %I:%M %p"}
				</div>
			</div>

			{if $currentStateInfo['refunded']}
				<div class="form-group row">
					<div class="col-md-2 col-sm-3">
						<strong>{l s='Refunded amount' mod='hotelreservationsystem'}</strong>
					</div>
					<div class="col-sm-9 col-md-10">
						{displayPrice price=$orderReturnInfo['refunded_amount'] currency=$orderInfo['id_currency']}
					</div>
				</div>
				{if $orderReturnInfo['payment_mode'] != '' && $orderReturnInfo['id_transaction'] != ''}
					<div class="form-group row">
						<div class="col-md-2 col-sm-3">
							<strong>{l s='Payment mode' mod='hotelreservationsystem'}</strong>
						</div>
						<div class="col-sm-9 col-md-10">
							{$orderReturnInfo['payment_mode']|escape:'html':'UTF-8'}
						</div>
					</div>
					<div class="form-group row">
						<div class="col-md-2 col-sm-3">
							<strong>{l s='Transaction ID' mod='hotelreservationsystem'}</strong>
						</div>
						<div class="col-sm-9 col-md-10">
							{$orderReturnInfo['id_transaction']|escape:'html':'UTF-8'}
						</div>
					</div>
				{/if}
				{if isset($orderReturnInfo['return_type'])}
					{if $orderReturnInfo['return_type'] == OrderReturn::RETURN_TYPE_CART_RULE}
						<div class="form-group row">
							<div class="col-md-2 col-sm-3">
								<strong>{l s='Voucher' mod='hotelreservationsystem'}</strong>
							</div>
							<div class="col-sm-9 col-md-10">
								<a class="link" href="{$link->getPageLink('discount')}" target="_blank">
									{$voucher|escape:'html':'UTF-8'}
								</a>
							</div>
						</div>
					{elseif $orderReturnInfo['return_type'] == OrderReturn::RETURN_TYPE_ORDER_SLIP}
						<div class="form-group row">
							<div class="col-md-2 col-sm-3">
								<strong>{l s='Credit Slip' mod='hotelreservationsystem'}</strong>
							</div>
							<div class="col-sm-9 col-md-10">
								<a class="link" href="{$link->getPageLink('order-slip')}" target="_blank">
									#{Configuration::get('PS_CREDIT_SLIP_PREFIX', $lang_id)}{$orderReturnInfo['id_return_type']|string_format:"%06d"}
								</a>
							</div>
						</div>
					{/if}
				{/if}
			{/if}
		{/block}
	</div>

	{block name='order_return_footer_links'}
		<ul class="footer_links clearfix">
			<li><a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}"><span><i class="icon-chevron-left"></i> {l s='Back to your account'}</span></a></li>
			<li><a class="btn btn-default button button-small" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}"><span><i class="icon-chevron-left"></i> {l s='Home'}</span></a></li>
		</ul>
	{/block}
{/block}
