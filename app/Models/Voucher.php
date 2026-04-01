<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration'
    ];

    // Relasi ke Token (untuk melihat stok)
    public function tokens()
    {
        return $this->hasMany(VoucherToken::class);
    }
}