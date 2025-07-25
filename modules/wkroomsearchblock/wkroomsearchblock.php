<?php
/**
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
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
include_once dirname(__FILE__).'/classes/WkRoomSearchHelper.php';

class WkRoomSearchBlock extends Module
{
    public function __construct()
    {
        $this->name = 'wkroomsearchblock';
        $this->tab = 'front_office_features';
        $this->version = '1.1.5';
        $this->author = 'Webkul';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('QloApps Room Search Panels');
        $this->description = $this->l('Room search blocks on different pages to search rooms as per user travel parameters.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function hookActionFrontControllerSetMedia()
    {
        $controller = Tools::getValue('controller');
        // apply assets globally for all pages of search panel
        if ('category' == $controller || 'index' == $controller || 'product' == $controller ) {
            $objHotelBranchInformation = new HotelBranchInformation();
            $hotelBranchesInfo = $objHotelBranchInformation->hotelBranchesInfo(0, 1);
            if (is_array($hotelBranchesInfo) && count($hotelBranchesInfo)) {
                $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/jquery.chosen.js');
                $this->context->controller->addCSS($this->_path.'/views/css/chosen.css');

                $this->context->controller->addCSS($this->_path.'/views/css/wk-global-search.css');
                $this->context->controller->addJS($this->_path.'/views/js/wk-room-search-block.js');

                $isOccupancyWiseSearch = false;
                if (Configuration::get('PS_FRONT_SEARCH_TYPE') == HotelBookingDetail::SEARCH_TYPE_OWS) {
                    $isOccupancyWiseSearch = true;
                }
                Media::addJsDef(
                    array (
                        'autocomplete_search_url' => $this->context->link->getModuleLink(
                            'wkroomsearchblock',
                            'autocompletesearch'
                        ),
                        'no_results_found_cond' => $this->l('No results found for this search', false, true),
                        'hotel_name_cond' => $this->l('Please select a hotel name', false, true),
                        'check_in_time_cond' => $this->l('Please enter Check In time', false, true),
                        'check_out_time_cond' => $this->l('Please enter Check Out time', false, true),
                        'less_checkin_date' => $this->l('Check In date can not be before current date.', false, true),
                        'more_checkout_date' => $this->l('Check Out date must be greater than Check In date.', false, true),
                        'hotel_location_txt' => $this->l('Hotel Location', false, true),
                        'select_htl_txt' => $this->l('Select Hotel', false, true),
                        'select_age_txt' => $this->l('Select age', false, true),
                        'under_1_age' => $this->l('Under 1', false, true),
                        'room_txt' => $this->l('Room', false, true),
                        'rooms_txt' => $this->l('Rooms', false, true),
                        'remove_txt' => $this->l('Remove', false, true),
                        'adult_txt' => $this->l('Adult', false, true),
                        'adults_txt' => $this->l('Adults', false, true),
                        'child_txt' => $this->l('Child', false, true),
                        'children_txt' => $this->l('Children', false, true),
                        'below_txt' => $this->l('Below', false, true),
                        'years_txt' => $this->l('years', false, true),
                        'all_children_txt' => $this->l('All Children', false, true),
                        'invalid_occupancy_txt' => $this->l('Invalid occupancy(adults/children) found.', false, true),
                        'hotel_name_has_search' => count($hotelBranchesInfo) >= Configuration::get('WK_HOTEL_NAME_SEARCH_THRESHOLD'),
                        'search_auto_focus_next_field' => (bool) Configuration::get('WK_SEARCH_AUTO_FOCUS_NEXT_FIELD'),
                        'is_occupancy_wise_search' => $isOccupancyWiseSearch,
                    )
                );
            }
        }

        // apply assets as per pages
        if ('category' == $controller) {
            $this->context->controller->addCSS($this->_path.'views/css/wk-category-search.css');
        }
        if ('index' == $controller) {
            $this->context->controller->addCSS($this->_path.'views/css/wk-landing-page-search.css');
        }
        if ('product' == $controller) {
            $this->context->controller->addCSS($this->_path.'views/css/wk-roomtype-search.css');
            $this->context->controller->addJS($this->_path.'views/js/wk-roomtype-search.js');
        }
    }


    // search panel block on the landing page
    public function hookDisplayAfterHookTop($params)
    {
        if ('index' == Tools::getValue('controller')) {
            $objSearchHelper = new WkRoomSearchHelper();
            $objSearchHelper->assignSearchPanelVariables();
            return $this->display(__FILE__, 'landingPageSearch.tpl');
        } elseif ('product' == Tools::getValue('controller')) {
            $objSearchHelper = new WkRoomSearchHelper();
            $objSearchHelper->assignSearchPanelVariables();
            return $this->display(__FILE__, 'roomTypePageSearch.tpl');
        }
    }

    // In the xs sceen the booking button on the landing page
    public function hookDisplayAfterHeaderHotelDesc()
    {
        $objHotelInfo = new HotelBranchInformation();
        if ($objHotelInfo->hotelBranchesInfo(0, 1)) {
            return $this->display(__FILE__, 'landingPageXsBtn.tpl');
        }
    }

    // search panel block on the category page on left block
    public function hookDisplayLeftColumn()
    {
        if ('category' == Tools::getValue('controller')) {
            $idCategory = Tools::getValue('id_category');
            if (Validate::isLoadedObject($objCategory = new Category((int) $idCategory))
                && HotelBranchInformation::getHotelIdByIdCategory($idCategory)
            ) {
                if ($objCategory->hasParent(Configuration::get('PS_LOCATIONS_CATEGORY'))) {
                    $objSearchHelper = new WkRoomSearchHelper();
                    $objSearchHelper->assignSearchPanelVariables();
                    return $this->display(__FILE__, 'categoryPageSearch.tpl');
                }
            }
        }
    }

    public function hookDisplayHeader()
    {
        // handle room search submit from all pages search panels
        if (Tools::isSubmit('search_room_submit')) {
            $this->roomSearchProcess();
        }
    }

    public function roomSearchProcess()
    {
        $urlData = array();
        $hotelCategoryId = Tools::getValue('hotel_cat_id');
        // change dates format to acceptable format
        if ($checkIn = Tools::getValue('check_in_time')) {
            if (!Validate::isDateFormat($checkIn)) {
                $checkIn = date('Y-m-d');
            } else {
                $checkIn = date('Y-m-d', strtotime($checkIn));
            }
            $urlData['date_from'] = $checkIn;
        }

        if ($checkOut = Tools::getValue('check_out_time')) {
            if (!Validate::isDateFormat($checkOut)) {
                $checkOut = date('Y-m-d', strtotime('+1 day', strtotime($checkIn)));
            } else {
                $checkOut = date('Y-m-d', strtotime($checkOut));
            }
            $urlData['date_to'] = $checkOut;
        }

        $maxOrderDate = Tools::getValue('max_order_date');
        $maxOrderDate = date('Y-m-d', strtotime($maxOrderDate));

        if (Configuration::get('PS_FRONT_ROOM_UNIT_SELECTION_TYPE') == HotelBookingDetail::PS_ROOM_UNIT_SELECTION_TYPE_OCCUPANCY) {
            if ($occupancy = Tools::getValue('occupancy')) {
                $urlData['occupancy'] = $occupancy;
            }
        }

        if ($locationCategoryId = Tools::getValue('location_category_id')) {
            $urlData['location'] = $locationCategoryId;
        }

        $objSearchHelper = new WkRoomSearchHelper();
        $this->context->controller->errors = array_merge(
            $this->context->controller->errors,
            $objSearchHelper->validateSearchFields()
        );
        // id there is no validation error the proceed to redirect on search result page
        if (!count($this->context->controller->errors)) {
            if (Configuration::get('PS_REWRITING_SETTINGS')) {
                $redirectLink = $this->context->link->getCategoryLink(
                    new Category($hotelCategoryId, $this->context->language->id),
                    null,
                    $this->context->language->id
                ).'?'.http_build_query($urlData);
            } else {
                $redirectLink = $this->context->link->getCategoryLink(
                    new Category($hotelCategoryId, $this->context->language->id),
                    null,
                    $this->context->language->id
                ).'&'.http_build_query($urlData);
            }
            Tools::redirect($redirectLink);
        }
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerModuleHooks()
        ) {
            return false;
        }
        return true;
    }

    private function registerModuleHooks()
    {
        return $this->registerHook(
            array(
                'displayHeader',
                'displayLeftColumn',
                'actionFrontControllerSetMedia',
                'displayAfterHookTop',
                'displayAfterHeaderHotelDesc',
                'displayAddModuleSettingLink',
            )
        );
    }
}
