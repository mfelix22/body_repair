#!/usr/bin/env php
<?php

use App\Models\WorkOrder;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Work Order Totals ===\n\n";

$workOrders = WorkOrder::orderBy('wo_number')->get();

foreach ($workOrders as $wo) {
    echo "WO {$wo->wo_number}: Rp. " . number_format($wo->grand_total, 0, ',', '.') .
        " (paket: " . number_format($wo->paket_grand_total, 0, ',', '.') . ")\n";
}

echo "\nTotal work orders: " . count($workOrders) . "\n";
