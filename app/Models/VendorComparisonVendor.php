<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorComparisonVendor extends Model
{
    protected $fillable = [
        'vendor_comparison_id',
        'vendor_order',
        'nama_calon_vendor',
        'alamat',
        'telepon_fax',
        'email',
        'pic_contact_person',
        'metode_pembayaran',
        'rekening_bank',
        'term_of_payment',
        'harga_barang_jasa',
        'ketentuan_lain',
    ];

    protected $casts = [
        'harga_barang_jasa' => 'decimal:2',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(VendorComparison::class, 'vendor_comparison_id');
    }
}
