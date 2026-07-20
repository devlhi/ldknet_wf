<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TokenUser;
use App\Models\User;
use App\Models\Website;
use App\Services\ForgotPasswordMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Masa berlaku token reset password (detik). Setelah lewat, token ditolak.
     */
    private const RESET_TOKEN_TTL = 3600; // 60 menit

    /**
     * Hash bcrypt dummy dipakai untuk menyamakan waktu respons login ketika email
     * tidak ada, supaya keberadaan akun tidak bocor lewat perbedaan timing.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$10$f4BA0BST5keNpp3feqhsbOjx8Zo7PESshz1ttSI0XxxuqX7V6Gr1O';

    /**
     * Token yang dikirim ke user berupa string acak; yang disimpan di DB adalah
     * hash-nya. Jadi kebocoran isi tabel token_user tidak membocorkan token yang
     * bisa dipakai untuk reset.
     */
    private function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Cari token reset yang masih valid (cocok + belum kedaluwarsa).
     * Token kedaluwarsa langsung dihapus.
     */
    private function findValidResetToken(string $token): ?TokenUser
    {
        if ($token === '') {
            return null;
        }

        $data = TokenUser::where('token', $this->hashResetToken($token))->first();

        if (! $data) {
            return null;
        }

        // date_create = unix timestamp saat token dibuat (kolom int legacy).
        if ((time() - (int) $data->date_create) > self::RESET_TOKEN_TTL) {
            $data->delete();

            return null;
        }

        return $data;
    }

    private function websiteData(): array
    {
        $website = Schema::hasTable('website') ? Website::first() : null;

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
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

    private function findLoginUser(string $identifier): ?User
    {
        $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'nomor';
        $users = User::where($column, $identifier)->limit(2)->get();

        return $users->count() === 1 ? $users->first() : null;
    }

    private function findCustomerOrder(User $user): ?Order
    {
        $email = trim((string) ($user->email ?? ''));
        $nomor = trim((string) ($user->nomor ?? ''));

        if ($email === '' && $nomor === '') {
            return null;
        }

        if ($email !== '' && $nomor !== '') {
            $exact = Order::where('email', $email)->where('nomor', $nomor)->orderByDesc('id')->get();
            if ($exact->pluck('idpel')->unique()->count() === 1) {
                return $exact->first();
            }
        }

        $candidates = Order::where(function ($query) use ($email, $nomor) {
            if ($email !== '') {
                $query->where('email', $email);
            }
            if ($nomor !== '') {
                $query->orWhere('nomor', $nomor);
            }
        })->orderByDesc('id')->get();

        return $candidates->pluck('idpel')->unique()->count() === 1 ? $candidates->first() : null;
    }

    public function index()
    {
        if (Auth::check()) {
            return $this->redirectByLevel(Auth::user()->level);
        }

        return view('auth.login', ['title' => 'Login'] + $this->websiteData());
    }

    public function auth(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'Email atau password tidak boleh kosong',
            'password.required' => 'Email atau password tidak boleh kosong',
        ]);

        // Login diputuskan hanya dari satu identitas yang jelas: email memakai
        // kolom email, selain itu dianggap nomor HP. Kandidat ambigu tidak boleh
        // mengambil baris pertama secara acak.
        $identifier = trim((string) $credentials['email']);
        $user = $identifier !== '' ? $this->findLoginUser($identifier) : null;

        $validLevel = $user && in_array($user->level, ['developer', 'admin', 'finance', 'user', 'technician'], true);

        // Verifikasi password DULU, sebelum mengungkap status akun apa pun. Kalau
        // user tidak ada tetap jalankan satu bcrypt (ke hash dummy) supaya waktu
        // respons sama — mencegah enumerasi akun lewat timing maupun beda pesan.
        if ($validLevel) {
            $passwordOk = Hash::check($credentials['password'], $user->password);
        } else {
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);
            $passwordOk = false;
        }

        if (! $passwordOk) {
            return redirect('auth/login')->with('auth_errors', ['Email atau password salah']);
        }

        // Pesan spesifik di bawah hanya tampil bagi yang kredensialnya sudah benar,
        // jadi tidak memberi sinyal keberadaan akun kepada penyerang.
        if (! $user->isVerified()) {
            return redirect('auth/login')->with('auth_errors', ['Akun anda belum diverifikasi, mohon cek email inbox / spam anda !']);
        }

        if ($user->status_account !== null && ! $user->isActive()) {
            return redirect('auth/login')->with('auth_errors', ['Akun anda nonaktif, hubungi Admin']);
        }

        // Akun pelanggan harus terhubung ke tepat satu identitas pelanggan sebelum
        // sesi dibuat. Data kontak ambigu tidak boleh membuka invoice pelanggan lain.
        $order = $user->level === 'user' ? $this->findCustomerOrder($user) : null;
        if ($user->level === 'user' && ! $order) {
            return redirect('auth/login')->with('auth_errors', ['Akun pelanggan belum terhubung dengan benar, hubungi Admin']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->level === 'user') {
            $request->session()->put('idpel', $order->idpel);
        }

        return $this->redirectByLevel($user->level);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgot()
    {
        if (Auth::check()) {
            return $this->redirectByLevel(Auth::user()->level);
        }

        return view('auth.forgot', ['title' => 'Reset Password'] + $this->websiteData());
    }

    public function sendforgot(Request $request, ForgotPasswordMailer $mailer)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Balas pesan sama baik email terdaftar maupun tidak, supaya keberadaan
        // akun tidak bisa dienumerasi lewat form lupa password.
        $genericResponse = redirect('auth/forgot')
            ->with('success', ['Jika email terdaftar, link reset password telah dikirim. Silakan cek inbox / spam Anda.']);

        if (! $user) {
            return $genericResponse;
        }

        // Token acak berentropi tinggi (bukan md5 dari email+waktu yang bisa ditebak).
        $token = Str::random(64);

        // Hanya boleh ada satu link reset aktif per email: buang token lama dulu.
        TokenUser::where('email', $email)->delete();

        TokenUser::create([
            'token' => $this->hashResetToken($token),
            'email' => $email,
            'date_create' => time(),
        ]);

        try {
            $mailer->sendResetLink($user, $token);
        } catch (\Throwable $e) {
            report($e);

            return redirect('auth/forgot')->with('auth_errors', ['Gagal, kesalahan sistem']);
        }

        return $genericResponse;
    }

    public function resetpassword(string $token)
    {
        if (! $this->findValidResetToken($token)) {
            return redirect('auth/login')->with('auth_errors', ['Token tidak valid atau sudah kedaluwarsa']);
        }

        return view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
        ] + $this->websiteData());
    }

    public function updateResetPassword(Request $request, string $token)
    {
        $request->validate([
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ], [
            'password.required' => 'Password harus di isi.',
            'confirm_password.required' => 'Konfirmasi password harus di isi.',
            'confirm_password.same' => 'Konfirmasi password harus sama.',
        ]);

        $data = $this->findValidResetToken($token);

        if (! $data) {
            return redirect('auth/login')->with('auth_errors', ['Token tidak valid atau sudah kedaluwarsa']);
        }

        User::where('email', $data->email)->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Token sekali-pakai: hapus semua token milik email ini setelah berhasil.
        TokenUser::where('email', $data->email)->delete();

        return redirect('auth/login')->with('success', ['Password berhasil diganti']);
    }
}
