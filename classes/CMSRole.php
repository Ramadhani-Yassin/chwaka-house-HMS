<?php
/*
Ramadhani
*/


class CMSRoleCore extends ObjectModel
{
    /** @var string name */
    public $name;
    /** @var integer id_cms */
    public $id_cms;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'cms_role',
        'primary' => 'id_cms_role',
        'fields' => array(
            'name'        =>    array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50),
            'id_cms'    =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
        ),
    );

    public static function getRepositoryClassName()
    {
        return 'Core_Business_CMS_CMSRoleRepository';
    }
}
