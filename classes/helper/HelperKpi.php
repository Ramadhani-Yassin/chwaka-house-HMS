<?php
/*
Ramadhani
*/

class HelperKpiCore extends Helper
{
    public $base_folder = 'helpers/kpi/';
    public $base_tpl = 'kpi.tpl';

    public $id;
    public $icon;
    public $chart;
    public $color;
    public $title;
    public $subtitle;
    public $value;
    public $data;
    public $source;
    public $id_hotels = false;
    public $exclude_id_hotels = array();
    public $href;
    public $target;
    public $tooltip;
    public $visible;
    public $refresh = true;

    public function generate()
    {
        $this->tpl = $this->createTemplate($this->base_tpl);

        $this->tpl->assign(array(
            'id' => $this->id,
            'icon' => $this->icon,
            'chart' => (bool)$this->chart,
            'color' => $this->color,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'value' => $this->value,
            'data' => $this->data,
            'source' => $this->source,
            'id_hotels' => $this->id_hotels,
            'exclude_id_hotels' => $this->exclude_id_hotels,
            'href' => $this->href,
            'target' => $this->target,
            'tooltip' => $this->tooltip,
            'visible' => $this->visible,
        ));

        return $this->tpl->fetch();
    }
}
