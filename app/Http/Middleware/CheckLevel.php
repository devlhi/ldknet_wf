<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti AccessFilter CI4: batasi area (admin/finance/user/server)
 * berdasarkan kolom `level` pada tabel users.
 *
 * Pemakaian di route: middleware('level:admin,developer')
 */
class CheckLevel
{
    public function handle(Request $request, Closure $next, string ...$levels): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('auth/login');
        }

        // Akun yang dinonaktifkan admin langsung diputus sesi pada request berikutnya.
        if ($user->status_account !== null && ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('auth/login')->with('auth_errors', ['Akun anda nonaktif, hubungi Admin']);
        }

        if (! in_array($user->level, $levels)) {
            return match ($user->level) {
                'developer', 'admin' => redirect('admin'),
                'finance' => redirect('finance'),
                'technician' => redirect('karyawan/absensi'),
                default => redirect('user'),
            };
        }

        return $next($request);
    }
}
