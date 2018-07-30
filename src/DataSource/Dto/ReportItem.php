<?php

namespace App\DataSource\Dto;

class ReportItem
{
    public $id;
    public $title;
    public $count;
    public $sum;
    public $tax;

    public function __construct(array $props)
    {
        $this->id       = $props['id'];
        $this->title    = $props['title'];
        $this->count    = (int) $props['count'];
        $this->sum      = rtrim($props['sum'], 0);
        $this->tax      = rtrim($props['tax'], 0);
    }
}