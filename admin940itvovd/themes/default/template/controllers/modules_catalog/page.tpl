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
<div id="catalog-center-column">
    {if isset($modules) && $modules}
        <div id="suggested-modules-list" class="row list-container">
            <div class="col-sm-12">
                <div class="row form-horizontal list-header">
                    <div class="pull-left col-sm-6 title">{l s='Suggested Modules'}&nbsp;</div>
                    <div class="pull-right col-sm-3">
                        <div class="input-group">
                            <input type="text" name="module-search" id="module-search" placeholder="{l s='Search module name...'}">
                            <span class="input-group-addon">
                                <i class="icon-search"></i>
                            </span>
                        </div>
                    </div>
                    {if isset($sort_criterta) && $sort_criterta}
                        <div class="pull-right col-sm-3">
                            <div class="row">
                                <label class="pull-left sort-label"><span>{l s='Sort By :'}</span></label>
                                <div class="col-sm-9">
                                    <select name="module-sort" id="module-sort">
                                        {foreach $sort_criterta as $criteria}
                                            <option value="{$criteria['value']}" {if $criteria['value'] == $module_sort}selected{/if}>{$criteria['title']}</option>
                                        {/foreach}
                                    </select>
                                </div>

                            </div>
                        </div>
                    {/if}
                </div>
                {include file='controllers/modules_catalog/modules_list.tpl' elements=$modules}
                <div class="list-empty" style="display:none">
                    <div class="list-empty-msg">
                        <i class="icon-warning-sign list-empty-icon"></i>
                        {l s='No modules found'}
                    </div>
                </div>
                <div class="pagination-container">
                    <ul class="pagination-block"></ul>
                </div>
            </div>
        </div>
        <div class="panel explore-panel text-center">
            {l s='Explore all addon of Qloapps'}&nbsp;
            <a href="https://qloapps.com/addons/" class="btn btn-primary btn-lg">{l s='QloApps Addons'}</a>
        </div>
    {/if}
    {if isset($themes) && $themes}
        <div id="suggested-theme-list" class="row list-container">
            <div class="col-sm-12">
                <div class="row form-horizontal list-header">
                    {* <div class="row"> *}
                        <div class="pull-left col-sm-6 title">{l s='Suggested Themes'}&nbsp;</div>

                        <div class="pull-right col-sm-3">
                            <div class="input-group">
                                <input type="text" name="theme-search" id="theme-search" placeholder="{l s='Search theme name...'}">
                                <span class="input-group-addon">
                                    <i class="icon-search"></i>
                                </span>
                            </div>
                        </div>
                        {if isset($sort_criterta) && $sort_criterta}
                            <div class="pull-right col-sm-3">
                                <div class="row">
                                    {* <div class="col-sm-3"> *}
                                        <label class="pull-left sort-label"><span>{l s='Sort By :'}</span></label>
                                    {* </div> *}
                                    <div class="col-sm-9">
                                        <select name="theme-sort" id="theme-sort">
                                            {foreach $sort_criterta as $criteria}
                                                <option value="{$criteria['value']}" {if $criteria['value'] == $theme_sort}selected{/if}>{$criteria['title']}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        {/if}
                    {* </div> *}
                </div>
                {include file='controllers/modules_catalog/theme_list.tpl' elements=$themes}
                <div class="list-empty" style="display:none">
                    <div class="list-empty-msg">
                        <i class="icon-warning-sign list-empty-icon"></i>
                        {l s='No themes found'}
                    </div>
                </div>
                <div class="pagination-container">
                    <ul class="pagination-block"></ul>
                </div>
            </div>
        </div>
        <div class="panel explore-panel text-center">
            {l s='Explore all themes of QloApps'}&nbsp;
            <a href="https://store.webkul.com/Qloapps/responsive.html" class="btn btn-primary btn-lg">{l s='QloApps Themes'}</a>
        </div>
    {/if}
</div>
