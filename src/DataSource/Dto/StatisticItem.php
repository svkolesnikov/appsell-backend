<?php

namespace App\DataSource\Dto;

class StatisticItem
{
    public $id;
    public $title;
    public $count;
    public $sum;
    public $reason;

    public function __construct(array $props)
    {
        $this->id       = $props['id'];
        $this->title    = $props['title'];
        $this->count    = $props['count'];
        $this->sum      = $props['sum'];
        $this->reason   = $props['reason'];
    }
}