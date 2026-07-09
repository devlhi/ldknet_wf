<?php

namespace Database\Seeders;

use App\Models\AccAccount;
use Illuminate\Database\Seeder;

class AccChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->accounts() as $acc) {
            AccAccount::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'subtype' => $acc['subtype'] ?? null,
                    'is_cash' => $acc['is_cash'] ?? false,
                    'is_locked' => $acc['is_locked'] ?? false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function accounts(): array
    {
        return [
            // ===== ASET (1) =====
            ['code' => '1-10001', 'name' => 'Kas', 'type' => 'asset', 'subtype' => 'current_asset', 'is_cash' => true],
            ['code' => '1-10002', 'name' => 'Kas Kecil', 'type' => 'asset', 'subtype' => 'current_asset', 'is_cash' => true],
            ['code' => '1-10003', 'name' => 'Bank', 'type' => 'asset', 'subtype' => 'current_asset', 'is_cash' => true],
            ['code' => '1-10100', 'name' => 'Piutang Usaha', 'type' => 'asset', 'subtype' => 'current_asset', 'is_locked' => true],
            ['code' => '1-10200', 'name' => 'Piutang Lain-lain', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1-10300', 'name' => 'Persediaan Barang', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1-10400', 'name' => 'Uang Muka Pembelian', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1-10500', 'name' => 'PPN Masukan', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1-20001', 'name' => 'Peralatan', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1-20002', 'name' => 'Akumulasi Penyusutan Peralatan', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1-20003', 'name' => 'Kendaraan', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1-20004', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1-20005', 'name' => 'Bangunan', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1-20006', 'name' => 'Akumulasi Penyusutan Bangunan', 'type' => 'asset', 'subtype' => 'fixed_asset'],

            // ===== LIABILITAS (2) =====
            ['code' => '2-10001', 'name' => 'Utang Usaha', 'type' => 'liability', 'subtype' => 'current_liability', 'is_locked' => true],
            ['code' => '2-10002', 'name' => 'Utang Lain-lain', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2-10003', 'name' => 'Uang Muka Penjualan', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2-10004', 'name' => 'PPN Keluaran', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2-10005', 'name' => 'Utang Pajak', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2-20001', 'name' => 'Utang Bank Jangka Panjang', 'type' => 'liability', 'subtype' => 'long_liability'],

            // ===== EKUITAS (3) =====
            ['code' => '3-10001', 'name' => 'Modal Disetor', 'type' => 'equity', 'subtype' => 'equity'],
            ['code' => '3-10002', 'name' => 'Prive / Penarikan', 'type' => 'equity', 'subtype' => 'equity'],
            ['code' => '3-10003', 'name' => 'Laba Ditahan', 'type' => 'equity', 'subtype' => 'equity', 'is_locked' => true],
            ['code' => '3-10004', 'name' => 'Laba Tahun Berjalan', 'type' => 'equity', 'subtype' => 'equity', 'is_locked' => true],

            // ===== PENDAPATAN (4) =====
            ['code' => '4-10001', 'name' => 'Pendapatan Jasa Internet', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
            ['code' => '4-10002', 'name' => 'Pendapatan Pemasangan', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
            ['code' => '4-10003', 'name' => 'Pendapatan Penjualan Barang', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
            ['code' => '4-20001', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'subtype' => 'other_revenue'],
            ['code' => '4-20002', 'name' => 'Diskon Penjualan', 'type' => 'revenue', 'subtype' => 'contra_revenue'],

            // ===== HARGA POKOK PENJUALAN (5) =====
            ['code' => '5-10001', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'subtype' => 'cogs'],
            ['code' => '5-10002', 'name' => 'Biaya Bandwidth / Upstream', 'type' => 'expense', 'subtype' => 'cogs'],

            // ===== BEBAN (6) =====
            ['code' => '6-10001', 'name' => 'Beban Gaji', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10002', 'name' => 'Beban Listrik & Air', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10003', 'name' => 'Beban Sewa', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10004', 'name' => 'Beban Telepon & Internet', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10005', 'name' => 'Beban Transportasi', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10006', 'name' => 'Beban Perlengkapan Kantor', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10007', 'name' => 'Beban Pemeliharaan', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10008', 'name' => 'Beban Penyusutan', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10009', 'name' => 'Beban Administrasi Bank', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-10010', 'name' => 'Beban Pemasaran', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6-20001', 'name' => 'Beban Pajak', 'type' => 'expense', 'subtype' => 'other_expense'],
            ['code' => '6-20002', 'name' => 'Beban Lain-lain', 'type' => 'expense', 'subtype' => 'other_expense'],
        ];
    }
}
