<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    /**
     * Halaman utama publik (landing page ANNORTY NET). Pengunjung tamu melihat
     * landing; pengguna yang sudah login diarahkan ke dashboard sesuai levelnya
     * (menjaga alur redirect setelah login yang menuju '/').
     */
    public function index()
    {
        if (Auth::check()) {
            return $this->redirectByLevel((string) Auth::user()->level);
        }

        $website = Schema::hasTable('website') ? Website::first() : null;

        // Nomor WhatsApp admin untuk tombol "Hubungi Admin" (tanya paket).
        // Isi format internasional tanpa tanda +/spasi, mis. '6281234567890'.
        // Biarkan null untuk menyembunyikan tombol WhatsApp.
        $waAdmin = null;

        return view('landing', [
            'title' => 'ANNORTY NET — Internet Fiber PT Landak Annorty Net',
            'logo' => $website->logo ?? '',
            'waAdmin' => $waAdmin,
        ]);
    }

    private function redirectByLevel(string $level)
    {
        return match ($level) {
            'developer', 'admin' => redirect('admin/dashboard'),
            'finance' => redirect('finance/dashboard'),
            'technician' => redirect('karyawan/absensi'),
            default => redirect('user/dashboard'),
        };
    }
}
