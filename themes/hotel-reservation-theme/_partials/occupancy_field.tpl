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

<div class="form-group dropdown">
    {block name='occupancy_field_button'}
        <button class="form-control booking_guest_occupancy input-occupancy{if isset($error) && $error == 1} error_border{/if}" type="button">
            <span class="">
                {if isset($occupancies) && $occupancies}
                    {if (isset($occupancy_adults) && $occupancy_adults)}{$occupancy_adults} {if $occupancy_adults > 1}{l s='Adults'}{else}{l s='Adult'}{/if}, {if isset($occupancy_children) && $occupancy_children}{$occupancy_children} {if $occupancy_children > 1} {l s='Children'}{else}{l s='Child'}{/if}, {/if}{$occupancies|count} {if $occupancies|count > 1}{l s='Rooms'}{else}{l s='Room'}{/if}{else}{l s='1 Adult, 1 Room'}{/if}
                {else}
                    {l s='Select Occupancy'}
                {/if}
            </span>
        </button>
    {/block}

    {block name='occupancy_field_content'}
        <div class="dropdown-menu booking_occupancy_wrapper">
            <input type="hidden" class="max_avail_type_qty" value="{if isset($total_available_rooms)}{$total_available_rooms|escape:'html':'UTF-8'}{/if}">
            <input type="hidden" class="max_adults" value="{$room_type_info['max_adults']|escape:'html':'UTF-8'}">
            <input type="hidden" class="max_children" value="{$room_type_info['max_children']|escape:'html':'UTF-8'}">
            <input type="hidden" class="max_guests" value="{$room_type_info['max_guests']|escape:'html':'UTF-8'}">
            <input type="hidden" class="base_adult" value="{$room_type_info['adults']|escape:'html':'UTF-8'}">
            <input type="hidden" class="base_children" value="{$room_type_info['children']|escape:'html':'UTF-8'}">
            <div class="booking_occupancy_inner">
                {if isset($occupancies) && $occupancies}
                    {assign var=countRoom value=1}
                    {foreach from=$occupancies key=key item=$occupancy name=occupancyInfo}
                        <div class="occupancy_info_block selected" occ_block_index="{$key|escape:'htmlall':'UTF-8'}">
                            <div class="occupancy_info_head"><span class="room_num_wrapper">{l s='Room'} - {$countRoom|escape:'htmlall':'UTF-8'} </span>{if !$smarty.foreach.occupancyInfo.first}<a class="remove-room-link pull-right" href="#">{l s='Remove'}</a>{/if}</div>
                            <div class="row">
                                <div class="form-group col-sm-5 col-xs-6 occupancy_count_block">
                                    <div class="row">
                                        <label class="col-sm-12">{l s='Adults'}</label>
                                        <div class="col-sm-12">
                                            <input type="hidden" class="num_occupancy num_adults room_occupancies" name="occupancy[{$key|escape:'htmlall':'UTF-8'}][adults]" value="{$occupancy['adults']|escape:'htmlall':'UTF-8'}">
                                            <div class="occupancy_count pull-left">
                                                <span>{$occupancy['adults']|escape:'htmlall':'UTF-8'}</span>
                                            </div>
                                            <div class="qty_direction pull-left">
                                                <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_up">
                                                    <span><i class="icon-plus"></i></span>
                                                </a>
                                                <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_down">
                                                    <span><i class="icon-minus"></i></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-7 col-xs-6 occupancy_count_block {if !$room_type_info['max_children']} hide {/if}">
                                    <div class="row">
                                        <label class="col-sm-12">{l s='Children'}</label>
                                        <div class="col-sm-12 clearfix">
                                            <input type="hidden" class="num_occupancy num_children room_occupancies" name="occupancy[{$key|escape:'htmlall':'UTF-8'}][children]" max="{}" value="{$occupancy['children']|escape:'htmlall':'UTF-8'}">
                                            <div class="occupancy_count pull-left">
                                                <span>{$occupancy['children']|escape:'htmlall':'UTF-8'}</span>
                                            </div>
                                            <div class="qty_direction pull-left">
                                                <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_up">
                                                    <span><i class="icon-plus"></i></span>
                                                </a>
                                                <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_down">
                                                    <span><i class="icon-minus"></i></span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <span class="label-desc-txt">({l s='Below'}  {$max_child_age|escape:'htmlall':'UTF-8'} {l s='years'})</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p style="display:none;"><span class="text-danger occupancy-input-errors"></span></p>
                            <div class="form-group row children_age_info_block" {if isset($occupancy['child_ages']) && $occupancy['child_ages']}style="display:block;"{/if}>
                                <label class="col-sm-12">{l s='All Children'}</label>
                                <div class="col-sm-12">
                                    <div class="children_ages">
                                        {if isset($occupancy['child_ages']) && $occupancy['child_ages']}
                                            {foreach $occupancy['child_ages'] as $childAge}
                                                <div>
                                                    <select class="guest_child_age room_occupancies" name="occupancy[{$key|escape:'htmlall':'UTF-8'}][child_ages][]">
                                                        <option value="-1" {if $childAge == -1}selected{/if}>{l s='Select 1'}</option>
                                                        <option value="0" {if $childAge == 0}selected{/if}>{l s='Under 1'}</option>
                                                        {for $age=1 to ($max_child_age-1)}
                                                            <option value="{$age|escape:'htmlall':'UTF-8'}" {if $childAge == $age}selected{/if}>{$age|escape:'htmlall':'UTF-8'}</option>
                                                        {/for}
                                                    </select>
                                                </div>
                                            {/foreach}
                                        {/if}
                                    </div>
                                </div>
                            </div>
                            <hr class="occupancy-info-separator">
                        </div>
                        {assign var=countRoom value=$countRoom+1}
                    {/foreach}
                {else}
                    <div class="occupancy_info_block" occ_block_index="0">
                        <div class="occupancy_info_head"><span class="room_num_wrapper">{l s='Room - 1'}</span></div>
                        <div class="row">
                            <div class="form-group col-sm-5 col-xs-6 occupancy_count_block">
                                <div class="row">
                                    <label class="col-sm-12">{l s='Adults'}</label>
                                    <div class="col-sm-12">
                                        <input type="hidden" class="num_occupancy num_adults" name="occupancy[0][adults]" value="{$room_type_info['adults']}">
                                        <div class="occupancy_count pull-left">
                                            <span>{$room_type_info['adults']}</span>
                                        </div>
                                        <div class="qty_direction pull-left">
                                            <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_up">
                                                <span>
                                                    <i class="icon-plus"></i>
                                                </span>
                                            </a>
                                            <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_down">
                                                <span>
                                                    <i class="icon-minus"></i>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-sm-7 col-xs-6 occupancy_count_block {if !$room_type_info['max_children']} hide {/if}">
                                <div class="row">
                                    <label class="col-sm-12">{l s='Children'}</label>
                                    <div class="col-sm-12 clearfix">
                                        <input type="hidden" class="num_occupancy num_children" name="occupancy[0][children]" value="0">
                                        <div class="occupancy_count pull-left">
                                            <span>0</span>
                                        </div>
                                        <div class="qty_direction pull-left">
                                            <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_up">
                                                <span>
                                                    <i class="icon-plus"></i>
                                                </span>
                                            </a>
                                            <a href="#" data-field-qty="qty" class="btn btn-default occupancy_quantity_down">
                                                <span>
                                                    <i class="icon-minus"></i>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <span class="label-desc-txt">({l s='Below'}  {$max_child_age|escape:'htmlall':'UTF-8'} {l s='years'})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p style="display:none;"><span class="text-danger occupancy-input-errors"></span></p>
                        <div class="form-group row children_age_info_block">
                            <label class="col-sm-12">{l s='All Children'}</label>
                            <div class="col-sm-12">
                                <div class="children_ages">
                                </div>
                            </div>
                        </div>
                        <hr class="occupancy-info-separator">
                    </div>
                {/if}
            </div>
            {block name='occupancy_field_actions'}
                <div class="occupancy_block_actions">
                    <span class="add_occupancy_block">
                        <a class="add_new_occupancy_btn {if isset($occupancies) && $occupancies && isset($total_available_rooms) && $total_available_rooms <= count($occupancies)} disabled{/if}" data-title-available="{l s='Click to add more rooms.'}" data-title-unavailable="{l s='No more rooms available.'}" href="#">
                            <i class="icon-plus"></i>
                            <span>{l s='Add Room'}</span>
                        </a>
                    </span>
                    <span>
                        <button type="submit" class="submit_occupancy_btn btn btn-primary">{l s='Done'}</button>
                    </span>
                </div>
            {/block}
        </div>
    {/block}
</div>
