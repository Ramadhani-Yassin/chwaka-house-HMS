{**
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

{if isset($resolvableOverBookings) && $resolvableOverBookings}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-refresh"></i> {l s='Resolvable Overbookings'}
        </div>
        <div class="panel-content">
            {if isset($smarty.get.resolvable_overbooked_orders) && $smarty.get.resolvable_overbooked_orders}
                <div class="alert alert-warning">
                    <b>{l s='Orders with resolvable overbookings are filtered.'}  <a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}" class="btn btn-warning"><i class="icon-refresh"></i> {l s='See all orders'}</a></b>
                </div>
            {/if}
            <div class="alert alert-info">
                <p>{l s='Some overbookings are now available to be resolved. You can directly resolve overbookings from below list as per your choice.'}</p>
                {if !isset($smarty.get.resolvable_overbooked_orders) || !$smarty.get.resolvable_overbooked_orders}
                    <br>
                    <p>{l s='You can also filter orders which overbookings are now available to be resolved.'}  <a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;resolvable_overbooked_orders=1" class="btn btn-default"><i class="icon-search"></i> {l s='Filter orders with resolvable overbookings'}</a></p>
                {/if}
            </div>
            <div class="table-responsive form-group">
                <table class="table table-striped">
                    <tr>
                        <th>{l s='Room No.'}</th>
                        <th>{l s='Room type'}</th>
                        <th>{l s='Hotel'}</th>
                        <th>{l s='Duration'}</th>
                        <th>{l s='Order'}</th>
                        <th>{l s='Resolve'}</th>
                    </tr>
                    {foreach from=$resolvableOverBookings item=data}
                        {if !$data.is_refunded}
                            <tr>
                                <td>{$data['room_num']}</td>
                                <td>
                                    <a href="{$link->getAdminLink('AdminProducts')}&amp;id_product={$data['id_product']|escape:'html':'UTF-8'}&amp;updateproduct">{$data['room_type_name']|escape:'html':'UTF-8'}</a>
                                </td>
                                <td>
                                    <a href="{$link->getAdminLink('AdminAddHotel')}&amp;id={$data['id_hotel']|escape:'html':'UTF-8'}&amp;updatehtl_branch_info" target="_blank"><span>{$data['hotel_name']}</span></a>
                                </td>
                                {assign var="is_full_date" value=($show_full_date && ($data['date_from']|date_format:'%D' == $data['date_to']|date_format:'%D'))}
                                <td>{dateFormat date=$data['date_from']|escape:'html':'UTF-8' full=$is_full_date} {l s='To'} {dateFormat date=$data['date_to']|escape:'html':'UTF-8' full=$is_full_date}</td>
                                <td>
                                    <a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$data['id_order']|escape:'html':'UTF-8'}">#{$data['id_order']|escape:'html':'UTF-8'}</a>
                                </td>
                                <td>
                                    {if isset($data['booked_room_info']) && $data['booked_room_info']}
                                        <span class="badge badge-information">{l s='Already booked'}</span>
                                    {else}
                                        <a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;resolve_overbooking={$data['id']|escape:'html':'UTF-8'}" class="btn btn-default resolve_overbooking" id_htl_booking="{$data['id']|escape:'html':'UTF-8'}"><i class="icon-refresh"></i> {l s='Resolve'}</a>
                                    {/if}
                                </td>
                            </tr>
                        {/if}
                    {/foreach}
                </table>
            </div>
        </div>
    </div>

{/if}
