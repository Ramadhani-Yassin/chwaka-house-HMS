<?php
/*
Ramadhani
*/

class ZoneCore extends ObjectModel
{
    /** @var string Name */
    public $name;

    /** @var bool Zone status */
    public $active = true;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'zone',
        'primary' => 'id_zone',
        'fields' => array(
            'name' =>    array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    protected $webserviceParameters = array();

    /**
     * Get all available geographical zones
     *
     * @param bool $active
     * @return array Zones
     */
    public static function getZones($active = false)
    {
        $cache_id = 'Zone::getZones_'.(bool)$active;
        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT *
				FROM `'._DB_PREFIX_.'zone`
				'.($active ? 'WHERE active = 1' : '').'
				ORDER BY `name` ASC
			');
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }

    /**
     * Get a zone ID from its default language name
     *
     * @param string $name
     * @return int id_zone
     */
    public static function getIdByName($name)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_zone`
			FROM `'._DB_PREFIX_.'zone`
			WHERE `name` = \''.pSQL($name).'\'
		');
    }

    /**
    * Delete a zone
    *
    * @return bool Deletion result
    */
    public function delete()
    {
        if (parent::delete()) {
            // Delete regarding delivery preferences
            $result = Db::getInstance()->delete('carrier_zone', 'id_zone = '.(int)$this->id);
            $result &= Db::getInstance()->delete('delivery', 'id_zone = '.(int)$this->id);

            // Update Country & state zone with 0
            $result &= Db::getInstance()->update('country', array('id_zone' => 0), 'id_zone = '.(int)$this->id);
            $result &= Db::getInstance()->update('state', array('id_zone' => 0), 'id_zone = '.(int)$this->id);

            return $result;
        }

        return false;
    }
}
