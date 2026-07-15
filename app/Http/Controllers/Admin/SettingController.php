<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AutoController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CronLog;
use App\Models\CronSetting;
use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function website()
    {
        $website = Website::all();

        return view('admin.setting.website', [
            'title' => 'Pengaturan Website',
            'content' => $website,
        ] + $this->websiteData());
    }

    public function websiteUpdate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            // image opsional: boleh update title saja tanpa ganti logo.
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120|dimensions:max_width=2000,max_height=2000',
        ], [
            'title.required' => 'Title wajib diisi',
            'image.max' => 'Ukuran logo maksimal 5MB',
            'image.mimes' => 'Format logo harus PNG, JPG, JPEG, atau WEBP',
            'image.image' => 'File yang diperbolehkan hanya gambar',
            'image.dimensions' => 'Dimensi logo maksimal 2000x2000px',
        ]);

        $website = Website::find(1);
        $data = ['title' => $request->input('title')];

        // Hanya proses bila ada file valid yang benar-benar terupload.
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $gambar = $request->file('image');

            // Nama file selalu di-generate server (hash) + ekstensi dari isi file
            // (guessExtension, bukan nama asli user) → cegah path traversal / spoofing.
            $ext = $gambar->guessExtension() ?: 'png';
            $filename = 'logo-'.date('ymd').'-'.substr(md5(uniqid('', true)), 0, 10).'.'.$ext;
            $gambar->move(public_path('assets/logo'), $filename);

            // Hapus logo lama setelah upload baru sukses (guard: hanya di assets/logo).
            $old = $website->logo ?? '';
            if ($old !== '' && basename($old) === $old) {
                $oldPath = public_path('assets/logo/'.$old);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $data['logo'] = $filename;
        }

        Website::where('id', 1)->update($data);

        return redirect('admin/setting/website')->with('success', ['Berhasil mengubah data website']);
    }

    public function company()
    {
        $company = Company::all();

        return view('admin.setting.company', [
            'title' => 'Pengaturan Company',
            'content' => $company,
        ] + $this->websiteData());
    }

    public function companyUpdate(Request $request)
    {
        Company::where('id', '1')->update([
            'email' => $request->input('email'),
            'phone_number' => $request->input('phone'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'country' => $request->input('country'),
            'postal_code' => $request->input('postal_code'),
        ]);

        return redirect('admin/setting/company')->with('success', ['Berhasil mengubah data company']);
    }

    public function notification()
    {
        $notification = Notification::all();

        return view('admin.setting.notification', [
            'title' => 'Setting Notification',
            'content' => $notification,
        ] + $this->websiteData());
    }

    public function notificationUpdate(Request $request)
    {
        Notification::where('id', $request->input('id'))->update([
            'sebelum' => $request->input('sebelum'),
            'notif_tagihan' => $request->input('notif_tagihan') ?? 'off',
            'notif_jatuh_tempo_h' => $request->input('notif_jatuh_tempo_h') ?? 'off',
            'notif_jatuh_tempo_h1' => $request->input('notif_jatuh_tempo_h1') ?? 'off',
            'notif_jatuh_tempo_h3' => $request->input('notif_jatuh_tempo_h3') ?? 'off',
            'notif_jatuh_tempo_h7' => $request->input('notif_jatuh_tempo_h7') ?? 'off',
            'notif_pelanggan_baru' => $request->input('notif_pelanggan_baru') ?? 'off',
            'notif_tagihan_terbayar' => $request->input('notif_tagihan_terbayar') ?? 'off',
        ]);

        return redirect('admin/setting/notification')->with('success', ['Data berhasil diupdate']);
    }

    // GET admin/setting/cron — atur jadwal cron per task (jam + on/off)
    public function cron(Request $request)
    {
        $showData = $request->boolean('show_data');

        // Scheduler dianggap aktif jika heartbeat tersentuh < 5 menit lalu
        // (heartbeat di-touch tiap menit oleh schedule:run).
        $lastHeartbeat = $showData ? CronLog::lastHeartbeat() : null;
        $cronAlive = $lastHeartbeat !== null && $lastHeartbeat->gt(now()->subMinutes(5));

        $logs = collect();
        $lastRunByTask = [];
        if ($showData && Schema::hasTable('cron_log')) {
            $logs = CronLog::orderByDesc('started_at')->limit(50)->get();
            $lastRunByTask = CronLog::selectRaw('task, MAX(started_at) AS last_run')
                ->groupBy('task')->pluck('last_run', 'task')->all();
        }

        return view('admin.setting.cron', [
            'title' => 'Pengaturan Cron',
            'crons' => $showData ? CronSetting::orderBy('id')->get() : collect(),
            'cronUrl' => url('auto/cron/'.AutoController::cronToken()),
            'cronArtisan' => base_path('artisan'),
            'cronAlive' => $cronAlive,
            'lastHeartbeat' => $lastHeartbeat,
            'cronLogs' => $logs,
            'lastRunByTask' => $lastRunByTask,
            'showData' => $showData,
        ] + $this->websiteData());
    }

    // POST admin/setting/cron/update
    public function cronUpdate(Request $request)
    {
        $times = (array) $request->input('time', []);
        $enabled = (array) $request->input('enabled', []);

        foreach (CronSetting::all() as $cron) {
            $time = $times[$cron->task] ?? $cron->time;

            // Validasi format HH:MM; kalau tidak valid, pertahankan waktu lama.
            if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                $time = $cron->time;
            }

            CronSetting::where('id', $cron->id)->update([
                'time' => $time,
                'enabled' => isset($enabled[$cron->task]) ? 1 : 0,
            ]);
        }

        return redirect('admin/setting/cron')->with('success', ['Jadwal cron berhasil diupdate']);
    }
}
