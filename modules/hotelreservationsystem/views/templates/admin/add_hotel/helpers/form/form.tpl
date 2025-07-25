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

<div class="panel">
	<div class="panel-heading">
		{if isset($edit)}
			<i class='icon-pencil'></i> {l s='Edit Hotel' mod='hotelreservationsystem'}
		{else}
			<i class='icon-plus'></i> {l s='Add New Hotel' mod='hotelreservationsystem'}
		{/if}
	</div>

	<form id="{$table|escape:'htmlall':'UTF-8'}_form" class="defaultForm {$name_controller|escape:'htmlall':'UTF-8'} form-horizontal" action="{$current|escape:'htmlall':'UTF-8'}&{if !empty($submit_action)}{$submit_action|escape:'htmlall':'UTF-8'}{/if}&token={$token|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data" {if isset($style)}style="{$style|escape:'htmlall':'UTF-8'}"{/if}>
		{if isset($edit)}
			{assign var=hook_arg_id_hotel value=$hotel_info.id}
		{else}
			{assign var=hook_arg_id_hotel value=null}
		{/if}
		{hook h='displayAdminAddHotelFormTop' id_hotel=$hook_arg_id_hotel}
		{if count($languages) > 1}
			<div class="row">
				<div class="col-lg-12">
					<label class="control-label">{l s='Choose Language' mod='hotelreservationsystem'}</label>
					<input type="hidden" name="choosedLangId" id="choosedLangId" value="{$currentLang.id_lang}">
					<button type="button" id="multi_lang_btn" class="btn btn-default dropdown-toggle wk_language_toggle" data-toggle="dropdown">
						{$currentLang.name}
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu wk_language_menu" style="left:14%;top:32px;">
						{foreach from=$languages item=language}
							<li>
								<a href="javascript:void(0)" onclick="showLangField('{$language.name}', {$language.id_lang});">
									{$language.name}
								</a>
							</li>
						{/foreach}
					</ul>
					<p class="help-block">{l s='Change language here to update information in multiple languages.' mod='hotelreservationsystem'}</p>
					<hr>
				</div>
			</div>
		{/if}
		{hook h='displayAdminAddHotelFormTabsBefore' id_hotel=$hook_arg_id_hotel}
		<div class="tabs wk-tabs-panel">
			<ul class="nav nav-tabs">
				<li class="active">
					<a href="#hotel-information" data-toggle="tab">
						<i class="icon-info-sign"></i>
						{l s='Information' mod='hotelreservationsystem'}
					</a>
				</li>
				<li>
					<a href="#hotel-seo" data-toggle="tab">
						<i class="icon-link"></i>
						{l s='Seo' mod='hotelreservationsystem'}
					</a>
				</li>
				<li>
					<a href="#hotel-images" data-toggle="tab">
						<i class="icon-image"></i>
						{l s='Images' mod='hotelreservationsystem'}
					</a>
				</li>
				<li>
					<a href="#hotel-booking-restrictions" data-toggle="tab">
						<i class="icon-lock"></i>
						{l s='Restrictions' mod='hotelreservationsystem'}
					</a>
				</li>
				<li>
					<a href="#hotel-refund-policies" data-toggle="tab">
						<i class="icon-file"></i>
						{l s='Refund Policies' mod='hotelreservationsystem'}
					</a>
				</li>
				<li>
					<a href="#hotel-features" data-toggle="tab">
						<i class="icon-list-alt"></i>
						{l s='Features' mod='hotelreservationsystem'}
					</a>
				</li>
				{hook h='displayAdminAddHotelFormTab' id_hotel=$hook_arg_id_hotel}
			</ul>
			<div class="tab-content panel collapse in">
				<div class="tab-pane active" id="hotel-information">
					{hook h='displayAdminAddHotelFormInformationTabBefore' id_hotel=$hook_arg_id_hotel}

					{if isset($edit)}
						<input id="id-hotel" type="hidden" value="{$hotel_info.id|escape:'html':'UTF-8'}" name="id" />
					{/if}
					<div class="form-group">
						<label class="control-label col-lg-3">
							<span>
								{l s='Enable Hotel' mod='hotelreservationsystem'}
							</span>
						</label>
						<div class="col-lg-9 ">
							<span class="switch prestashop-switch fixed-width-lg">
								<input type="radio" {if isset($edit) && $hotel_info.active==1} checked="checked" {else}checked="checked"{/if} value="1" id="ENABLE_HOTEL_on" name="ENABLE_HOTEL">
								<label for="ENABLE_HOTEL_on">{l s='Yes' mod='hotelreservationsystem'}</label>
								<input {if isset($edit) && $hotel_info.active==0} checked="checked" {/if} type="radio" value="0" id="ENABLE_HOTEL_off" name="ENABLE_HOTEL">
								<label for="ENABLE_HOTEL_off">{l s='No' mod='hotelreservationsystem'}</label>
								<a class="slide-button btn"></a>
							</span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label required" for="hotel_name" >
							{l s='Hotel Name :' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="hotel_name" value="hotel_name_`$language.id_lang`"}
								<input type="text"
								id="name_{$language.id_lang}"
								name="hotel_name_{$language.id_lang}"
								value="{if isset($smarty.post.$hotel_name)}{$smarty.post.$hotel_name|escape:'htmlall':'UTF-8'}{elseif isset($edit)}{$hotel_info.hotel_name[{$language.id_lang}]|escape:'htmlall':'UTF-8'}{/if}"
								class="form-control wk_text_field_all wk_text_field_{$language.id_lang} copy2friendlyUrl"
								maxlength="128"
								{if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if} />
							{/foreach}
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">
							{l s='Short Description :' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="short_desc_name" value="short_description_`$language.id_lang`"}
								<div id="short_desc_div_{$language.id_lang}" class="wk_text_field_all wk_text_field_{$language.id_lang}" {if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if}>
									<textarea
									name="short_description_{$language.id_lang}"
									id="short_description_{$language.id_lang}"
									{if isset($PS_SHORT_DESC_LIMIT) && $PS_SHORT_DESC_LIMIT} maxlength="{$PS_SHORT_DESC_LIMIT|intval}"{/if}
									cols="2" rows="3"
									class="form-control">{if isset($smarty.post.$short_desc_name)}{$smarty.post.$short_desc_name}{elseif isset($edit)}{$hotel_info.short_description[{$language.id_lang}]}{/if}</textarea>
								</div>
							{/foreach}
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">
							{l s='Description :' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="description" value="description_`$language.id_lang`"}
								<div id="description_div_{$language.id_lang}" class="wk_text_field_all wk_text_field_{$language.id_lang}" {if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if}>
									<textarea
									name="description_{$language.id_lang}"
									id="description_{$language.id_lang}"
									cols="2" rows="3"
									class="wk_tinymce form-control">{if isset($smarty.post.$description)}{$smarty.post.$description}{elseif isset($edit)}{$hotel_info.description[{$language.id_lang}]}{/if}</textarea>
								</div>
							{/foreach}
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label required">{l s='Phone :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<input type="text" name="phone" id="phone" value="{if isset($smarty.post.phone)}{$smarty.post.phone}{elseif isset($edit)}{$address_info.phone|escape:'htmlall':'UTF-8'}{/if}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-lg-3 control-label required">{l s='Email :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<div class="input-group">
								<span class="input-group-addon">
									<i class="icon-envelope-o"></i>
								</span>
								<input class="reg_sel_input form-control-static" type="text" name="email" id="hotel_email" value="{if isset($smarty.post.email)}{$smarty.post.email}{elseif isset($edit)}{$hotel_info.email|escape:'htmlall':'UTF-8'}{/if}"/>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label required">{l s='Address :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<textarea name="address" rows="4" cols="35" >{if isset($smarty.post.address)}{$smarty.post.address}{elseif isset($edit)}{$address_info.address1|escape:'htmlall':'UTF-8'}{/if}</textarea>
						</div>
					</div>
					<div class="form-group check_in_div" style="position:relative">
						<label class="col-sm-3 control-label" for="fax">{l s='Fax' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<input autocomplete="off" type="text" class="form-control" id="fax" name="fax" value="{if isset($smarty.post.fax)}{$smarty.post.fax}{elseif isset($edit)}{$hotel_info.fax|escape:'htmlall':'UTF-8'}{/if}" />
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3 required" for="hotel_country">{l s='Rating :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<div style="width: 195px;">
								<select class="form-control" name="hotel_rating" id="hotel_rating" value="">
									<option value="">{l s='No star' mod='hotelreservationsystem'}</option>
									<option value="1" {if (isset($smarty.post.hotel_rating) && $smarty.post.hotel_rating == '1') || isset($edit) && $hotel_info['rating'] == '1'}selected{/if}>*</option>
									<option value="2" {if (isset($smarty.post.hotel_rating) && $smarty.post.hotel_rating == '2') || isset($edit) && $hotel_info['rating'] == '2'}selected{/if}>**</option>
									<option value="3" {if (isset($smarty.post.hotel_rating) && $smarty.post.hotel_rating == '3') || isset($edit) && $hotel_info['rating'] == '3'}selected{/if}>***</option>
									<option value="4" {if (isset($smarty.post.hotel_rating) && $smarty.post.hotel_rating == '4') || isset($edit) && $hotel_info['rating'] == '4'}selected{/if}>****</option>
									<option value="5" {if (isset($smarty.post.hotel_rating) && $smarty.post.hotel_rating == '5') || isset($edit) && $hotel_info['rating'] == '5'}selected{/if}>*****</option>
								</select>
							</div>
						</div>
					</div>
					<div class="form-group check_in_div" style="position:relative">
						<label class="col-sm-3 control-label required" for="check_in_time">
							{l s='Check-in:' mod='hotelreservationsystem'}
						</label>
						<div class="col-sm-2">
							<input autocomplete="off" type="text" class="form-control" id="check_in_time" name="check_in" value="{if isset($smarty.post.check_in)}{$smarty.post.check_in}{elseif isset($edit)}{$hotel_info.check_in|escape:'htmlall':'UTF-8'}{/if}" />
						</div>
					</div>
					<div class="form-group check_out_div" style="position:relative">
						<label class="col-sm-3 control-label required" for="check_out_time">
							{l s='Check-out:' mod='hotelreservationsystem'}
						</label>
						<div class="col-sm-2">
							<input autocomplete="off" type="text" class="form-control" id="check_out_time" name="check_out" value="{if isset($smarty.post.check_out)}{$smarty.post.check_out}{elseif isset($edit)}{$hotel_info.check_out|escape:'htmlall':'UTF-8'}{/if}" />
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3 required" for="hotel_country">{l s='Country :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-9">
							<div style="width: 195px;">
								<select class="form-control" name="hotel_country" id="hotel_country" value="">
									<option value="0" selected="selected">{l s='Choose your Country' mod='hotelreservationsystem'} </option>
									{if $country_var}
										{foreach $country_var as $countr}
											<option value="{$countr['id_country']}" {if isset($smarty.post.hotel_country) && $smarty.post.hotel_country}{if $smarty.post.hotel_country == $countr['id_country']}selected{/if}{elseif isset($edit) && $address_info['id_country'] == $countr['id_country']}selected{/if}>{$countr['name']}</option>
										{/foreach}
									{/if}
								</select>
							</div>
							<div class="help-block"><em>** {l s='If Hotel\'s country is not present in country list then import that country from' mod='hotelreservationsystem'}<a href="{$link->getAdminLink('AdminLocalization')|escape:'html':'UTF-8'}"> <strong>{l s='Localization' mod='hotelreservationsystem'}</strong> </a>{l s='tab.' mod='hotelreservationsystem'}</em></div>
						</div>
					</div>
					<div class="form-group hotel_state_dv" {if !$state_var}style="display:none;"{/if}>
						<label class="control-label col-sm-3 required hotel_state_lbl" for="hotel_state" {if !$state_var}style="display:none;"{/if}>{l s='State :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<div style="width: 195px;">
								<select class="form-control" name="hotel_state" id="hotel_state">
								{if is_array($state_var) && count($state_var)}
									{foreach $state_var as $state}
										<option value="{$state['id_state']}" {if (isset($smarty.post.hotel_state) && $smarty.post.hotel_state == $state['id_state']) || (isset($edit) && $address_info['id_state'] == $state['id_state'])}selected{/if}> {$state['name']}</option>
									{/foreach}
								{/if}
								</select>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3 required" for="hotel_city">{l s='City :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<input class="form-control" type="" data-validate="" id="hotel_city" name="hotel_city" value="{if isset($smarty.post.hotel_city)}{$smarty.post.hotel_city}{elseif isset($edit)}{$address_info.city|escape:'htmlall':'UTF-8'}{/if}" />
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3 required" for="hotel_postal_code">{l s='Zip Code :' mod='hotelreservationsystem'}</label>
						<div class="col-sm-6">
							<input class="form-control" type="" data-validate="" id="hotel_postal_code" name="hotel_postal_code" value="{if isset($smarty.post.hotel_postal_code)}{$smarty.post.hotel_postal_code}{elseif isset($edit)}{$address_info.postcode|escape:'htmlall':'UTF-8'}{/if}" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">
							{l s='Hotel Policies :' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="policies" value="policies_`$language.id_lang`"}
								<div id="policies_div_{$language.id_lang}" class="wk_text_field_all wk_text_field_{$language.id_lang}" {if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if}>
									<textarea
									name="policies_{$language.id_lang}"
									id="policies_{$language.id_lang}"
									cols="2" rows="3"
									class="wk_tinymce form-control">{if isset($smarty.post.$policies)}{$smarty.post.$policies}{elseif isset($edit)}{$hotel_info.policies[{$language.id_lang}]}{/if}</textarea>
								</div>
							{/foreach}
						</div>
					</div>
					{if isset($enabledDisplayMap) && $enabledDisplayMap}
						<div class="form-group">
							<label class="col-sm-3 control-label">{l s='Map:' mod='hotelreservationsystem'}</label>
							<div class="col-sm-6" id="googleMapContainer">
								<input type="hidden" id="loclatitude" name="loclatitude" value="{if isset($edit)}{$hotel_info.latitude|escape:'htmlall':'UTF-8'}{/if}" />
								<input type="hidden" id="loclongitude" name="loclongitude" value="{if isset($edit)}{$hotel_info.longitude|escape:'htmlall':'UTF-8'}{/if}" />
								<input type="hidden" id="locformatedAddr" name="locformatedAddr" value="{if isset($edit)}{$hotel_info.map_formated_address}{/if}" />
								<input type="hidden" id="googleInputField" name="googleInputField" value="{if isset($edit)}{$hotel_info.map_input_text}{/if}" />
								<div id="pac-input" class="controls" type="text"></div>
								<div id="map"></div>
							</div>
						</div>
					{/if}

					{hook h='displayAdminAddHotelFormInformationTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				<div class="tab-pane" id="hotel-seo">
					{hook h='displayAdminAddHotelFormSeoTabBefore' id_hotel=$hook_arg_id_hotel}
					<div class="form-group">
						<label class="col-sm-3 control-label required" for="link_rewrite" >
							{l s='Friendly URL :' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="link_rewrite" value="link_rewrite_`$language.id_lang`"}
								<input type="text"
								id="link_rewrite_{$language.id_lang}"
								name="link_rewrite_{$language.id_lang}"
								value="{if isset($smarty.post.$link_rewrite)}{$smarty.post.$link_rewrite|escape:'htmlall':'UTF-8'}{elseif isset($edit)}{$link_rewrite_info[{$language.id_lang}]|escape:'htmlall':'UTF-8'}{/if}"
								class="form-control wk_text_field_all wk_text_field_{$language.id_lang}"
								maxlength="128"
								{if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if} />
							{/foreach}
						</div>
						<div class="col-lg-2">
							<button type="button" class="btn btn-default" onmousedown="updateFriendlyURLByName();"><i class="icon-random"></i> {l s='Generate'}</button>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" for="meta_title" >
							{l s='Meta title:' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
						{foreach from=$languages item=language}
							{assign var="meta_title" value="meta_title_`$language.id_lang`"}
								<input type="text"
								id="meta_title_{$language.id_lang}"
								name="meta_title_{$language.id_lang}"
								value="{if isset($smarty.post.$meta_title)}{$smarty.post.$meta_title|escape:'htmlall':'UTF-8'}{elseif isset($edit)}{$meta_title_info[$language.id_lang]|escape:'htmlall':'UTF-8'}{/if}""
								class="form-control wk_text_field_all wk_text_field_{$language.id_lang}"
								maxlength="128"
								{if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if} />
							{/foreach}
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" for="meta_title" >
							{l s='Meta description:' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								{assign var="meta_description" value="meta_description_`$language.id_lang`"}
								<input type="text"
								id="meta_description_{$language.id_lang}"
								name="meta_description_{$language.id_lang}"
								value="{if isset($smarty.post.$meta_description)}{$smarty.post.$meta_description|escape:'htmlall':'UTF-8'}{elseif isset($edit)}{$meta_description_info[{$language.id_lang}]|escape:'htmlall':'UTF-8'}{/if}"
								class="form-control wk_text_field_all wk_text_field_{$language.id_lang}"
								maxlength="225"
								{if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if} />
							{/foreach}
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" for="meta_title" >
							{l s='Meta keywords:' mod='hotelreservationsystem'}
							{include file="../../../_partials/htl-form-fields-flag.tpl"}
						</label>
						<div class="col-lg-6">
							{foreach from=$languages item=language}
								<div class="wk_text_field_all wk_text_field_{$language.id_lang}" {if $currentLang.id_lang != $language.id_lang}style="display:none;"{/if} >
									{assign var="meta_keywords" value="meta_keywords_`$language.id_lang`"}
									<script type="text/javascript">
										$().ready(function () {
											var input_id = 'meta_keywords_{$language.id_lang}';
											$("#"+input_id).tagify({ delimiters: [13,44], addTagPrompt: "{l s='Add tag' mod='hotelreservationsystem' js=1}"});
											$("#htl_branch_info_form").submit( function() {
												$('#'+input_id).val($('#'+input_id).tagify('serialize'));
											});
										});
									</script>
									<input type="text"
									id="meta_keywords_{$language.id_lang}"
									name="meta_keywords_{$language.id_lang}"
									value="{if isset($smarty.post.$meta_keywords)}{$smarty.post.$meta_keywords|escape:'htmlall':'UTF-8'}{elseif isset($edit)}{$meta_keywords_info[{$language.id_lang}]|escape:'htmlall':'UTF-8'}{/if}"
									class="form-control tagify"
									maxlength="225">
								</div>
							{/foreach}
						</div>
					</div>
					{hook h='displayAdminAddHotelFormSeoTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				<div class="tab-pane" id="hotel-images">
					{hook h='displayAdminAddHotelFormImagesTabBefore' id_hotel=$hook_arg_id_hotel}

					{if isset($hotel_info.id) && $hotel_info.id}
						<div class="form-group row">
							<label for="hotel_images" class="col-sm-3 control-label padding-top-0">
								{l s='Upload images' mod='hotelreservationsystem'}&nbsp;:&nbsp;&nbsp;
							</label>
							<div class="col-sm-5">
								<input class="form-control-static" type="file" accept="image/gif, image/jpg, image/jpeg, image/png" id="hotel_images" name="hotel_images[]" multiple>
							</div>
						</div>
						<hr>
						{* Image table *}
						<h4><i class="icon-image"></i> <span>{l s='Hotel Images' mod='hotelreservationsystem'}</span></h4>
						<div class="row">
							<div class="col-sm-12">
								{include file="../../_partials/htl-images-list.tpl"}
							</div>
						</div>
					{else}
						<div class="alert alert-warning">
							{l s='Please save hotel information before saving hotel images.' mod='hotelreservationsystem'}
						</div>
					{/if}

					{hook h='displayAdminAddHotelFormImagesTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				<div class="tab-pane" id="hotel-booking-restrictions">
					{hook h='displayAdminAddHotelFormRestrictionsTabBefore' id_hotel=$hook_arg_id_hotel}

					{if isset($hotel_info.id) && $hotel_info.id}
						<div class="form-group">
							<label class="control-label col-lg-3">
								<span>{l s='Use Global Maximum checkout offset:' mod='hotelreservationsystem'}</span>
							</label>
							<div class="col-lg-6">
								<span class="switch prestashop-switch fixed-width-lg">
									<input type="radio" {if isset($smarty.post.enable_use_global_max_checkout_offset)} {if $smarty.post.enable_use_global_max_checkout_offset} checked="checked"{/if} {elseif isset($edit) && isset($order_restrict_date_info.use_global_max_checkout_offset) && $order_restrict_date_info.use_global_max_checkout_offset} checked="checked" {else if isset($order_restrict_date_info) && !$order_restrict_date_info} checked="checked" {/if} value="1" id="enable_use_global_max_checkout_offset_on" name="enable_use_global_max_checkout_offset">
									<label for="enable_use_global_max_checkout_offset_on">{l s='Yes' mod='hotelreservationsystem'}</label>
									<input type="radio" {if isset($smarty.post.enable_use_global_max_checkout_offset)} {if !$smarty.post.enable_use_global_max_checkout_offset} checked="checked"{/if} {elseif isset($edit) && isset($order_restrict_date_info.use_global_max_checkout_offset) && !$order_restrict_date_info.use_global_max_checkout_offset} checked="checked" {/if} value="0" id="enable_use_global_max_checkout_offset_off" name="enable_use_global_max_checkout_offset">
									<label for="enable_use_global_max_checkout_offset_off">{l s='No' mod='hotelreservationsystem'}</label>
									<a class="slide-button btn"></a>
								</span>
								<div class="help-block">{l s='Global Maximum checkout offset:' mod='hotelreservationsystem'} {$PS_MAX_CHECKOUT_OFFSET}</div>
							</div>
						</div>
						<div class="form-group" {if isset($smarty.post.enable_use_global_max_checkout_offset)} {if !$smarty.post.enable_use_global_max_checkout_offset} style="display:block;" {else} style="display:none;" {/if} {elseif isset($order_restrict_date_info.use_global_max_checkout_offset) && !$order_restrict_date_info.use_global_max_checkout_offset} style="display:block;" {else} style="display:none;" {/if}>
							<label class="control-label col-sm-3 required" for="max_checkout_offset">{l s='Maximum checkout offset :' mod='hotelreservationsystem'}</label>
							<div class="col-sm-2">
								<input type="text" class="form-control" id="max_checkout_offset" name="max_checkout_offset" value="{if isset($smarty.post.max_checkout_offset)}{$smarty.post.max_checkout_offset}{elseif isset($order_restrict_date_info.max_checkout_offset)}{$order_restrict_date_info.max_checkout_offset|escape:'htmlall':'UTF-8'}{/if}" />
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-lg-3">
								<span>{l s='Use Global minimum booking offset :' mod='hotelreservationsystem'}</span>
							</label>
							<div class="col-lg-6">
								<span class="switch prestashop-switch fixed-width-lg">
									<input type="radio" {if isset($smarty.post.enable_use_global_min_booking_offset)} {if $smarty.post.enable_use_global_min_booking_offset} checked="checked" {/if} {elseif isset($edit) && isset($order_restrict_date_info.use_global_min_booking_offset) && $order_restrict_date_info.use_global_min_booking_offset} checked="checked" {else if isset($order_restrict_date_info) && !$order_restrict_date_info} checked="checked" {/if} value="1" id="enable_use_global_min_booking_offset_on" name="enable_use_global_min_booking_offset">
									<label for="enable_use_global_min_booking_offset_on">{l s='Yes' mod='hotelreservationsystem'}</label>
									<input type="radio" {if isset($smarty.post.enable_use_global_min_booking_offset)} {if !$smarty.post.enable_use_global_min_booking_offset} checked="checked" {/if} {elseif isset($edit) && isset($order_restrict_date_info.use_global_min_booking_offset) && !$order_restrict_date_info.use_global_min_booking_offset} checked="checked" {/if} value="0" id="enable_use_global_min_booking_offset_off" name="enable_use_global_min_booking_offset">
									<label for="enable_use_global_min_booking_offset_off">{l s='No' mod='hotelreservationsystem'}</label>
									<a class="slide-button btn"></a>
								</span>
								<div class="help-block">{l s='Global minimum booking offset :' mod='hotelreservationsystem'} {$PS_MIN_BOOKING_OFFSET}</div>
							</div>
						</div>
						<div class="form-group" {if isset($smarty.post.enable_use_global_min_booking_offset)} {if !$smarty.post.enable_use_global_min_booking_offset} style="display:block;" {else} style="display:none;" {/if} {else if isset($edit) && isset($order_restrict_date_info.use_global_min_booking_offset) && !$order_restrict_date_info.use_global_min_booking_offset} style="display:block;" {else} style="display:none;" {/if}>
							<label class="control-label col-sm-3 required" for="min_booking_offset">{l s='Minimum booking offset :' mod='hotelreservationsystem'}</label>
							<div class="col-sm-2">
								<input type="text" class="form-control" id="min_booking_offset" name="min_booking_offset" value="{if isset($smarty.post.min_booking_offset)}{$smarty.post.min_booking_offset|escape:'html':'UTF-8'}{elseif isset($edit) && isset($order_restrict_date_info.min_booking_offset)}{$order_restrict_date_info.min_booking_offset|escape:'htmlall':'UTF-8'}{/if}" />
							</div>
							<div class="col-lg-9 col-lg-offset-3">
								<div class="help-block">{l s='Set to 0 to disable this feature.' mod='hotelreservationsystem'}</div>
							</div>
						</div>
					{else}
						<div class="alert alert-warning">
							{l s='Please save the hotel information before saving the hotel booking restrictions.' mod='hotelreservationsystem'}
						</div>
					{/if}

					{hook h='displayAdminAddHotelFormRestrictionsTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				<div class="tab-pane" id="hotel-refund-policies">
					{hook h='displayAdminAddHotelFormRefundPoliciesTabBefore' id_hotel=$hook_arg_id_hotel}

					{if isset($hotel_info.id) && $hotel_info.id}
						{if isset($WK_ORDER_REFUND_ALLOWED) && !$WK_ORDER_REFUND_ALLOWED}
							<div class="alert alert-info">
								{l s='To enable hotel-wise refunds, activate order refunds in' mod='hotelreservationsystem'} <a href="{$link->getAdminLink('AdminOrderRefundRules')|escape:'html':'UTF-8'}" target="_blank">{l s='Manage Order Refund Rules' mod='hotelreservationsystem'}</a>
							</div>
						{/if}
						<div class="form-group">
							<label for="active_refund" class="control-label col-sm-5">
								<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title='{l s='Enable, if you want to enable refund for this hotel.' mod='hotelreservationsystem'}'>{l s='Enable refund' mod='hotelreservationsystem'}</span>
							</label>
							<div class="col-sm-7">
								<span class="switch prestashop-switch fixed-width-lg">
									<input type="radio" value="1" id="active_refund_on" name="active_refund" {if isset($WK_ORDER_REFUND_ALLOWED) && !$WK_ORDER_REFUND_ALLOWED} disabled="disabled" {elseif isset($smarty.post.active_refund)}{if $smarty.post.active_refund}checked="checked"{/if}{elseif isset($hotel_info) && $hotel_info.active_refund}checked="checked"{/if}>
									<label for="active_refund_on">{l s='Yes' mod='hotelreservationsystem'}</label>
									<input type="radio" value="0" id="active_refund_off" name="active_refund" {if isset($WK_ORDER_REFUND_ALLOWED) && !$WK_ORDER_REFUND_ALLOWED} disabled="disabled" checked="checked" {elseif isset($smarty.post.active_refund)}{if !$smarty.post.active_refund}checked="checked"{/if}{elseif !isset($hotel_info)}checked="checked"{elseif isset($hotel_info) && !$hotel_info.active_refund}checked="checked"{/if}>
									<label for="active_refund_off">{l s='No' mod='hotelreservationsystem'}</label>
									<a class="slide-button btn"></a>
								</span>
							</div>
						</div>
						<div class="refund_rules_container" {if isset($smarty.post.active_refund)}{if !$smarty.post.active_refund}style="display:none;"{/if}{elseif !isset($hotel_info.active_refund) || !$hotel_info.active_refund}style="display:none;"{/if}>
							{if isset($allRefundRules) && $allRefundRules}
								<hr>
								<div class="table-responsive">
									<table class="table wk-htl-datatable">
										<thead>
											<tr>
												<th></th>
												<th></th>
												<th>{l s='Id' mod='hotelreservationsystem'}</th>
												<th>{l s='Name' mod='hotelreservationsystem'}</th>
												<th>{l s='Full payment charges' mod='hotelreservationsystem'}</th>
												<th>{l s='Advance payment charges' mod='hotelreservationsystem'}</th>
												<th>{l s='Days before check-in' mod='hotelreservationsystem'}</th>
											</tr>
										</thead>
										<tbody id="slides">
											{foreach from=$allRefundRules item=refundRule}
												<tr id="slides_{$refundRule.id_refund_rule}">
													<td>
														<i class="icon-arrows "></i>
													</td>
													<td>
														<p class="checkbox">
															<label><input name="htl_refund_rules[]" type="checkbox" class="checkbox" value="{$refundRule.id_refund_rule}" {if isset($hotelRefundRules) && ($refundRule.id_refund_rule|in_array:$hotelRefundRules)}checked{/if} /></label>
														</p>
													</td>
													<td>
														{$refundRule.id_refund_rule|escape:'html':'UTF-8'} <a target="blank" href="{$link->getAdminLink('AdminOrderRefundRules')|escape:'html':'UTF-8'}&amp;id_refund_rule={$refundRule.id_refund_rule|escape:'html':'UTF-8'}&amp;updatehtl_order_refund_rules"><i class="icon-external-link-sign"></i></a>
													</td>
													<td>
														{$refundRule['name']|escape:'html':'UTF-8'}
													</td>
													<td>
														{if $refundRule['payment_type'] == $WK_REFUND_RULE_PAYMENT_TYPE_PERCENTAGE}
															{$refundRule['deduction_value_full_pay']|escape:'html':'UTF-8'} %
														{else}
															{displayPrice price=$refundRule['deduction_value_full_pay'] currency=$defaultCurrency}
														{/if}
													</td>
													<td>
														{if $refundRule['payment_type'] == $WK_REFUND_RULE_PAYMENT_TYPE_PERCENTAGE}
															{$refundRule['deduction_value_adv_pay']|escape:'html':'UTF-8'} %
														{else}
															{displayPrice price=$refundRule['deduction_value_adv_pay'] currency=$defaultCurrency}
														{/if}
													</td>
													<td>{$refundRule['days']|escape:'html':'UTF-8'} {l s='days' mod='hotelreservationsystem'}</td>
												</tr>
											{/foreach}
										</tbody>
									</table>
								</div>
							{else}
								<div class="alert alert-warning">
									{l s='No refund rules are created yet.' mod='hotelreservationsystem'} {l s='You can create refund rules by visiting ' mod='hotelreservationsystem'} <a target="_blank" href="{$link->getAdminLink('AdminOrderRefundRules')}">{l s='create refund rules' mod='hotelreservationsystem'}</a>
								</div>
							{/if}
						</div>
					{else}
						<div class="alert alert-warning">
							{l s='Please save hotel information before saving refund policy options.' mod='hotelreservationsystem'}
						</div>
					{/if}

					{hook h='displayAdminAddHotelFormRefundPoliciesTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				<div class="tab-pane" id="hotel-features">
					{hook h='displayAdminAddHotelFormFeaturesTabBefore' id_hotel=$hook_arg_id_hotel}
					{if isset($hotel_feature_tree)}
						<div class="form-group">
							<label for="hotel_feature" class="control-label col-sm-3">
								<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title='{l s='Select features for this hotel.' mod='hotelreservationsystem'}'>{l s='Select feature' mod='hotelreservationsystem'}</span>
							</label>
							<div class="col-xs-7 hotel_features_tree">
								{$hotel_feature_tree}
							</div>
						</div>
					{elseif isset($hotel_info.id) && $hotel_info.id}
						<div class="alert alert-warning">
							{l s='No features created yet.' mod='hotelreservationsystem'} {l s='You can create features by visiting ' mod='hotelreservationsystem'} <a target="_blank" href="{$link->getAdminLink('AdminHotelFeatures')}">{l s='manage hotel features.' mod='hotelreservationsystem'}</a>
						</div>
					{else}
						<div class="alert alert-warning">
							{l s='Please save hotel information before assigning hotel features.' mod='hotelreservationsystem'}
						</div>
					{/if}

					{hook h='displayAdminAddHotelFormFeaturesTabAfter' id_hotel=$hook_arg_id_hotel}
				</div>
				{hook h='displayAdminAddHotelFormTabContent' id_hotel=$hook_arg_id_hotel}
			</div>
		</div>

		{hook h='displayAdminAddHotelFormBottom' id_hotel=$hook_arg_id_hotel}

		<div class="panel-footer">
			<a href="{$link->getAdminLink('AdminAddHotel')|escape:'html':'UTF-8'}" class="btn btn-default">
				<i class="process-icon-cancel"></i>{l s='Cancel' mod='hotelreservationsystem'}
			</a>
			<button type="submit" name="submitAddhotel_branch_info" class="btn btn-default pull-right">
				<i class="process-icon-save"></i> {l s='Save' mod='hotelreservationsystem'}
			</button>
			<button type="submit" name="submitAdd{$table|escape:'html':'UTF-8'}AndStay" class="btn btn-default pull-right">
				<i class="process-icon-save"></i> {l s='Save and stay' mod='hotelreservationsystem'}
			</button>
		</div>
	</form>
</div>

{strip}
	{addJsDef adminHotelCtrlUrl = $link->getAdminlink('AdminAddHotel')}
		{addJsDefL name=imgUploadSuccessMsg}{l s='Image Successfully Uploaded' js=1 mod='hotelreservationsystem'}{/addJsDefL}
	{addJsDefL name=imgUploadErrorMsg}{l s='Something went wrong while uploading images. Please try again later !!' js=1 mod='hotelreservationsystem'}{/addJsDefL}

	{addJsDefL name=coverImgSuccessMsg}{l s='Cover image changed successfully' js=1 mod='hotelreservationsystem'}{/addJsDefL}
	{addJsDefL name=coverImgErrorMsg}{l s='Error while changing cover image' js=1 mod='hotelreservationsystem'}{/addJsDefL}

	{addJsDefL name=deleteImgSuccessMsg}{l s='Image deleted successfully' js=1 mod='hotelreservationsystem'}{/addJsDefL}
	{addJsDefL name=deleteImgErrorMsg}{l s='Something went wrong while deleteing image. Please try again later !!' js=1 mod='hotelreservationsystem'}{/addJsDefL}

	{addJsDef enabledDisplayMap = $enabledDisplayMap}
	{addJsDef defaultCountry = $defaultCountry}
	{addJsDef statebycountryurl = $link->getAdminLink('AdminAddHotel')}
	{addJsDefL name=htlImgDeleteSuccessMsg}{l s='Image removed successfully.' js=1 mod='hotelreservationsystem'}{/addJsDefL}
	{addJsDefL name=htlImgDeleteErrMsg}{l s='Some error occurred while deleting hotel image.' js=1 mod='hotelreservationsystem'}{/addJsDefL}
{/strip}

{block name=script}
<script type="text/javascript">
	var id_language = {$defaultFormLanguage|intval};
	allowEmployeeFormLang = {$allowEmployeeFormLang|intval};
	var ps_force_friendly_product = false;

	// for tiny mce setup
	var iso = "{$iso|escape:'htmlall':'UTF-8'}";
	var pathCSS = "{$smarty.const._THEME_CSS_DIR_|escape:'htmlall':'UTF-8'}";
	var ad = "{$ad|escape:'htmlall':'UTF-8'}";
	$(document).ready(function(){
		{block name="autoload_tinyMCE"}
			tinySetup({
				editor_selector :"wk_tinymce",
				width : 700
			});
		{/block}

	});
</script>
{/block}
