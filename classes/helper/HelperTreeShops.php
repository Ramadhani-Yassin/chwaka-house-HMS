<?php
/*
Ramadhani
*/

class HelperTreeShopsCore extends TreeCore
{
    const DEFAULT_TEMPLATE             = 'tree_shops.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_checkbox_shops.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE   = 'tree_node_item_checkbox_shops.tpl';

    private $_lang;
    private $_selected_shops;

    public function __construct($id, $title = null, $lang = null)
    {
        parent::__construct($id);

        if (isset($title)) {
            $this->setTitle($title);
        }

        $this->setLang($lang);
    }

    public function getData()
    {
        if (!isset($this->_data)) {
            $this->setData(Shop::getTree());
        }

        return $this->_data;
    }

    public function setLang($value)
    {
        $this->_lang = $value;
        return $this;
    }

    public function getLang()
    {
        if (!isset($this->_lang)) {
            $this->setLang($this->getContext()->employee->id_lang);
        }

        return $this->_lang;
    }

    public function setSelectedShops($value)
    {
        if (!is_array($value)) {
            throw new PrestaShopException('Selected shops value must be an array');
        }

        $this->_selected_shops = $value;
        return $this;
    }

    public function getSelectedShops()
    {
        if (!isset($this->_selected_shops)) {
            $this->_selected_shops = array();
        }

        return $this->_selected_shops;
    }

    public function render($data = null, $use_default_actions = true, $use_selected_shop = true)
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if ($use_default_actions) {
            $this->setActions(array(
                new TreeToolbarLink(
                    'Collapse All',
                    '#',
                    '$(\'#'.$this->getId().'\').tree(\'collapseAll\'); return false;',
                    'icon-collapse-alt'),
                new TreeToolbarLink(
                    'Expand All',
                    '#',
                    '$(\'#'.$this->getId().'\').tree(\'expandAll\'); return false;',
                    'icon-expand-alt'),
                new TreeToolbarLink(
                    'Check All',
                    '#',
                    'checkAllAssociatedShops($(\'#'.$this->getId().'\')); return false;',
                    'icon-check-sign'),
                new TreeToolbarLink(
                    'Uncheck All',
                    '#',
                    'uncheckAllAssociatedShops($(\'#'.$this->getId().'\')); return false;',
                    'icon-check-empty')
                )
            );
        }

        if ($use_selected_shop) {
            $this->setAttribute('selected_shops', $this->getSelectedShops());
        }

        return parent::render($data);
    }

    public function renderNodes($data = null, $root = false)
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $html = '';

        foreach ($data as $item) {
            if (array_key_exists('shops', $item)
                && !empty($item['shops'])) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(array(
                    'children' => $this->renderNodes($item['shops']),
                    'node'     => $item
                ))->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(array(
                    'node' => $item
                ))->fetch();
            }
        }

        return $html;
    }
}
