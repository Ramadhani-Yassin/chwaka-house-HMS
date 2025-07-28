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
* needs please refer to http://www.prestashop.com for more information.n
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $status == 'ok'}
        <div class="alert alert-success">
            <h4><i class="icon-check-circle"></i> {l s='Booking Confirmed!' mod='bankwire'}</h4>
            <p>{l s='Your' mod='bankwire'} {if $cart_room_bookings|count > 1}{l s='bookings have' mod='bankwire'}{else}{l s='booking has' mod='bankwire'}{/if} {l s='been created successfully!' mod='bankwire'}</p>
        </div>
        
        <div class="bankwire-instructions">
            <h4><i class="icon-bank"></i> {l s='How to Complete Your Payment' mod='bankwire'}</h4>
            <p><strong>{l s='Please follow these steps to complete your bank transfer:' mod='bankwire'}</strong></p>
            
            <div class="payment-details">
                <h5>{l s='Bank Transfer Details:' mod='bankwire'}</h5>
                <ul class="payment-list">
                    <li><strong>{l s='Amount to Transfer:' mod='bankwire'}</strong> <span class="price">{$total_to_pay}</span></li>
                    <li><strong>{l s='Account Owner Name:' mod='bankwire'}</strong> <span class="highlight">{if $bankwireOwner}{$bankwireOwner}{else}___________{/if}</span></li>
                    <li><strong>{l s='Account Details:' mod='bankwire'}</strong> <span class="highlight">{if $bankwireDetails}{$bankwireDetails}{else}___________{/if}</span></li>
                    <li><strong>{l s='Bank Account Number:' mod='bankwire'}</strong> <span class="highlight">{if $bankwireAddress}{$bankwireAddress}{else}___________{/if}</span></li>
                </ul>
            </div>
            
            <div class="transfer-steps">
                <h5>{l s='Transfer Steps:' mod='bankwire'}</h5>
                <ol>
                    <li>{l s='Go to your bank\'s online platform or visit your bank branch' mod='bankwire'}</li>
                    <li>{l s='Select "Transfer Money" or "Bank Transfer" option' mod='bankwire'}</li>
                    <li>{l s='Enter the account number above' mod='bankwire'}</li>
                    <li>{l s='The account owner name will appear automatically' mod='bankwire'}</li>
                    <li>{l s='Enter the amount to transfer' mod='bankwire'}</li>
                    <li>{l s='Add your order reference in the transfer description/memo' mod='bankwire'}</li>
                    <li>{l s='Confirm and complete the transfer' mod='bankwire'}</li>
                </ol>
            </div>
            
            <div class="order-reference">
                <h5>{l s='Important:' mod='bankwire'}</h5>
                {if !isset($reference)}
                    <p class="alert alert-info"><i class="icon-info-circle"></i> {l s='Do not forget to include your order number #%d in the transfer description/memo field.' sprintf=$id_order mod='bankwire'}</p>
                {else}
                    <p class="alert alert-info"><i class="icon-info-circle"></i> {l s='Do not forget to include your order reference %s in the transfer description/memo field.' sprintf=$reference mod='bankwire'}</p>
                {/if}
            </div>
            
            <div class="processing-time">
                <h5>{l s='Processing Time:' mod='bankwire'}</h5>
                <p>{l s='Bank transfers typically take 1-3 business days to process. Your booking will be confirmed once we receive your payment.' mod='bankwire'}</p>
            </div>
        </div>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='bankwire'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='bankwire'}</a>.
	</p>
{/if}
