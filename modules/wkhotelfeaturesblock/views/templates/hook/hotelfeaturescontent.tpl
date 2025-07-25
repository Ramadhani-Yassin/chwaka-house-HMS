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

{block name='hotel_features_block'}
    {if isset($hotelAmenities) && $hotelAmenities}
        <div id="hotelAmenitiesBlock" class="row home_block_container">
            <div class="col-xs-12 col-sm-12 home_amenities_wrapper">
                {if $HOTEL_AMENITIES_HEADING && $HOTEL_AMENITIES_DESCRIPTION}
                    <div class="row home_block_desc_wrapper">
                        <div class="col-md-offset-1 col-md-10 col-lg-offset-2 col-lg-8">
                            {block name='hotel_features_block_heading'}
                                <p class="home_block_heading">{$HOTEL_AMENITIES_HEADING|escape:'htmlall':'UTF-8'}</p>
                            {/block}
                            {block name='hotel_features_block_description'}
                                <p class="home_block_description">{$HOTEL_AMENITIES_DESCRIPTION|escape:'htmlall':'UTF-8'}</p>
                            {/block}
                            <hr class="home_block_desc_line"/>
                        </div>
                    </div>
                {/if}
                {block name='hotel_features_images'}
                    <div class="homeAmenitiesBlock home_block_content">
                        {assign var='amenityPosition' value=0}
                        {assign var='amenityIteration' value=0}
                        {foreach from=$hotelAmenities item=amenity name=amenityBlock}

                            {if $smarty.foreach.amenityBlock.iteration%2 != 0}
                                <div class="row margin-lr-0">
                                {if $amenityPosition}
                                    {assign var='amenityPosition' value=0}
                                {else}
                                    {assign var='amenityPosition' value=1}
                                {/if}
                            {/if}
                                    <div class="col-md-6 padding-lr-0 hidden-xs hidden-sm">
                                        <div class="row margin-lr-0 amenity_content">
                                            {if $amenityPosition}
                                                <div class="col-md-6 padding-lr-0">
                                                    <div class="amenity_img_primary">

                                                        <div class="amenity_img_secondary" style="background-image: url('{$link->getMediaLink("`$module_dir|escape:'htmlall':'UTF-8'`views/img/hotels_features_img/`$amenity.id_features_block|escape:'htmlall':'UTF-8'`.jpg")}')">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 padding-lr-0 amenity_desc_cont">
                                                    <div class="amenity_desc_primary">
                                                        <div class="amenity_desc_secondary">
                                                            <p class="amenity_heading">{$amenity['feature_title']|escape:'htmlall':'UTF-8'}</p>
                                                            <p class="amenity_description">{$amenity['feature_description']|escape:'htmlall':'UTF-8'}</p>
                                                            <hr class="amenity_desc_hr" />
                                                        </div>
                                                    </div>
                                                </div>
                                            {else}
                                                <div class="col-md-6 padding-lr-0 amenity_desc_cont">
                                                    <div class="amenity_desc_primary">
                                                        <div class="amenity_desc_secondary">
                                                            <p class="amenity_heading">{$amenity['feature_title']|escape:'htmlall':'UTF-8'}</p>
                                                            <p class="amenity_description">{$amenity['feature_description']|escape:'htmlall':'UTF-8'}</p>
                                                            <hr class="amenity_desc_hr" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 padding-lr-0">
                                                    <div class="amenity_img_primary">
                                                        <div class="amenity_img_secondary" style="background-image: url('{$link->getMediaLink("`$module_dir|escape:'htmlall':'UTF-8'`views/img/hotels_features_img/`$amenity.id_features_block|escape:'htmlall':'UTF-8'`.jpg")}')">
                                                        </div>
                                                    </div>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                    <div class="col-sm-12 padding-lr-0 visible-sm">
                                        <div class="row margin-lr-0 amenity_content">
                                            {if $smarty.foreach.amenityBlock.iteration%2 != 0}
                                                <div class="col-sm-6 padding-lr-0">
                                                    <div class="amenity_img_primary">
                                                        <div class="amenity_img_secondary" style="background-image: url('{$link->getMediaLink("`$module_dir|escape:'htmlall':'UTF-8'`views/img/hotels_features_img/`$amenity.id_features_block|escape:'htmlall':'UTF-8'`.jpg")}')">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 padding-lr-0 amenity_desc_cont">
                                                    <div class="amenity_desc_primary">
                                                        <div class="amenity_desc_secondary">
                                                            <p class="amenity_heading">{$amenity['feature_title']|escape:'htmlall':'UTF-8'}</p>
                                                            <p class="amenity_description">{$amenity['feature_description']|escape:'htmlall':'UTF-8'}</p>
                                                            <hr class="amenity_desc_hr" />
                                                        </div>
                                                    </div>
                                                </div>
                                            {else}
                                                <div class="col-sm-6 padding-lr-0 amenity_desc_cont">
                                                    <div class="amenity_desc_primary">
                                                        <div class="amenity_desc_secondary">
                                                            <p class="amenity_heading">{$amenity['feature_title']|escape:'htmlall':'UTF-8'}</p>
                                                            <p class="amenity_description">{$amenity['feature_description']|escape:'htmlall':'UTF-8'}</p>
                                                            <hr class="amenity_desc_hr" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 padding-lr-0">
                                                    <div class="amenity_img_primary">
                                                        <div class="amenity_img_secondary" style="background-image: url('{$link->getMediaLink("`$module_dir|escape:'htmlall':'UTF-8'`views/img/hotels_features_img/`$amenity.id_features_block|escape:'htmlall':'UTF-8'`.jpg")}')">
                                                        </div>
                                                    </div>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 padding-lr-0 visible-xs">
                                        <div class="row margin-lr-0 amenity_content">
                                            <div class="col-xs-12 padding-lr-0">
                                                <div class="amenity_img_primary">
                                                    <div class="amenity_img_secondary" style="background-image: url('{$link->getMediaLink("`$module_dir|escape:'htmlall':'UTF-8'`views/img/hotels_features_img/`$amenity.id_features_block|escape:'htmlall':'UTF-8'`.jpg")}')">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 padding-lr-0 amenity_desc_cont">
                                                <div class="amenity_desc_primary">
                                                    <div class="amenity_desc_secondary">
                                                        <p class="amenity_heading">{$amenity['feature_title']|escape:'htmlall':'UTF-8'}</p>
                                                        <p class="amenity_description">{$amenity['feature_description']|escape:'htmlall':'UTF-8'}</p>
                                                        <hr class="amenity_desc_hr" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            {if $smarty.foreach.amenityBlock.iteration%2 == 0}
                                </div>
                            {/if}
                            {assign var='amenityIteration' value=$smarty.foreach.amenityBlock.iteration}
                        {/foreach}
                        {if $amenityIteration%2}
                            </div>
                        {/if}
                    </div>
                {/block}
            </div>
            <hr class="home_block_seperator"/>
        </div>
    {/if}
{/block}
