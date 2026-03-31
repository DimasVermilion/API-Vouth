<?php

namespace App\Imports;

use App\Models\VoucherToken;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class VoucherTokenImport implements ToModel, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private $rows = 0;

    public function model(array $row)
    {
        $this->rows++;

        return new VoucherToken([
            'voucher_id' => $row[0], // Kolom A: ID dari Jenis Voucher
            'token_code' => $row[1], // Kolom B: Kode Token 
            'status'     => $row[2] ?? 'available', 
        ]);
    }

    public function rules(): array
    {
        return [
            '0' => 'required|exists:vouchers,id',             
            '1' => 'required|unique:voucher_tokens,token_code', 
        ];
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }
}