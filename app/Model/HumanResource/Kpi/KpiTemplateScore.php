<?php

namespace App\Model\HumanResource\Kpi;

use Illuminate\Database\Eloquent\Model;

class KpiTemplateScore extends Model
{
    protected $connection = 'tenant';

    public function indicator()
    {
        return $this->belongsTo(get_class(new KpiTemplateIndicator()));
    }
}
