#!/bin/bash
cd "$(dirname "$0")"
php artisan tinker <<'TINKER'
use App\Models\WorkOrder;
$workOrders = WorkOrder::where('paket_grand_total', '>', 0)->get();
foreach($workOrders as $wo) {
    $wo->grand_total = $wo->paket_grand_total;
    $wo->labor_total = 75000;
    $wo->material_total = 0;
    $wo->save();
    echo "Updated WO: " . $wo->wo_number . "\n";
}
echo "Total updated: " . count($workOrders) . " work orders\n";
TINKER
