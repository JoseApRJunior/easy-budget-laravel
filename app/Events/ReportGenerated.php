<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Report;

class ReportGenerated
{
    public function __construct(public Report $report) {}
}
