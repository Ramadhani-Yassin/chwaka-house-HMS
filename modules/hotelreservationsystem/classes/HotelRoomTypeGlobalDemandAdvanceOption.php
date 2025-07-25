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

class HotelRoomTypeGlobalDemandAdvanceOption extends ObjectModel
{
    public $id_global_demand;
    public $name;
    public $price;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'htl_room_type_global_demand_advance_option',
        'primary' => 'id_option',
        'multilang' => true,
        'fields' => array(
            'id_global_demand' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'price' => array('type' => self::TYPE_FLOAT),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            //lang fields
            'name' => array(
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isCatalogName',
                'required' => true,
                'size' => 128
            ),
    ));

    protected $webserviceParameters = array(
        'objectsNodeName' => 'advance_options',
        'objectNodeName' => 'advance_option',
        'fields' => array(
            'id_global_demand' => array(
                'xlink_resource' => array(
                    'resourceName' => 'extra_demands'
                )
            ),
        ),
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }

    public function delete()
    {
        // delete the global demands from cart
        $objCartBookingData = new HotelCartBookingData();
        if ($cartExtraDemands = $objCartBookingData->getCartExtraDemands()) {
            foreach ($cartExtraDemands as &$demandInfo) {
                if (isset($demandInfo['extra_demands']) && $demandInfo['extra_demands']) {
                    $cartChanged = 0;
                    foreach ($demandInfo['extra_demands'] as $key => $demand) {
                        if ($this->id == $demand['id_option']
                            && $this->id_global_demand == $demand['id_global_demand']
                        ) {
                            $cartChanged = 1;
                            unset($demandInfo['extra_demands'][$key]);
                        }
                    }
                    if ($cartChanged) {
                        if (Validate::isLoadedObject($objCartBooking = new HotelCartBookingData($demandInfo['id']))) {
                            $objCartBooking->extra_demands = json_encode($demandInfo['extra_demands']);
                            $objCartBooking->save();
                        }
                    }
                }
            }
        }
        // delete the info from room type demands table
        $objRoomTypeDemandPrice = new HotelRoomTypeDemandPrice();
        $objRoomTypeDemandPrice->deleteRoomTypeDemandPrices(0, $this->id_global_demand, $this->id);
        return parent::delete();
    }

    public function getGlobalDemandAdvanceOptions($idGlobalDemand, $idLang = null)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'htl_room_type_global_demand_advance_option`
        WHERE `id_global_demand` = '.(int)$idGlobalDemand;
        if ($advOptions = Db::getInstance()->executeS($sql)) {
            foreach ($advOptions as &$option) {
                $option = (array)(new HotelRoomTypeGlobalDemandAdvanceOption($option['id_option'], $idLang));
            }
        }
        return $advOptions;
    }

    public function deleteGlobalDemandAdvanceOptions($idGlobalDemand, $skipIds = array())
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'htl_room_type_global_demand_advance_option`
        WHERE `id_global_demand` = '.(int)$idGlobalDemand;
        if (count($skipIds)) {
            $sql .= ' AND `id_option` NOT IN ('.pSQL(implode(',', $skipIds)).')' ;
        }
        if ($advOptions = Db::getInstance()->executeS($sql)) {
            foreach ($advOptions as &$option) {
                $objOption = new HotelRoomTypeGlobalDemandAdvanceOption($option['id_option']);
                $objOption->delete();
            }
        }
        return true;
    }
}
