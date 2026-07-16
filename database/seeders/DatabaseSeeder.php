<?php

namespace Database\Seeders;

use App\Models\UOM;
use App\Models\UOMConversion;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $seedUserId = User::where('role', 'super_admin')->value('id');

        // Create UOMs (Units of Measurement)
        $kalengGram = UOM::create(['name' => 'Kaleng/Gram', 'code' => 'KG_KL']);
        $pcsSet     = UOM::create(['name' => 'PCS SET',     'code' => 'PCS_SET']);
        $piece      = UOM::create(['name' => 'Pcs',         'code' => 'PCS']);
        $lembar     = UOM::create(['name' => 'Lembar',      'code' => 'LBR']);
        $millilitre = UOM::create(['name' => 'Ml',          'code' => 'ML']);
        $gram       = UOM::create(['name' => 'Gram',        'code' => 'GRM']);
        $kilogram   = UOM::create(['name' => 'Kg',          'code' => 'KG']);
        $botol      = UOM::create(['name' => 'Botol',       'code' => 'BTL']);
        $botolGram  = UOM::create(['name' => 'Botol/Gram',  'code' => 'BTL_GRM']);
        $roll       = UOM::create(['name' => 'Roll',        'code' => 'ROLL']);
        $set        = UOM::create(['name' => 'Set',         'code' => 'SET']);
        $galon      = UOM::create(['name' => 'Galon',       'code' => 'GAL']);
        $pack       = UOM::create(['name' => 'Pack',        'code' => 'PCK']);
        $litre      = UOM::create(['name' => 'Litre',       'code' => 'LTR']);

        // Helper to derive conversion factor from composite UOMs to the base unit (Gram)
        // 1L of liquid = 1000g; "1kg" bottle = 1000g
        $getCompositeFactor = function ($uom, string $name): ?float {
            if ($uom->code === 'BTL_GRM') {
                if (preg_match('/(\d+)\s*kg/i', $name, $m)) {
                    return (float) $m[1] * 1000;
                }
                return 1000;
            }
            if ($uom->code === 'KG_KL') {
                if (preg_match('/\((\d+),(\d+)L\)/', $name, $m)) {
                    $liters = (float) ($m[1] . '.' . $m[2]);
                    return $liters * 1000;
                }
                if (preg_match('/\((\d+)L\)/', $name, $m)) {
                    return (float) $m[1] * 1000;
                }
                return 1000;
            }
            return null;
        };

        // Helper closure to create item + item_uom + stock + opening transaction
        $make = function (string $code, string $name, $uom, float $price, float $stock, string $category = 'Supplies', string $itemType = 'C') use (&$piece, &$gram, $kalengGram, $botolGram, $getCompositeFactor, $seedUserId) {
            $compositeFactor = $getCompositeFactor($uom, $name);
            $smallestUom = $compositeFactor ? $gram : $uom;

            $item = Item::create([
                'code'           => $code,
                'name'           => $name,
                'item_type'      => $itemType,
                'category'       => $category,
                'smallest_uom_id' => $smallestUom->id,
                'reorder_level'  => 0,
                'is_active'      => true,
            ]);

            // Smallest UOM ItemUOM (price converted to per-gram for composite UOMs)
            ItemUOM::create([
                'item_id'              => $item->id,
                'uom_id'               => $smallestUom->id,
                'conversion_to_smallest' => 1,
                'price'                => $compositeFactor ? round($price / $compositeFactor, 2) : $price,
                'is_default'           => $compositeFactor ? false : true,
            ]);

            if ($compositeFactor) {
                // Composite/container UOM ItemUOM (original image price, per can/bottle)
                ItemUOM::create([
                    'item_id'              => $item->id,
                    'uom_id'               => $uom->id,
                    'conversion_to_smallest' => $compositeFactor,
                    'price'                => $price,
                    'is_default'           => true,
                ]);

                // Global UOM conversion record (default 1000g; per-item accuracy lives in ItemUOM)
                UOMConversion::firstOrCreate(
                    ['from_uom_id' => $uom->id, 'to_uom_id' => $gram->id],
                    ['conversion_factor' => 1000]
                );
            }

            $perSmallestPrice = $compositeFactor ? round($price / $compositeFactor, 2) : $price;
            $createdStock = Stock::create([
                'item_id'  => $item->id,
                'location' => 'default',
                'quantity' => $stock,
                'avg_cost' => $perSmallestPrice,
            ]);

            // Record the opening balance so the transaction history is complete
            if ($seedUserId) {
                StockTransaction::create([
                    'item_id'          => $item->id,
                    'transaction_type' => StockTransaction::TYPE_OPENING,
                    'quantity'         => $stock,
                    'unit_cost'        => $perSmallestPrice,
                    'balance_after'    => $stock,
                    'location'         => 'default',
                    'reference_type'   => 'OPENING',
                    'reference_id'     => null,
                    'notes'            => 'Opening balance from database seeder',
                    'created_by'       => $seedUserId,
                ]);
            }

            return $item;
        };

        // ── Image 1: Paint & Chemical items (AXT codes) ────────────────────────
        $make('AXT-060',  'AXT FLIP CONTROLLER (0,9L)',                      $kalengGram, 400000,  1008.33, 'Paint', 'A');
        $make('AXT-101',  'AXT-101 TRANSPARENT WHITE (0,9L)',                $kalengGram, 815200,  1181.67, 'Paint', 'A');
        $make('AXT-200',  'AXT-200 ULTRA DEEP BLACK (0,9L)',                 $kalengGram, 443000,  0,       'Paint', 'A');
        $make('AXT-203',  'AXT-203 BLUE BLACK (0,9L)',                       $kalengGram, 443000,  1144.06, 'Paint', 'A');
        $make('AXT-207',  'AXT 207 BLACK TONER (0,9L)',                      $kalengGram, 445400,  1209.51, 'Paint', 'A');
        $make('AXT-300',  'AXT-300 VIOLET BLUE (BLUE SHADE) (0,9L)',         $kalengGram, 907200,  1207.76, 'Paint', 'A');
        $make('AXT-302',  'AXT-302 TRANSPARENT BLUE (0,9L)',                 $kalengGram, 475600,  1166.6,  'Paint', 'A');
        $make('AXT-304',  'AXT-304 GREEN BLUE (0,9L)',                       $kalengGram, 561400,  1171.44, 'Paint', 'A');
        $make('AXT-306',  'AXT-306 LAKE BLUE (0,9L)',                        $kalengGram, 1066200, 1207.35, 'Paint', 'A');
        $make('AXT-307',  'AXT 307 BLUE (0,9L)',                             $kalengGram, 687200,  1198.08, 'Paint', 'A');
        $make('AXT-400',  'AXT-400 GREEN (0,9L)',                            $kalengGram, 505000,  1211.6,  'Paint', 'A');
        $make('AXT-401',  'AXT-401 YELLOW GREEN (0,9L)',                     $kalengGram, 672400,  1201.22, 'Paint', 'A');
        $make('AXT-402',  'AXT-402 GOLDEN GREEN (0,9L)',                     $kalengGram, 672400,  1201.22, 'Paint', 'A');
        $make('AXT-501',  'AXT-501 TRANSOXIDE RED (0,9L)',                   $kalengGram, 618200,  1161.79, 'Paint', 'A');
        $make('AXT-502',  'AXT-502 BRICK RED (0,9L)',                        $kalengGram, 475600,  1255.34, 'Paint', 'A');
        $make('AXT-503',  'AXT-503 BRIGHT RED (0,9L)',                       $kalengGram, 605800,  1212.7,  'Paint', 'A');
        $make('AXT-505',  'AXT-505 VIOLET RED (0,9L)',                       $kalengGram, 628200,  1202.85, 'Paint', 'A');
        $make('AXT-506',  'AXT-506 PEACH RED (0,9L)',                        $kalengGram, 598400,  1207.88, 'Paint', 'A');
        $make('AXT-508',  'AXT-508 MAROON RED (0,9L)',                       $kalengGram, 690000,  1190.04, 'Paint', 'A');
        $make('AXT-509',  'AXT-509 TRANSPARENT RED (0,9L)',                  $kalengGram, 903800,  1193.29, 'Paint', 'A');
        $make('AXT-512',  'AXT 512 ORANGE RED (0,9L)',                       $kalengGram, 830000,  1225.4,  'Paint', 'A');
        $make('AXT-513',  'AXT-513 BRILLIANT RED (0,9L)',                    $kalengGram, 775800,  1198.87, 'Paint', 'A');
        $make('AXT-515',  'AXT-515 LIGHT VIOLET RED (0,9L)',                 $kalengGram, 840800,  1178.96, 'Paint', 'A');
        $make('AXT-516',  'AXT-516 ROSE RED (0,9L)',                         $kalengGram, 761000,  1197.12, 'Paint', 'A');
        $make('AXT-518',  'AXT-518 EXTRA RED (0,9L)',                        $kalengGram, 1017000, 1073.89, 'Paint', 'A');
        $make('AXT-600',  'AXT-600 MUD YELLOW (0,9L)',                       $kalengGram, 460800,  1100.26, 'Paint', 'A');
        $make('AXT-601',  'AXT-601 TRANSOXIDE YELLOW (0,9L)',                $kalengGram, 618200,  1163.25, 'Paint', 'A');
        $make('AXT-605',  'AXT-605 LEMON YELLOW (0,9L)',                     $kalengGram, 844600,  1187.62, 'Paint', 'A');
        $make('AXT-607',  'AXT-607 LIGHT YELLOW (0,9L)',                     $kalengGram, 716600,  1238.22, 'Paint', 'A');
        $make('AXT-608',  'AXT-608 ORANGE YELLO (0,9L)',                     $kalengGram, 628200,  1222.35, 'Paint', 'A');
        $make('AXT-609',  'AXT-609 TRANSPARENT ORANGE YELLOW (0,9L)',        $kalengGram, 687000,  1196.1,  'Paint', 'A');
        $make('AXT-700',  'AXT 700 VIOLET (0,9L)',                           $kalengGram, 579000,  1168.95, 'Paint', 'A');
        $make('AXT-810',  'AXT-810 EXTRA FINE SILVER (0,9L)',                $kalengGram, 465800,  1124.34, 'Paint', 'A');
        $make('AXT-811',  'AXT-811 FINE WHITE SILVER (0,9L)',                $kalengGram, 512200,  440,     'Paint', 'A');
        $make('AXT-830',  'AXT-830 COARSE SILVER (0,9L)',                    $kalengGram, 600600,  1188.61, 'Paint', 'A');
        $make('AXT-831',  'AXT-831 EXTRA COARSE SILVER (0,9L)',              $kalengGram, 517000,  1175.07, 'Paint', 'A');
        $make('AXT-841',  'AXT 841 FINE BRIGHT SILVER (0,9L)',               $kalengGram, 517000,  1181.9,  'Paint', 'A');
        $make('AXT-843',  'AXT-843 MEDIUM BRIGHT SILVER (0,9L)',             $kalengGram, 531800,  1175.46, 'Paint', 'A');
        $make('AXT-847',  'AXT-847 ULTRA BRIGHT SILVER (0,9L)',              $kalengGram, 789800,  1185.8,  'Paint', 'A');
        $make('AXT-910',  'AXT-910 WHITE PEARL (0,9L)',                      $kalengGram, 466800,  892.19,  'Paint', 'A');
        $make('AXT-911',  'AXT-911 FINE WHITE PEARL (0,9L)',                 $kalengGram, 512200,  1199.75, 'Paint', 'A');
        $make('AXT-930',  'AXT-930 BLUE PEARL (0,9L)',                       $kalengGram, 483400,  1192.66, 'Paint', 'A');
        $make('AXT-940',  'AXT 940 GREEN PEARL (0,9L)',                      $kalengGram, 531800,  1207.66, 'Paint', 'A');
        $make('AXT-950',  'AXT-950 RED PEARL (0,9L)',                        $kalengGram, 479000,  1199.28, 'Paint', 'A');
        $make('AXT-960',  'AXT-960 YELLOW PEARL (0,9L)',                     $kalengGram, 546600,  1226,    'Paint', 'A');
        $make('AXT-961',  'AXT-961 GOLDEN PEARL (0,9L)',                     $kalengGram, 531600,  1186.5,  'Paint', 'A');
        $make('AXT-963',  'AXT-963 GOLDEN YELLOW PEARL (0,9L)',              $kalengGram, 551600,  1212.62, 'Paint', 'A');
        $make('AXT-965',  'AXT 965 COPPER PEARL (0,9L)',                     $kalengGram, 541800,  1206.64, 'Paint', 'A');
        $make('AXT-970',  'AXT-970 VIOLET PEARL (0,9L)',                   $kalengGram, 465800,  0,       'Paint', 'A');
        $make('AXT-971',  'AXT-971 VIOLET RED PEARL (0,9L)',                 $kalengGram, 488000,  0,       'Paint', 'A');
        $make('AXT-010',  'AXT-010 ECO CRYSTAL WHITE PEARL (0,9L)',          $kalengGram, 566400,  644.56,  'Paint', 'A');
        $make('AXT-030',  'AXT-030 ECO CRYSTAL BLUE PEARL (0,9L)',           $kalengGram, 625400,  1005.61, 'Paint', 'A');
        $make('AXT-061',  'AXT 061 ECO CRYSTAL GOLDEN PEARL (0,9L)',         $kalengGram, 615400,  1192.28, 'Paint', 'A');
        $make('AXT-040',  'AXT 040 ECO CRYSTAL GREEN PEARL (0,45L)',         $kalengGram, 937000,  750.24,  'Paint', 'A');
        $make('AXT-050',  'AXT-050 ECO CRYSTAL RED PEARL (0,45L)',           $kalengGram, 0,       0,       'Paint', 'A');
        $make('AXT-9991', 'AXT-9991 GREEN RED PEARL (0,45L)',                 $kalengGram, 0,       0,       'Paint', 'A');
        $make('AXT-9992', 'AXT-9992 ULTRA FINE WHITE PEARL (0,45L)',         $kalengGram, 0,       0,       'Paint', 'A');
        $make('AXT-850',  'AXT-850 MEDIUM GOLDEN SILVER (0,45L)',            $kalengGram, 752400,  748.74,  'Paint', 'A');
        $make('AXT-852',  'AXT 852 MEDIUM ORANGE SILVER (0,45L)',            $kalengGram, 1008400, 753.72,  'Paint', 'A');
        $make('AXT-100',  'AXT-100 WHITE (3,5L)',                            $kalengGram, 1449400, 0,       'Paint', 'A');
        $make('AXT-206',  'AXT-206 EXTRA BLACK (3,5L)',                      $kalengGram, 1798800, 0,       'Paint', 'A');
        $make('AXT-3100', 'AXT-3100 1K CLEAR BINDER (3,5L)',                 $kalengGram, 1143200, 0,       'Paint', 'A');
        $make('AXT-3520', 'AXT-3520 1K BINDER (3,5L)',                       $kalengGram, 1143200, 0,       'Paint', 'A');
        $make('AXT-840',  'AXT 840 EXTRA FINE BRIGHT SILVER (3,5L)',         $kalengGram, 1692400, 0,       'Paint', 'A');
        $make('AXT-820',  'AXT-820 MEDIUM SILVER (3,5L)',                    $kalengGram, 1643400, 0,       'Paint', 'A');
        $make('AXT-812',  'AXT-812 MEDIUM FINE WHITE SILVER (3,5L)',         $kalengGram, 1611000, 0,       'Paint', 'A');
        $make('AXT-821',  'AXT-821 MEDIUM WHITE SILVER (3,5L)',              $kalengGram, 1627200, 0,       'Paint', 'A');
        $make('HS-CLEAR-COAT',  'HS CLEAR COAT 360 (Include Hardiner)',      $pcsSet,     385000,  0,       'Paint', 'B');
        $make('MS-CLEAR-COAT',  'MS CLEAR COAT 280 (Include Hardiner)',      $pcsSet,     210000,  6170.84, 'Paint', 'B');
        $make('AXT-PU2K-PRIMER-GRE', 'AXT PU 2K PRIMER GREY (Include Hardiner)', $pcsSet, 220000, 5658.84, 'Paint', 'B');
        $make('AXT-PU2K-PRIMER-WHI', 'AXT PU 2K PRIMER WHITE',              $pcsSet,     220000,  0,       'Paint', 'B');
        $make('AXT-PU2K-PRIMER-BLA', 'AXT PU 2K PRIMER BLACK',              $pcsSet,     220000,  0,       'Paint', 'B');
        $make('AXT-EP2K-PRIMER-GRE', 'AXT EP 2K PRIMER GREY (Include Hardiner)', $pcsSet, 220000, 14746.85, 'Paint', 'B');
        $make('AXT-PU-URETH',        'AXT PU URETHANE REDUCER (20L)',         $galon,      2170000, 0,       'Paint', 'B');
        $make('AXT-PU-FAST',         'AXT PU FAST REDUCER (20L)',            $galon,      2170000, 0,       'Paint', 'B');
        $make('AXT-PU-SILICON',      'AXT PU SILICON DEGREASER (1L)',        $piece,      110000,  8579.61, 'Paint', 'B');
        $make('AXT-1K-PR',           'AXT 1K PR PP CLEAR (1L)',              $pcsSet,     166000,  0,       'Paint', 'B');
        $make('HARDENER-HS-CC-360',   'HARDENER HS CLEAR COAT 360',           $pcsSet,     0,       0,       'Paint', 'B');
        $make('HARDENER-MS-CC-280',   'HARDENER MS CLEAR COAT 280',           $pcsSet,     0,       1741.84, 'Paint', 'B');
        $make('HARDENER-PU2K-GRE',    'HARDENER AXT PU 2K PRIMER GREY',       $pcsSet,     0,       988.73,  'Paint', 'B');
        $make('HARDENER-PU2K-WHI',    'HARDENER AXT PU 2K PRIMER WHITE',      $pcsSet,     0,       0,       'Paint', 'B');
        $make('HARDENER-PU2K-BLA',    'HARDENER AXT PU 2K PRIMER BLACK',      $pcsSet,     0,       0,       'Paint', 'B');
        $make('HARDENER-EP2K-GRE',    'HARDENER AXT EP 2K PRIMER GREY',       $pcsSet,     0,       2646.3,  'Paint', 'B');

        // ── Image 2: General supplies ─────────────────────────────────────────
        $make('SUP-001',  'Masker cat & dempul',            $piece,     250000,  0,      'Supplies', 'C');
        $make('SUP-002',  'Kabel Ties 200x2,5-htm',        $piece,     15000,   93,     'Supplies', 'C');
        $make('SUP-003',  'Kabel Ties 300x3,6-htm',        $pack,      30000,   26,     'Supplies', 'C');
        $make('SUP-004',  'Kabel Ties Klip Kecil',          $piece,     0,       5,      'Supplies', 'C');
        $make('SUP-005',  'Kabel Ties Klip Besar',          $piece,     0,       5,      'Supplies', 'C');
        $make('SUP-006',  'Thinner PU',                     $millilitre, 900000,  21000,  'Supplies', 'C');
        $make('SUP-007',  'Thinner ND (cuci)',               $millilitre, 450000,  6400,   'Supplies', 'C');
        $make('SUP-008',  'Semir Ban / tyre polish',         $botol,     60000,   4,      'Supplies', 'C');
        $make('SUP-009',  'Masking tape 24 (kecil)',         $piece,     7620,    88,     'Supplies', 'C');
        $make('SUP-010',  'Masking tape 48 (besar)',         $piece,     10300,   0,      'Supplies', 'C');
        $make('SUP-011',  'Autoglow ( Harga Include Hardliner)', $millilitre, 90000, 5583.45, 'Supplies', 'C');
        $make('SUP-012',  'Hardener Autoglow',               $millilitre, 0,       174.25, 'Supplies', 'C');
        $make('SUP-013',  'A.LF Hi-Grade @4Kg',              $gram,      190000,  12000,  'Supplies', 'C');
        $make('SUP-014',  'Borax',                           $gram,      45000,   122.53, 'Supplies', 'C');
        $make('SUP-015',  'Degreaser Autoglow',              $millilitre, 79570,   5,      'Supplies', 'C');
        $make('SUP-016',  'Kertas roti lembaran / kg',       $lembar,    20000,   58,     'Supplies', 'C');
        $make('SUP-017',  'Kain Majun min.50Kg',             $kilogram,  18000,   0,      'Supplies', 'C');
        $make('SUP-018',  'Amplas Nikken 100',               $lembar,    6850,    7.25,   'Supplies', 'C');
        $make('SUP-019',  'Amplas Nikken 120',               $lembar,    6550,    9,      'Supplies', 'C');
        $make('SUP-020',  'Amplas Nikken 240',               $lembar,    6550,    8,      'Supplies', 'C');
        $make('SUP-021',  'Amplas Nikken 360',               $lembar,    6550,    10,     'Supplies', 'C');
        $make('SUP-022',  'Amplas Nikken 400',               $lembar,    6550,    5,      'Supplies', 'C');
        $make('SUP-023',  'Amplas Nikken 600',               $lembar,    6550,    9.5,    'Supplies', 'C');
        $make('SUP-024',  'Amplas Nikken 1000',              $lembar,    7850,    5,      'Supplies', 'C');
        $make('SUP-025',  'Amplas Nikken 1500',              $lembar,    7850,    8.5,    'Supplies', 'C');
        $make('SUP-026',  'Amplas Nikken 2000',              $lembar,    7850,    5,      'Supplies', 'C');
        $make('SUP-027',  'Amplas Crocodile 120',            $lembar,    8000,    10,     'Supplies', 'C');
        $make('SUP-028',  'Plat List Sample Warna',          $piece,     0,       0,      'Supplies', 'C');
        $make('SUP-029',  'Scotbrathe Abu-abu',              $piece,     16682,   18,     'Supplies', 'C');
        $make('SUP-030',  'Plastik Zip Lock Besar',          $piece,     0,       0,      'Supplies', 'C');
        $make('SUP-031',  'Lap Kimtech Biru',                $piece,     93000,   130,    'Supplies', 'C');
        $make('SUP-032',  'Combi Wanda',                     $piece,     41300,   2,      'Supplies', 'C');
        $make('SUP-033',  'Scrube dempul made in German',    $piece,     0,       1,      'Supplies', 'C');
        $make('SUP-034',  'Amplas sending P120',             $lembar,    6000,    0,      'Supplies', 'C');
        $make('SUP-035',  'Power Glue',                      $piece,     8000,    0,      'Supplies', 'C');
        $make('SUP-036',  'Lem Aibon 300grm',                $piece,     58000,   1,      'Supplies', 'C');
        $make('SUP-037',  'Double Tape 3M Besar',            $piece,     57000,   2,      'Supplies', 'C');
        $make('SUP-038',  'Double Tape 3M Kecil',            $piece,     27000,   1,      'Supplies', 'C');
        $make('SUP-039',  'Dextone / lem besi',              $piece,     22000,   5,      'Supplies', 'C');
        $make('SUP-040',  'Pad Poles 2 inch',                $piece,     35000,   1,      'Supplies', 'C');
        $make('SUP-041',  'Pad Poles 3 inch',                $piece,     65000,   1,      'Supplies', 'C');
        $make('SUP-042',  'Pad Poles 5 inch',                $piece,     75000,   3,      'Supplies', 'C');
        $make('SUP-043',  'Compound Axial Cut 1kg',          $botolGram, 225000,  1,      'Supplies', 'C');
        $make('SUP-044',  'Compound Axial Cut plus 1kg',     $botolGram, 435000,  1,      'Supplies', 'C');
        $make('SUP-045',  'Compound Axial finish plus 1kg',  $botolGram, 435000,  1,      'Supplies', 'C');
        $make('SUP-046',  'Lap Microfiber',                  $piece,     50000,   3,      'Supplies', 'C');
        $make('SUP-047',  'WD',                              $botol,     93000,   1,      'Supplies', 'C');
        $make('SUP-048',  'Lakban Bening Besar',             $roll,      15000,   5,      'Supplies', 'C');
        $make('SUP-049',  'Lap Tack Cloth Putih',            $piece,     54810,   10,     'Supplies', 'C');
        $make('SUP-050',  'Lap Tack Cloth Kuning',           $piece,     23333,   1,      'Supplies', 'C');
        $make('SUP-051',  'Masking Film Roll',               $roll,      1500000, 2,      'Supplies', 'C');
        $make('SUP-052',  'Vinil',                           $piece,     100000,  1,      'Supplies', 'C');
        $make('SUP-053',  'Kanebo',                          $piece,     10000,   1,      'Supplies', 'C');
        $make('SUP-054',  'WD',                              $piece,     93000,   1,      'Supplies', 'C');
        $make('SUP-055',  'Mata Gerinda Potong-tbl',         $piece,     8000,    1,      'Supplies', 'T');
        $make('SUP-056',  'Kawat LasAcetylene@5kg',          $piece,     175000,  1,      'Supplies', 'C');
        $make('SUP-057',  'Baut Ulir Kunci 10',              $piece,     750,     0,      'Supplies', 'C');
        $make('SUP-058',  'Baut Skrup Gypsum',               $piece,     5000,    50,     'Supplies', 'C');
        $make('SUP-059',  'Baut 10',                         $piece,     800,     49,     'Supplies', 'C');
        $make('SUP-060',  'Mur 10',                          $piece,     800,     49,     'Supplies', 'C');
        $make('SUP-061',  'Ring 10',                         $piece,     800,     50,     'Supplies', 'C');
        $make('SUP-062',  'Baut 12',                         $piece,     1250,    50,     'Supplies', 'C');
        $make('SUP-063',  'Mur 12',                          $piece,     1250,    50,     'Supplies', 'C');
        $make('SUP-064',  'Ring 12',                         $piece,     1250,    50,     'Supplies', 'C');
        $make('SUP-065',  'Baut 14',                         $piece,     2500,    46,     'Supplies', 'C');
        $make('SUP-066',  'Mur 14',                          $piece,     2500,    53,     'Supplies', 'C');
        $make('SUP-067',  'Ring 14',                         $piece,     2500,    46,     'Supplies', 'C');
        $make('SUP-068',  'Klip Bumper (universal)',         $set,       8000,    602,    'Supplies', 'C');
        $make('SUP-069',  'Pad Wool kriwil',                 $piece,     185000,  1,      'Supplies', 'C');
        $make('SUP-070',  'Toplos',                          $piece,     4260,    50,     'Supplies', 'C');
        $make('SUP-071',  'gelus plastik',                   $piece,     12000,   133,    'Supplies', 'C');
        $make('SUP-072',  'Gelas Ukur',                      $piece,     37200,   1,      'Supplies', 'C');
        $make('SUP-073',  'Tusuk Gigi',                      $pack,      11100,   1,      'Supplies', 'C');
        $make('SUP-074',  'Kuas Minyak',                     $pack,      62400,   1,      'Supplies', 'C');
        $make('SUP-075',  'Gerjaji Kayu',                    $piece,     65000,   2,      'Supplies', 'C');
        $make('SUP-076',  'Amplas Roll Taiyo No 60',         $roll,      410000,  1,      'Supplies', 'C');
        $make('SUP-077',  'Amplas Roll Taiyo No 120',        $roll,      350000,  1,      'Supplies', 'C');
    }
}
