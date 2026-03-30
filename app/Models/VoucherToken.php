<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherToken extends Model
{
    protected $fillable = [
        'voucher_id',
        'token_code',
        'status', 
    ];

    // Relasi ke Voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
