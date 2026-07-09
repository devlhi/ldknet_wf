<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Website;
use App\Services\NewAccountMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ManageController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function users()
    {
        $account = User::whereIn('level', ['admin', 'technician', 'finance'])->get();

        return view('admin.user.management', [
            'title' => 'User Management',
            'account' => $account,
            'password' => random(5),
        ] + $this->websiteData());
    }

    public function usersAdd(Request $request, NewAccountMailer $mailer)
    {
        $email = $request->input('email');
        $nama = $request->input('nama');
        $nomor = $request->input('nomor');
        $password = (string) $request->input('password');
        $level = $request->input('level');

        User::create([
            'email' => $email,
            'nama' => $nama,
            'nomor' => $nomor,
            'password' => Hash::make($password),
            'level' => $level,
            'verify_account' => '1',
        ]);

        try {
            $mailer->sendAccountInfo($nama, $email, $password);

            return redirect('admin/manage/user')->with('success', ['Berhasil menambahkan data akun baru']);
        } catch (\Throwable $e) {
            return redirect('admin/manage/user')->with('auth_errors', ['Gagal menambahkan data akun baru ']);
        }
    }

    public function editUsers($id)
    {
        $account = User::where('id', $id)->get();

        if ($account->isEmpty()) {
            return redirect('admin/manage/user');
        }

        if ($account->first()->level === 'user') {
            return redirect('admin/manage/user');
        }

        return view('admin.user.edit', [
            'title' => 'Edit User',
            'account' => $account,
        ] + $this->websiteData());
    }

    public function deleteUser($id)
    {
        $account = User::where('id', $id)->get();

        if ($account->isEmpty()) {
            return redirect('admin/manage/user');
        }

        User::where('id', $id)->delete();

        return redirect('admin/manage/user')->with('success', ['Berhasil menghapus user']);
    }

    public function updateUsers(Request $request, $id)
    {
        $account = User::where('id', $id)->get();

        if ($account->isEmpty()) {
            return redirect('admin/manage/user');
        }

        User::where('id', $id)->update([
            'password' => Hash::make((string) $request->input('password')),
        ]);

        return redirect('admin/manage/user')->with('success', ['Berhasil mengupdate password tersebut']);
    }

    public function coupon()
    {
        $coupon = Coupon::all();

        return view('admin.coupon.management', [
            'title' => 'Coupon Management',
            'content' => $coupon,
        ] + $this->websiteData());
    }

    public function addCoupon(Request $request)
    {
        Coupon::create([
            'code' => $request->input('code'),
            'rate' => $request->input('rate'),
            'otp' => $request->input('otp'),
            'status' => 'Active',
        ]);

        return redirect('admin/manage/coupon')->with('success', ['Berhasil menambahkan data kupon ']);
    }

    public function deleteCoupon($id)
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return redirect('admin/manage/coupon')->with('auth_errors', ['Gagal menghapus kupon']);
        }

        $coupon->delete();

        return redirect('admin/manage/coupon')->with('success', ['Berhasil menghapus kupon ']);
    }

    public function updateCoupon($id)
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return redirect('admin/manage/coupon')->with('auth_errors', ['Gagal menghapus kupon']);
        }

        $coupon->update([
            'status' => $coupon->status === 'Active' ? 'Not Active' : 'Active',
        ]);

        return redirect('admin/manage/coupon')->with('success', ['Berhasil mengupdate kupon']);
    }
}
