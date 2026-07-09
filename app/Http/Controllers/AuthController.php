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

class AuthController extends Controller
{
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
            default => redirect('user/dashboard'),
        };
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

        // Login bisa pakai email atau nomor HP, sama seperti aplikasi lama
        $user = User::where('email', $credentials['email'])
            ->orWhere('nomor', $credentials['email'])
            ->first();

        if (! $user) {
            return redirect('auth/login')->with('auth_errors', ['Email tidak terdaftar']);
        }

        if (! $user->isVerified()) {
            return redirect('auth/login')->with('auth_errors', ['Akun anda belum diverifikasi, mohon cek email inbox / spam anda !']);
        }

        if (! in_array($user->level, ['developer', 'admin', 'finance', 'user'])) {
            return redirect('auth/login')->with('auth_errors', ['Email tidak terdaftar']);
        }

        if ($user->level === 'user' && ! $user->isActive()) {
            return redirect('auth/login')->with('auth_errors', ['Akun anda nonaktif, hubungi Admin']);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return redirect('auth/login')->with('auth_errors', ['Password salah']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        // idpel dipakai dashboard user (diambil dari orders, perilaku app lama)
        if ($user->level === 'user') {
            $order = Order::where('email', $user->email)
                ->orWhere('nomor', $user->nomor)
                ->orderByDesc('id')
                ->first();
            $request->session()->put('idpel', $order->idpel ?? null);
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

        if (! $user) {
            return redirect('auth/forgot')->with('auth_errors', ['Email tidak ditemukan']);
        }

        $token = md5($email.now()->format('d-m-Y H:i:s'));

        TokenUser::create([
            'token' => $token,
            'email' => $email,
            'date_create' => time(),
        ]);

        try {
            $mailer->sendResetLink($user, $token);
        } catch (\Throwable $e) {
            report($e);

            return redirect('auth/forgot')->with('auth_errors', ['Gagal, kesalahan sistem']);
        }

        return redirect('auth/forgot')->with('success', ['Berhasil mengirimkan link reset password, harap cek inbox / spam !']);
    }

    public function resetpassword(string $token)
    {
        $data = TokenUser::where('token', $token)->first();

        if (! $data) {
            return redirect('auth/login')->with('auth_errors', ['Invalid token']);
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

        $data = TokenUser::where('token', $token)->first();

        if (! $data) {
            return redirect('auth/login')->with('auth_errors', ['Invalid token']);
        }

        User::where('email', $data->email)->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect('auth/login')->with('success', ['Password berhasil diganti']);
    }
}
