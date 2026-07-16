<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Panel;

class PanelLaborSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            ['code' => 'PNL-001', 'description' => 'Bumper Depan',              'p0_300' => 756000,    'p300_500' => 840000,    'p500_800' => 1050000,  'p800_2000' => 1155000],
            ['code' => 'PNL-002', 'description' => 'Bumper Belakang',           'p0_300' => 756000,    'p300_500' => 840000,    'p500_800' => 1050000,  'p800_2000' => 1155000],
            ['code' => 'PNL-003', 'description' => 'Moulding Bumper /pcs',      'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 420000,   'p800_2000' => 462000],
            ['code' => 'PNL-004', 'description' => 'Grill bumper',              'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 560000,   'p800_2000' => 616000],
            ['code' => 'PNL-005', 'description' => 'Radiator support',          'p0_300' => 409500,    'p300_500' => 455000,    'p500_800' => 560000,   'p800_2000' => 616000],
            ['code' => 'PNL-006', 'description' => 'Panel grill',               'p0_300' => 409500,    'p300_500' => 455000,    'p500_800' => 560000,   'p800_2000' => 616000],
            ['code' => 'PNL-007', 'description' => 'Engine hood',               'p0_300' => 1134000,   'p300_500' => 1260000,   'p500_800' => 1540000,  'p800_2000' => 1694000],
            ['code' => 'PNL-008', 'description' => 'Roof sedan',                'p0_300' => 1134000,   'p300_500' => 1260000,   'p500_800' => 1960000,  'p800_2000' => 2156000],
            ['code' => 'PNL-009', 'description' => 'Roof SUV',                  'p0_300' => 1134000,   'p300_500' => 1260000,   'p500_800' => 1925000,  'p800_2000' => 2117500],
            ['code' => 'PNL-010', 'description' => 'Roof minibus',              'p0_300' => 1417500,   'p300_500' => 1575000,   'p500_800' => 1750000,  'p800_2000' => 1925000],
            ['code' => 'PNL-011', 'description' => 'Pintu Depan',               'p0_300' => 945000,    'p300_500' => 1050000,   'p500_800' => 1120000,  'p800_2000' => 1232000],
            ['code' => 'PNL-012', 'description' => 'Handle (all door)',         'p0_300' => 350000,    'p300_500' => 350000,    'p500_800' => 450000,   'p800_2000' => 450000],
            ['code' => 'PNL-013', 'description' => 'Trisplang',                 'p0_300' => 630000,    'p300_500' => 700000,    'p500_800' => 700000,   'p800_2000' => 770000],
            ['code' => 'PNL-014', 'description' => 'Pintu belakang',            'p0_300' => 945000,    'p300_500' => 1050000,   'p500_800' => 1120000,  'p800_2000' => 1232000],
            ['code' => 'PNL-015', 'description' => 'Fender depan',              'p0_300' => 756000,    'p300_500' => 840000,    'p500_800' => 1120000,  'p800_2000' => 1232000],
            ['code' => 'PNL-016', 'description' => 'Inner',                     'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 385000,   'p800_2000' => 423500],
            ['code' => 'PNL-017', 'description' => 'Moulding fender',           'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 420000,   'p800_2000' => 462000],
            ['code' => 'PNL-018', 'description' => 'Quarter panel',             'p0_300' => 1039500,   'p300_500' => 1155000,   'p500_800' => 1260000,  'p800_2000' => 1386000],
            ['code' => 'PNL-019', 'description' => 'Quarter panel inter',       'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 420000,   'p800_2000' => 462000],
            ['code' => 'PNL-020', 'description' => 'Trunk',                     'p0_300' => 1134000,   'p300_500' => 1260000,   'p500_800' => 1540000,  'p800_2000' => 1694000],
            ['code' => 'PNL-021', 'description' => 'Garnish bagasi',            'p0_300' => 252000,    'p300_500' => 280000,    'p500_800' => 560000,   'p800_2000' => 616000],
            ['code' => 'PNL-022', 'description' => 'Cross member belakang',     'p0_300' => 346500,    'p300_500' => 385000,    'p500_800' => 476000,   'p800_2000' => 523600],
            ['code' => 'PNL-023', 'description' => 'Rumah lampu belakang',      'p0_300' => 252000,    'p300_500' => 280000,    'p500_800' => 350000,   'p800_2000' => 385000],
            ['code' => 'PNL-024', 'description' => 'Cover spion /pcs',          'p0_300' => 350000,    'p300_500' => 350000,    'p500_800' => 450000,   'p800_2000' => 450000],
            ['code' => 'PNL-025', 'description' => 'Tutup bensin',              'p0_300' => 378000,    'p300_500' => 420000,    'p500_800' => 462000,   'p800_2000' => 508200],
            ['code' => 'PNL-026', 'description' => 'Pilar A',                   'p0_300' => 472500,    'p300_500' => 525000,    'p500_800' => 595000,   'p800_2000' => 654500],
            ['code' => 'PNL-027', 'description' => 'Pilar B',                   'p0_300' => 472500,    'p300_500' => 525000,    'p500_800' => 595000,   'p800_2000' => 654500],
            ['code' => 'PNL-028', 'description' => 'Lantai depan / tengah',     'p0_300' => 693000,    'p300_500' => 770000,    'p500_800' => 1050000,  'p800_2000' => 1155000],
            ['code' => 'PNL-029', 'description' => 'Lantai bagasi',             'p0_300' => 787500,    'p300_500' => 875000,    'p500_800' => 1050000,  'p800_2000' => 1155000],
            ['code' => 'PNL-030', 'description' => 'Celette bench',             'p0_300' => 5355000,   'p300_500' => 5950000,   'p500_800' => 6300000,  'p800_2000' => 6930000],
            ['code' => 'PNL-031', 'description' => 'Stel pintu baru / lama',    'p0_300' => 252000,    'p300_500' => 280000,    'p500_800' => 280000,   'p800_2000' => 308000],
            ['code' => 'PNL-032', 'description' => 'Engsel pintu',              'p0_300' => 252000,    'p300_500' => 280000,    'p500_800' => 350000,   'p800_2000' => 385000],
            ['code' => 'PNL-033', 'description' => 'Firewall',                  'p0_300' => 787500,    'p300_500' => 875000,    'p500_800' => 980000,   'p800_2000' => 1078000],
            ['code' => 'PNL-034', 'description' => 'Brecket bumper',            'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 350000,   'p800_2000' => 385000],
            ['code' => 'PNL-035', 'description' => 'List lampu',                'p0_300' => 220500,    'p300_500' => 245000,    'p500_800' => 315000,   'p800_2000' => 346500],
            ['code' => 'PNL-036', 'description' => 'B/P lock pintu',            'p0_300' => 220500,    'p300_500' => 245000,    'p500_800' => 364000,   'p800_2000' => 400400],
            ['code' => 'PNL-037', 'description' => 'Handle pintu',              'p0_300' => 315000,    'p300_500' => 350000,    'p500_800' => 560000,   'p800_2000' => 616000],
            ['code' => 'PNL-038', 'description' => 'List pintu',                'p0_300' => 220500,    'p300_500' => 245000,    'p500_800' => 616000,   'p800_2000' => 677600],
            ['code' => 'PNL-039', 'description' => 'Apron',                     'p0_300' => 504000,    'p300_500' => 560000,    'p500_800' => 700000,   'p800_2000' => 770000],
            ['code' => 'PNL-040', 'description' => 'Panel atas pintu',          'p0_300' => 504000,    'p300_500' => 560000,    'p500_800' => 630000,   'p800_2000' => 693000],
            ['code' => 'PNL-041', 'description' => 'Cat 1 body Siram luar',     'p0_300' => 13500000,  'p300_500' => 15500000,  'p500_800' => 18900000, 'p800_2000' => 21000000],
            ['code' => 'PNL-042', 'description' => 'Cat 1 Body luar Dalam',     'p0_300' => 18000000,  'p300_500' => 20000000,  'p500_800' => 25000000, 'p800_2000' => 27000000],
            ['code' => 'PNL-043', 'description' => 'Sensor parking (cat) /pcs', 'p0_300' => 189000,    'p300_500' => 189000,    'p500_800' => 315000,   'p800_2000' => 315000],
            ['code' => 'PNL-044', 'description' => 'Sensor parking (bongkar pasang) /pcs', 'p0_300' => 82500, 'p300_500' => 175000, 'p500_800' => 175000, 'p800_2000' => 192500],
            ['code' => 'PNL-045', 'description' => 'AC (isi freon dan Oli kompresor)', 'p0_300' => 350000, 'p300_500' => 350000, 'p500_800' => 450000, 'p800_2000' => 450000],
            ['code' => 'PNL-046', 'description' => 'Turun naik mesin',          'p0_300' => 1575000,   'p300_500' => 1750000,   'p500_800' => 2100000,  'p800_2000' => 2500000],
            ['code' => 'PNL-047', 'description' => 'Bongkar pasang spion /pcs', 'p0_300' => 82500,     'p300_500' => 105000,    'p500_800' => 175000,   'p800_2000' => 192500],
            ['code' => 'PNL-048', 'description' => 'Bongkar pasang dashboard',  'p0_300' => 550000,    'p300_500' => 700000,    'p500_800' => 1050000,  'p800_2000' => 1155000],
            ['code' => 'PNL-049', 'description' => 'Bongkar pasang jok depan /pcs', 'p0_300' => 137500, 'p300_500' => 175000,  'p500_800' => 245000,   'p800_2000' => 280000],
            ['code' => 'PNL-050', 'description' => 'Bongkar pasang jok belakang', 'p0_300' => 192500,  'p300_500' => 245000,   'p500_800' => 350000,   'p800_2000' => 440000],
            ['code' => 'PNL-051', 'description' => 'Bongkar pasang console tengah', 'p0_300' => 275000, 'p300_500' => 525000,  'p500_800' => 700000,   'p800_2000' => 800000],
            ['code' => 'PNL-052', 'description' => 'Bongkar pasang karpet',     'p0_300' => 550000,    'p300_500' => 700000,    'p500_800' => 1050000,  'p800_2000' => 1320000],
            ['code' => 'PNL-053', 'description' => 'Bongkar pasang plafond',    'p0_300' => 550000,    'p300_500' => 700000,    'p500_800' => 1050000,  'p800_2000' => 1200000],
            ['code' => 'PNL-054', 'description' => 'Bongkar pasang sunroof',    'p0_300' => 825000,    'p300_500' => 1050000,   'p500_800' => 1400000,  'p800_2000' => 1600000],
            ['code' => 'PNL-055', 'description' => 'Bongkar pasang lampu',      'p0_300' => 220000,    'p300_500' => 280000,    'p500_800' => 350000,   'p800_2000' => 440000],
            ['code' => 'PNL-056', 'description' => 'bongkar pasang bumper',     'p0_300' => 137500,    'p300_500' => 175000,    'p500_800' => 245000,   'p800_2000' => 280000],
            ['code' => 'PNL-057', 'description' => 'Bongkar pasang kaca depan', 'p0_300' => 825000,    'p300_500' => 1050000,   'p500_800' => 1400000,  'p800_2000' => 1600000],
            ['code' => 'PNL-058', 'description' => 'Bongkar pasang kaca belakang', 'p0_300' => 550000, 'p300_500' => 700000,   'p500_800' => 1050000,  'p800_2000' => 1200000],
            ['code' => 'PNL-059', 'description' => 'Bongkar kaca mati samping', 'p0_300' => 275000,    'p300_500' => 350000,    'p500_800' => 525000,   'p800_2000' => 600000],
            ['code' => 'PNL-060', 'description' => 'Bongkar pasang kaca pintu', 'p0_300' => 192500,    'p300_500' => 245000,    'p500_800' => 350000,   'p800_2000' => 440000],
            ['code' => 'PNL-061', 'description' => 'Ganti spion',               'p0_300' => 137500,    'p300_500' => 175000,    'p500_800' => 245000,   'p800_2000' => 280000],
            ['code' => 'PNL-062', 'description' => 'Bongkar pasang radiator',   'p0_300' => 275000,    'p300_500' => 350000,    'p500_800' => 525000,   'p800_2000' => 800000],
            ['code' => 'PNL-063', 'description' => 'Bongkar pasang extrafan',   'p0_300' => 275000,    'p300_500' => 350000,    'p500_800' => 525000,   'p800_2000' => 800000],
            ['code' => 'PNL-064', 'description' => 'Bongkar pasang condensor',  'p0_300' => 275000,    'p300_500' => 350000,    'p500_800' => 525000,   'p800_2000' => 800000],
            ['code' => 'PNL-065', 'description' => 'Ganti chasis depan',        'p0_300' => 1375000,   'p300_500' => 1750000,   'p500_800' => 2100000,  'p800_2000' => 2400000],
            ['code' => 'PNL-066', 'description' => 'Ganti chasis tengah',       'p0_300' => 1375000,   'p300_500' => 1750000,   'p500_800' => 2100000,  'p800_2000' => 2400000],
            ['code' => 'PNL-067', 'description' => 'Ganti chasis belakang',     'p0_300' => 1375000,   'p300_500' => 1750000,   'p500_800' => 2100000,  'p800_2000' => 2400000],
        ];

        foreach ($panels as $panel) {
            Panel::updateOrCreate(
                ['panel_code' => $panel['code']],
                [
                    'description'    => $panel['description'],
                    'price'          => $panel['p0_300'],
                    'price_0_300'    => $panel['p0_300'],
                    'price_300_500'  => $panel['p300_500'],
                    'price_500_800'  => $panel['p500_800'],
                    'price_800_2000' => $panel['p800_2000'],
                    'is_active'      => true,
                ]
            );
        }

        $this->command->info('67 panels seeded successfully!');
    }
}
