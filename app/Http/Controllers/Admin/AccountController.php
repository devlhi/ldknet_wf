<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function account()
    {
        return view('admin.account.index', [
            'title' => 'Pengaturan Akun',
            'user' => auth()->user(),
        ] + $this->websiteData());
    }

    public function changepassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ], [
            'current_password.required' => 'Password saat ini harus di isi.',
            'new_password.required' => 'Password baru harus di isi.',
            'new_password.min' => 'Password baru minimal 8 Karakter',
            'confirm_password.required' => 'Konfirmasi password harus di isi.',
            'confirm_password.same' => 'Konfirmasi password harus sama',
        ]);

        $user = auth()->user();

        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return redirect()->back()->with('auth_errors', ['Password saat ini salah']);
        }

        User::where('email', $user->email)->update([
            'password' => Hash::make((string) $request->input('new_password')),
        ]);

        return redirect('admin/account')->with('success', ['Password berhasil diganti']);
    }
}
