<?php
/*
Ramadhani
*/

class BestSalesControllerCore extends FrontController
{
    public $php_self = 'best-sales';

    public function initContent()
    {
        if (Configuration::get('PS_DISPLAY_BEST_SELLERS')) {
            parent::initContent();

            $this->productSort();
            $nb_products = (int)ProductSale::getNbSales();
            $this->pagination($nb_products);

            if (!Tools::getValue('orderby')) {
                $this->orderBy = 'sales';
            }

            $products = ProductSale::getBestSales($this->context->language->id, $this->p - 1, $this->n, $this->orderBy, $this->orderWay);
            $this->addColorsToProductList($products);

            $this->context->smarty->assign(array(
                'products' => $products,
                'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                'nbProducts' => $nb_products,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
                'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM')
            ));

            $this->setTemplate(_PS_THEME_DIR_.'best-sales.tpl');
        } else {
            Tools::redirect('index.php?controller=404');
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
    }
}
