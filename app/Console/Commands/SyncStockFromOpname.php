<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Stock;
use App\Models\StockCostAdjustment;
use App\Models\StockTransaction;
use App\Models\UOM;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyncStockFromOpname extends Command
{
    protected $signature = 'stock:sync-so
                            {file : Path to the stock opname Excel file}
                            {--sheet=Stock with Prices : Worksheet name to read}
                            {--user= : User ID recorded on the adjustment records (defaults to first admin)}
                            {--dry-run : Show what would change without writing}
                            {--force : Apply without the confirmation prompt}';

    protected $description = 'Sync stocks (quantity = "SO fisik", avg_cost = "Avg Price", UOM) from a stock opname Excel file';

    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;

    public function handle(): int
    {
        $file  = $this->argument('file');
        $sheet = (string) $this->option('sheet');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $ws = IOFactory::createReaderForFile($file);
        $ws->setReadDataOnly(true);
        $worksheet = $ws->load($file)->getSheetByName($sheet);

        if (!$worksheet) {
            $this->error("Sheet '{$sheet}' not found in {$file}");
            return self::FAILURE;
        }

        $columns = $this->locateColumns($worksheet);
        if ($columns === null) {
            return self::FAILURE;
        }

        $userId = $this->resolveUserId();
        if ($userId === null && !$dryRun) {
            $this->error('No admin user found to record the adjustments against. Pass --user=<id>.');
            return self::FAILURE;
        }

        $rows    = $this->collectRows($worksheet, $columns);
        $changes = [];
        $skipped = [];

        foreach ($rows as $row) {
            $item = Item::where('code', $row['code'])->with('smallestUom', 'stock')->first();

            if (!$item) {
                $skipped[] = "{$row['code']} — not found in items table";
                continue;
            }

            $uom = null;
            if ($row['uom'] !== '' && $row['uom'] !== $item->smallestUom?->code) {
                $uom = UOM::where('code', $row['uom'])->first();
                if (!$uom) {
                    $skipped[] = "{$row['code']} — UOM '{$row['uom']}' not found in uoms table, row skipped";
                    continue;
                }
            }

            $stock  = $item->stock;
            $oldQty = $stock ? (float) $stock->quantity : 0.0;
            $oldAvg = $stock ? (float) $stock->avg_cost : 0.0;

            $qtyChanged  = abs($row['quantity'] - $oldQty) > 0.005;
            $costChanged = abs($row['avg_cost'] - $oldAvg) > 0.005;

            if (!$qtyChanged && !$costChanged && !$uom) {
                continue;
            }

            $changes[] = [
                'item'        => $item,
                'stock'       => $stock,
                'uom'         => $uom,
                'old_qty'     => $oldQty,
                'new_qty'     => $row['quantity'],
                'old_avg'     => $oldAvg,
                'new_avg'     => $row['avg_cost'],
                'qty_changed' => $qtyChanged,
                'avg_changed' => $costChanged,
            ];
        }

        $this->displayChanges($changes, $skipped);

        if ($dryRun) {
            $this->info('[DRY RUN] No changes written.');
            return self::SUCCESS;
        }

        if (!$changes) {
            $this->info('Nothing to update — database already matches the sheet.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Apply these changes to the database?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes, $userId, $file) {
            foreach ($changes as $change) {
                $this->applyChange($change, $userId, $file);
            }
        });

        $this->info('Done. ' . count($changes) . ' item(s) synced.');
        return self::SUCCESS;
    }

    /**
     * Map expected header names (row 4) to their column letters.
     */
    private function locateColumns($worksheet): ?array
    {
        $map = [];
        foreach (range('A', 'Z') as $letter) {
            $value = $worksheet->getCell($letter . self::HEADER_ROW)->getValue();
            if ($value !== null) {
                $map[trim((string) $value)] = $letter;
            }
        }

        $required = ['Item Code', 'SO fisik', 'UOM', 'Avg Price'];
        $missing  = array_diff($required, array_keys($map));

        if ($missing) {
            $this->error('Missing expected header(s) on row ' . self::HEADER_ROW . ': ' . implode(', ', $missing));
            $this->line('Found headers: ' . implode(' | ', array_keys($map)));
            return null;
        }

        return $map;
    }

    /**
     * Extract item rows: code, target quantity (SO fisik), UOM, target avg cost (Avg Price).
     */
    private function collectRows($worksheet, array $columns): array
    {
        $rows = [];

        for ($r = self::FIRST_DATA_ROW; $r <= $worksheet->getHighestRow(); $r++) {
            $code = trim((string) $worksheet->getCell($columns['Item Code'] . $r)->getValue());
            if ($code === '') {
                continue;
            }

            $rows[] = [
                'code'     => $code,
                'quantity' => round((float) $worksheet->getCell($columns['SO fisik'] . $r)->getValue(), 2),
                'uom'      => trim((string) $worksheet->getCell($columns['UOM'] . $r)->getValue()),
                'avg_cost' => round((float) $worksheet->getCell($columns['Avg Price'] . $r)->getValue(), 2),
            ];
        }

        return $rows;
    }

    private function resolveUserId(): ?int
    {
        if ($id = $this->option('user')) {
            return User::find($id)?->id;
        }

        return User::all()->first(fn ($u) => $u->hasAnyRole(['super_admin', 'admin']))?->id;
    }

    private function displayChanges(array $changes, array $skipped): void
    {
        if (!$changes) {
            $this->info('No differences found.');
        } else {
            $this->info(count($changes) . ' item(s) to update:');
            $this->table(
                ['Code', 'Name', 'Qty →', 'Avg Cost →', 'UOM →'],
                array_map(fn ($c) => [
                    $c['item']->code,
                    mb_strimwidth($c['item']->name, 0, 40, '…'),
                    $c['qty_changed']  ? $c['old_qty'] . ' → ' . $c['new_qty'] : '(same)',
                    $c['avg_changed']  ? $c['old_avg'] . ' → ' . $c['new_avg'] : '(same)',
                    $c['uom']          ? $c['item']->smallestUom?->code . ' → ' . $c['uom']->code : '(same)',
                ], $changes)
            );
        }

        foreach ($skipped as $line) {
            $this->warn('Skipped: ' . $line);
        }
    }

    /**
     * Write one item's sync: quantity adjustment transaction, cost adjustment
     * (+ its ledger row), UOM change, then the stocks row itself — mirroring
     * StockController::adjust and ItemController::adjustCost.
     */
    private function applyChange(array $change, int $userId, string $file): void
    {
        $item  = $change['item'];
        $stock = $change['stock'] ?? Stock::create([
            'item_id'  => $item->id,
            'location' => 'default',
            'quantity' => 0,
            'avg_cost' => 0,
        ]);

        if ($change['qty_changed']) {
            StockTransaction::create([
                'item_id'          => $item->id,
                'transaction_type' => StockTransaction::TYPE_ADJUSTMENT,
                'quantity'         => $change['new_qty'] - $change['old_qty'],
                'unit_cost'        => $change['old_avg'],
                'balance_after'    => $change['new_qty'],
                'avg_cost_after'   => $change['old_avg'],
                'location'         => 'default',
                'reference_type'   => 'STOCK_OPNAME',
                'reference_id'     => null,
                'notes'            => "Stock opname sync ({$file}): qty {$change['old_qty']} → {$change['new_qty']}",
                'created_by'       => $userId,
            ]);
        }

        if ($change['avg_changed']) {
            $adjustment = StockCostAdjustment::create([
                'item_id'      => $item->id,
                'stock_id'     => $stock->id,
                'old_avg_cost' => $change['old_avg'],
                'new_avg_cost' => $change['new_avg'],
                'reason'       => "Stock opname sync ({$file})",
                'adjusted_by'  => $userId,
            ]);

            StockTransaction::create([
                'item_id'          => $item->id,
                'transaction_type' => StockTransaction::TYPE_ADJUSTMENT,
                'quantity'         => 0,
                'unit_cost'        => $change['new_avg'],
                'balance_after'    => $change['new_qty'],
                'avg_cost_after'   => $change['new_avg'],
                'location'         => 'default',
                'reference_type'   => 'COST_ADJUSTMENT',
                'reference_id'     => $adjustment->id,
                'notes'            => "Avg cost adjusted via stock opname: {$change['old_avg']} → {$change['new_avg']}",
                'created_by'       => $userId,
            ]);
        }

        $stock->update([
            'quantity' => $change['new_qty'],
            'avg_cost' => $change['new_avg'],
        ]);

        if ($change['uom']) {
            $item->update(['smallest_uom_id' => $change['uom']->id]);
        }
    }
}
