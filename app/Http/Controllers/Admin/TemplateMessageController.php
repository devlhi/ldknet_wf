<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateMessage;
use App\Models\Website;
use Illuminate\Http\Request;

class TemplateMessageController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function templateMessage(Request $request)
    {
        $showData = $request->boolean('show_data');

        return view('admin.gateway.template', [
            'title' => 'Template Message',
            'content' => $showData ? TemplateMessage::all() : collect(),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function templateMessageUpdate(Request $request)
    {
        // Mapping field mengikuti kode CI4 asli (notif_pembelian_voucher -> notif_tagihan_email, dst)
        TemplateMessage::where('id', $request->post('id'))->update([
            'notif_tagihan' => $request->post('notif_tagihan'),
            'notif_pengingat' => $request->post('notif_pengingat'),
            'notif_tagihan_terbayar' => $request->post('notif_tagihan_terbayar'),
            'notif_pelanggan_baru' => $request->post('notif_pelanggan_baru'),
            'notif_tagihan_email' => $request->post('notif_pembelian_voucher'),
            'notif_pengingat_email' => $request->post('notif_pembelian_voucher_terbayar'),
            'notif_pelanggan_baru_email' => $request->post('notif_pelanggan_baru_email'),
            'notif_tagihan_terbayar_email' => $request->post('notif_tagihan_terbayar_email'),
        ]);

        return redirect(url('admin/template/message'))->with('success', ['Data berhasil diupdate']);
    }
}
