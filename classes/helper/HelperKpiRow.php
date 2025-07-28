<?php
/*
Ramadhani
*/

class HelperKpiRowCore extends Helper
{
    public $base_folder = 'helpers/kpi/';
    public $base_tpl = 'row.tpl';

    public $kpis = array();
    public $refresh = true;

    public function generate()
    {
        $this->tpl = $this->createTemplate($this->base_tpl);

        // set visiblity for each KPI
        $countVisible = 0;
        $cookieKeyPrefix = 'kpi_visibility_'.$this->context->controller->className.'_';
        foreach ($this->kpis as &$kpi) {
            $cookieKey = $cookieKeyPrefix.$kpi->id;
            if (isset($this->context->cookie->$cookieKey)) {
                $kpi->visible = (bool) $this->context->cookie->$cookieKey;
            } else {
                $kpi->visible = true;
            }

            $countVisible = $kpi->visible ? ++$countVisible : $countVisible;
        }

        $cookieKeyView = 'kpi_wrapping_'.$this->context->controller->className;

        $this->tpl->assign('kpis', $this->kpis);
        $this->tpl->assign('refresh', $this->refresh);
        $this->tpl->assign('no_wrapping', (int) $this->context->cookie->$cookieKeyView);
        $this->tpl->assign('count_visible', $countVisible);

        return $this->tpl->fetch();
    }
}
