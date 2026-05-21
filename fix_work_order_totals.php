<?php

use App\Models\WorkOrder;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

// Update work orders
$workOrders = WorkOrder::where('paket_grand_total', '>', 0)->get();
$count = 0;

foreach ($workOrders as $wo) {
    $wo->grand_total = $wo->paket_grand_total;
    $wo->labor_total = 75000;
    $wo->material_total = 0;
    $wo->save();
    $count++;
    echo "Updated WO #{$wo->wo_number}: Rp. " . number_format($wo->grand_total, 0, ',', '.') . "\n";
}

echo "\nTotal updated: {$count} work orders\n";
