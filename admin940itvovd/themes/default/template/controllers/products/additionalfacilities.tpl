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

 {if isset($product->id) && $product->id}
    <input type="hidden" name="submitted_tabs[]" value="AdditionalFacilities" />
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-user"></i> {l s='Room Type Additional Facilities'}
        </div>
        <div class="alert alert-info">
            {l s='To create new additional facilities please visit'} <a target="_blank" href="{$link->getAdminLink('AdminRoomTypeGlobalDemand')}">{l s='Additional facilities'}</a> {l s='page.'}
        </div>
        {if isset($allDemands) && $allDemands}

            <div class="from-group table-responsive-row clearfix">
                <table class="table" id="demands_table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>
                                {l s='Name'}
                            </th>
                            <th>
                                {l s='Option'}
                            </th>
                            <th class="fixed-width-xl">
                                {l s='Price'}
                            </th>
                            <th>
                                {l s='Tax rule'}
                            </th>
                            <th class="fixed-width-lg text-center">
                                {l s='Per day price calculation'}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $allDemands as $key => $demand}
                            {assign var="rowcount" value=0}
                            {if isset($demand['adv_option']) && $demand['adv_option']}
                                {assign var="rowspan" value=$demand['adv_option']|count}
                                {assign var="adv_option" value=$demand['adv_option']}
                            {else}
                                {assign var="rowspan" value=1}
                                {assign var="adv_option" value=[]}
                            {/if}
                                {foreach $adv_option as $option}
                                    {assign var="rowcount" value=$rowcount + 1}
                                    <tr>
                                        {if $rowcount <= 1}
                                            <td rowspan="{$rowspan}">
                                                <input class="selected_demand" type="checkbox" name="selected_demand[]" value="{$demand['id_global_demand']|escape:'html':'UTF-8'}" {if isset($selectedDemands[$demand['id_global_demand']])}checked{/if} />
                                                <input type="hidden" name="demand_price_{$demand['id_global_demand']|escape:'html':'UTF-8'}" value="{if isset($selectedDemands[$demand['id_global_demand']]['price'])}{$selectedDemands[$demand['id_global_demand']]['price']|escape:'html':'UTF-8'}{elseif isset($demand['price'])}{$demand['price']|escape:'html':'UTF-8'}{/if}"/>
                                            </td>
                                            <td rowspan="{$rowspan}">
                                                <a target="blank" href="{$link->getAdminLink('AdminRoomTypeGlobalDemand')|escape:'html':'UTF-8'}&amp;id_global_demand={$demand['id_global_demand']|escape:'html':'UTF-8'}&amp;updatehtl_room_type_global_demand"><i class="icon-external-link-sign"></i></a> {$demand['name']|escape:'html':'UTF-8'}
                                            </td>
                                        {/if}
                                        <td>
                                            {$option['name']|escape:'html':'UTF-8'}
                                        </td>
                                        <td class="demand_price_{$demand['id_global_demand']}">
                                            <div class="input-group price_input" {if !isset($selectedDemands[$demand['id_global_demand']])}style="display:none"{/if}>
                                                <span class="input-group-addon">{$defaultcurrencySign|escape:'html':'UTF-8'}</span>
                                                <input type="text" name="option_price_{$option['id']|escape:'html':'UTF-8'}" value="{if isset($selectedDemands[$demand['id_global_demand']]['adv_option'][$option['id']]['price'])}{$selectedDemands[$demand['id_global_demand']]['adv_option'][$option['id']]['price']|escape:'html':'UTF-8'}{else}{$option['price']|escape:'html':'UTF-8'}{/if}"/>
                                            </div>
                                            <div class="price_display" {if isset($selectedDemands[$demand['id_global_demand']])}style="display:none"{/if}>
                                                {displayPrice price={$option['price']|escape:'html':'UTF-8'}  currency=$idDefaultcurrency}
                                            </div>

                                        </td>
                                        {if $rowcount == 1}
                                            <td rowspan="{$rowspan}">
                                                {$demand['default_tax_rules_group_name']}
                                            </td>
                                            <td class="text-center" rowspan="{$rowspan}">
                                                {if $demand['price_calc_method'] == 1}
                                                    <span class="badge badge-success">{l s='Yes'}</span>
                                                {else}
                                                    <span>{l s='No'}</span>
                                                {/if}
                                            </td>
                                        {/if}
                                    </tr>
                                {foreachelse}
                                    <tr>
                                        <td>
                                            <input class="selected_demand" type="checkbox" name="selected_demand[]" value="{$demand['id_global_demand']|escape:'html':'UTF-8'}" {if isset($selectedDemands[$demand['id_global_demand']])}checked{/if} />
                                        </td>
                                        <td>
                                            <a target="blank" href="{$link->getAdminLink('AdminRoomTypeGlobalDemand')|escape:'html':'UTF-8'}&amp;id_global_demand={$demand['id_global_demand']|escape:'html':'UTF-8'}&amp;updatehtl_room_type_global_demand"><i class="icon-external-link-sign"></i></a> {$demand['name']|escape:'html':'UTF-8'}
                                        </td>

                                        <td></td>
                                        <td class="demand_price_{$demand['id_global_demand']}">
                                            <div class="input-group price_input" {if !isset($selectedDemands[$demand['id_global_demand']])}style="display:none"{/if}>
                                                <span class="input-group-addon">{$defaultcurrencySign|escape:'html':'UTF-8'}</span>
                                                <input type="text" name="demand_price_{$demand['id_global_demand']|escape:'html':'UTF-8'}"
                                                value="{if isset($selectedDemands[$demand['id_global_demand']]['price'])}{$selectedDemands[$demand['id_global_demand']]['price']|escape:'html':'UTF-8'}{elseif isset($demand['price'])}{$demand['price']|escape:'html':'UTF-8'}{/if}"/>
                                            </div>
                                            <div class="price_display" {if isset($selectedDemands[$demand['id_global_demand']])}style="display:none"{/if}>
                                                {displayPrice price={$demand['price']|escape:'html':'UTF-8'} currency=$idDefaultcurrency}
                                            </div>
                                        </td>
                                        <td>
                                            {$demand['default_tax_rules_group_name']}
                                        </td>
                                        <td class="text-center">
                                            {if $demand['price_calc_method'] == 1}
                                                <span class="badge badge-success">{l s='Yes'}</span>
                                            {else}
                                                <span>{l s='No'}</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                        {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default">
                    <i class="process-icon-cancel"></i>
                    {l s='Cancel'}
                </a>
                <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled">
                    <i class="process-icon-loading"></i>
                    {l s='Save'}
                </button>
                <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"  disabled="disabled">
                    <i class="process-icon-loading"></i>
                        {l s='Save and stay'}
                </button>
            </div>
        {/if}
    </div>
{/if}