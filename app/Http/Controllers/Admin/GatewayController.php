<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\WhatsAppMetaApi;
use App\Models\PaymentCat;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\SmtpSetting;
use App\Models\TemplateMessage;
use App\Models\Website;
use App\Models\Whatsapp;
use App\Models\WhatsappSetting;
use App\Support\WhatsAppGatewayResolver;
use App\Support\WhatsAppNotifier;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GatewayController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    /* =========================================================
     * PAYMENT GATEWAY
     * ========================================================= */

    public function payment()
    {
        $defaultGateways = PaymentGateway::where('payment_default', '1')->get();

        // Jika tidak ada gateway default, tampilkan semua yang enable (perilaku CI4)
        $gatewaysForSelect = $defaultGateways->isNotEmpty()
            ? $defaultGateways
            : PaymentGateway::where('status', 'enable')->get();

        $otherGateways = PaymentGateway::where('payment_default', '0')->get();

        $provider = optional($defaultGateways->last())->name;

        $method = PaymentMethod::where('provider', $provider)->get();

        return view('admin.gateway.payment.index', [
            'title' => 'Payment Gateway',
            'method' => $method,
            'defaultGateways' => $gatewaysForSelect,
            'otherGateways' => $otherGateways,
            'payment' => $defaultGateways,
        ] + $this->websiteData());
    }

    public function methodpayment()
    {
        return view('admin.gateway.payment.method.index', [
            'title' => 'Payment Method',
            'method' => PaymentMethod::all(),
            'category' => PaymentCat::where('status', '1')->get(),
            'gateway' => PaymentGateway::where('status', 'enable')->get(),
            'notdefault' => PaymentGateway::where('payment_default', '0')->get(),
        ] + $this->websiteData());
    }

    public function addpaymentmethod(Request $request)
    {
        $name = $request->post('nama');
        $provider = $request->post('gateway');

        if ($provider == '1') {
            $providernya = null;
            $providercodenya = null;
        } else {
            $providernya = $provider;
            $providercodenya = $request->post('provider_code');
        }

        PaymentMethod::create([
            'name' => $name,
            'no_rekening' => $request->post('norek'),
            'atas_nama' => $request->post('atasnama'),
            'note' => $request->post('note'),
            'category' => $request->post('category'),
            'service' => $name,
            'provider' => $providernya,
            'provider_code' => $providercodenya,
            'status' => '1',
        ]);

        return redirect(url('admin/gateway/payment/method'))->with('success', ['Berhasil menambahkan metode pembayaran']);
    }

    public function setDefault(Request $request)
    {
        $gatewayName = $request->post('gatewayName');

        $gatewayToSetDefault = PaymentGateway::where('name', $gatewayName)->first();

        if ($gatewayToSetDefault !== null) {
            // Set payment_default = 0 pada semua payment gateway
            PaymentGateway::query()->update(['payment_default' => 0]);

            // Set payment_default = 1 pada payment gateway yang baru menjadi default
            PaymentGateway::where('id', $gatewayToSetDefault->id)->update(['payment_default' => 1]);

            $response = ['success' => true];
        } else {
            $response = ['success' => false];
        }

        return response()->json($response);
    }

    public function updateMethod(Request $request)
    {
        $status = $request->post('status');
        $kode = $request->post('kodepm');

        $update = PaymentMethod::where('provider_code', $kode)->update(['status' => $status]);

        if ($update) {
            session()->flash('success_pm', ['Berhasil update payment method']);
        } else {
            session()->flash('errors_pm', ['Gagal update data']);
        }

        return response('');
    }

    public function updateMerchant(Request $request)
    {
        $newSandboxMode = $request->post('sandboxMode');
        $isSandbox = ($newSandboxMode == 'yes');

        $gateway = PaymentGateway::find($request->post('idnya'));

        if (! $gateway) {
            return redirect(url('admin/gateway/payment'))->with('auth_errors', ['Payment gateway tidak ditemukan']);
        }

        if ($gateway->name === 'duitku') {
            // Duitku: base URL berbeda antara sandbox & production; segmen `url`
            // dikosongkan karena endpoint sudah lengkap di api_url.
            PaymentGateway::where('id', $gateway->id)->update([
                'code_merchant' => $request->post('code_merchant'),
                'api_url' => $isSandbox ? 'https://sandbox.duitku.com/webapi' : 'https://passport.duitku.com/webapi',
                'api_key' => $request->post('api_key'),
                'status' => $request->post('status'),
                'url' => '',
                'sandbox' => $isSandbox ? 'yes' : 'no',
            ]);

            return redirect(url('admin/gateway/payment'))->with('success', ['Berhasil update merchant']);
        }

        // Tripay (default): segmen `url` menentukan api-sandbox / api.
        PaymentGateway::where('id', $gateway->id)->update([
            'code_merchant' => $request->post('code_merchant'),
            'api_url' => $request->post('api_url'),
            'api_key' => $request->post('api_key'),
            'private_key' => $request->post('private_key'),
            'status' => $request->post('status'),
            'url' => $isSandbox ? 'api-sandbox' : 'api',
            'sandbox' => $isSandbox ? 'yes' : 'no',
        ]);

        return redirect(url('admin/gateway/payment'))->with('success', ['Berhasil update merchant']);
    }

    /* =========================================================
     * EMAIL GATEWAY (SMTP / Brevo)
     * ========================================================= */

    public function smtp()
    {
        return view('admin.gateway.email.home', [
            'title' => 'Pengaturan SMTP',
            'content' => SmtpSetting::all(),
        ] + $this->websiteData());
    }

    public function smtpUpdate(Request $request)
    {
        SmtpSetting::where('id', $request->post('target'))->update([
            'key' => $request->post('key'),
            'nama' => $request->post('namasmtp'),
            'email' => $request->post('emailsmtp'),
        ]);

        return redirect(url('admin/setting/smtp'))->with('success', ['Berhasil update data']);
    }

    public function smtpTest()
    {
        return view('admin.gateway.email.message', [
            'title' => 'Send Email Test',
            'content' => SmtpSetting::all(),
        ] + $this->websiteData());
    }

    public function webhook()
    {
        return view('admin.webhook.index', [
            'title' => 'Webhook Setting',
            'whatsapp' => WhatsappSetting::all(),
        ] + $this->websiteData());
    }

    public function webhookUpdate(Request $request)
    {
        WhatsappSetting::where('id', $request->post('id'))->update([
            'api_url' => $request->post('api_url'),
            'api_key' => $request->post('api_key'),
            'sender' => $request->post('sender'),
        ]);

        return redirect(url('admin/webhook'))->with('success', ['Berhasil update webhook']);
    }

    public function sendsmtp(Request $request)
    {
        $subject = $request->post('subject');
        $penerima = $request->post('penerima');
        $pesan = $request->post('pesan');

        $smtp = SmtpSetting::orderByDesc('id')->first();

        if ($smtp === null) {
            return redirect(url('admin/gateway/email/message'))->with('auth_errors', ['Pengaturan SMTP belum tersedia']);
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $smtp->key);

        $apiInstance = new TransactionalEmailsApi(
            new Client,
            $config
        );

        $sendSmtpEmail = new SendSmtpEmail;
        $sendSmtpEmail['params'] = ['subject' => $subject];
        $sendSmtpEmail['subject'] = '{{params.subject}}';
        $sendSmtpEmail['htmlContent'] = view('emails.gateway-test', ['pesan' => $pesan])->render();
        $sendSmtpEmail['sender'] = ['name' => $smtp->nama, 'email' => $smtp->email];
        $sendSmtpEmail['to'] = [['email' => $penerima]];
        $sendSmtpEmail['replyTo'] = ['email' => $smtp->email, 'name' => $smtp->nama];

        if ($apiInstance->sendTransacEmail($sendSmtpEmail)) {
            return redirect(url('admin/gateway/email/message'))->with('success', ['Berhasil mengirimkan pesan']);
        }

        return redirect(url('admin/gateway/email/message'))->with('auth_errors', ['Gagal, ada kesalahan pada sistem']);
    }

    /* =========================================================
     * WHATSAPP GATEWAY
     * ========================================================= */

    public function whatsapp()
    {
        $cekwhatsapp = WhatsappSetting::all();

        if ($cekwhatsapp->isEmpty()) {
            return redirect(url('admin/gateway/whatsapp/setup'));
        }

        return view('admin.gateway.whatsapp.index', [
            'title' => 'Whatsapp Gateway',
            'content' => $cekwhatsapp,
        ] + $this->websiteData());
    }

    public function whatsappEdit($id)
    {
        $cekwhatsapp = WhatsappSetting::where('id', $id)->get();

        if ($cekwhatsapp->isEmpty()) {
            return redirect(url('admin/gateway/whatsapp/setup'));
        }

        return view('admin.gateway.whatsapp.edit', [
            'title' => 'Edit Whatsapp Gateway',
            'content' => $cekwhatsapp,
        ] + $this->websiteData());
    }

    public function whatsappTextMessage()
    {
        $cekwhatsapp = WhatsappSetting::all();

        if ($cekwhatsapp->isEmpty()) {
            return redirect(url('admin/whatsapp/setup'));
        }

        return view('admin.gateway.whatsapp.message', [
            'title' => 'Text Message',
            'content' => $cekwhatsapp,
        ] + $this->websiteData());
    }

    public function whatsappSetup(Request $request)
    {
        return view('admin.gateway.whatsapp.setup', [
            'title' => 'Whatsapp Setup',
            'prefillProvider' => $request->query('provider', 'wablas'),
        ] + $this->websiteData());
    }

    public function whatsappAdd(Request $request)
    {
        $provider = $request->post('provider', 'wablas');
        $nama = $provider === 'meta'
            ? 'Meta Official'
            : ($request->post('nama') ?: 'Whatsapp Gateway');

        $apiKey = $request->post('api_key');
        $phoneNumberId = $request->post('meta_phone_number_id') ?: $request->post('sender');
        $sender = $provider === 'meta' ? 'meta' : $request->post('sender');
        $mode = $request->post('mode') ?? 'off';

        if (empty($apiKey) || empty($provider === 'meta' ? $phoneNumberId : $sender)) {
            return redirect(url('admin/whatsapp/setup'.($provider === 'meta' ? '?provider=meta' : '')))
                ->withInput()
                ->with('auth_errors', ['API Key / Access Token dan Sender / Phone Number ID wajib diisi']);
        }

        $type = in_array($request->post('type'), ['blast', 'otp']) ? $request->post('type') : 'blast';

        if ($mode === 'on') {
            WhatsappSetting::where('mode', 'on')->update(['mode' => 'off']);
        }

        $apiUrl = $provider === 'meta'
            ? WhatsAppGatewayResolver::encodeMetaSettings($this->metaSettingsFromRequest($request))
            : ($request->post('api_url') ?: '');

        if ($error = $this->gatewayColumnLimitError($apiUrl, $sender)) {
            return redirect(url('admin/whatsapp/setup'.($provider === 'meta' ? '?provider=meta' : '')))
                ->withInput()
                ->with('auth_errors', [$error]);
        }

        WhatsappSetting::create([
            'nama' => $nama,
            'type' => $type,
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'sender' => $sender,
            'mode' => $mode,
        ]);

        return redirect(url('admin/whatsapp'))->with('success', ['Data berhasil disimpan. '.($mode === 'on' ? 'Gateway lain otomatis OFF.' : '')]);
    }

    public function whatsappUpdateSetting(Request $request)
    {
        // Kolom notifikasi (sebelum, notif_*) belum tentu ada di tabel
        // whatsapp_setting pada skema ini. Hanya update kolom yang benar-benar
        // ada agar tidak memicu SQL error "Unknown column".
        $candidate = [
            'sebelum' => $request->post('sebelum'),
            'notif_tagihan' => $request->post('notif_tagihan') ?? 'off',
            'notif_jatuh_tempo_h' => $request->post('notif_jatuh_tempo_h') ?? 'off',
            'notif_jatuh_tempo_h1' => $request->post('notif_jatuh_tempo_h1') ?? 'off',
            'notif_jatuh_tempo_h3' => $request->post('notif_jatuh_tempo_h3') ?? 'off',
            'notif_jatuh_tempo_h7' => $request->post('notif_jatuh_tempo_h7') ?? 'off',
            'notif_pelanggan_baru' => $request->post('notif_pelanggan_baru') ?? 'off',
            'notif_tagihan_terbayar' => $request->post('notif_tagihan_terbayar') ?? 'off',
        ];

        $payload = array_filter(
            $candidate,
            fn ($column) => Schema::hasColumn('whatsapp_setting', $column),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($payload)) {
            return redirect(url('admin/whatsapp'))
                ->with('auth_errors', ['Kolom pengaturan notifikasi belum tersedia di database ini.']);
        }

        WhatsappSetting::where('id', $request->post('id'))->update($payload);

        return redirect(url('admin/whatsapp'))->with('success', ['Data berhasil diupdate']);
    }

    public function whatsappUpdateNumber(Request $request)
    {
        $provider = $request->post('provider', 'wablas');
        $nama = $provider === 'meta'
            ? 'Meta Official'
            : ($request->post('nama') ?: 'Whatsapp Gateway');

        $phoneNumberId = $request->post('meta_phone_number_id') ?: $request->post('sender');

        if (empty($request->post('api_key')) || empty($provider === 'meta' ? $phoneNumberId : $request->post('sender'))) {
            return redirect(url('admin/whatsapp/edit/'.$request->post('id')))
                ->withInput()
                ->with('auth_errors', ['API Key / Access Token dan Sender / Phone Number ID wajib diisi']);
        }

        if ($request->post('mode') === 'on') {
            WhatsappSetting::where('mode', 'on')
                ->where('id', '!=', $request->post('id'))
                ->update(['mode' => 'off']);
        }

        $apiUrl = $provider === 'meta'
            ? WhatsAppGatewayResolver::encodeMetaSettings($this->metaSettingsFromRequest($request))
            : $request->post('api_url');

        // Untuk Meta, Phone Number ID disimpan di blob api_url — kolom sender (varchar 15) diisi penanda pendek.
        $sender = $provider === 'meta' ? 'meta' : $request->post('sender');

        if ($error = $this->gatewayColumnLimitError($apiUrl, $sender)) {
            return redirect(url('admin/whatsapp/edit/'.$request->post('id')))
                ->withInput()
                ->with('auth_errors', [$error]);
        }

        WhatsappSetting::where('id', $request->post('id'))->update([
            'nama' => $nama,
            'api_url' => $apiUrl,
            'api_key' => $request->post('api_key'),
            'sender' => $sender,
            'mode' => $request->post('mode') ?? 'off',
        ]);

        return redirect(url('admin/whatsapp'))->with('success', ['Data berhasil diupdate. '.($request->post('mode') === 'on' ? 'Gateway lain otomatis OFF.' : '')]);
    }

    private function gatewayColumnLimitError(?string $apiUrl, ?string $sender): ?string
    {
        // Kolom DB legacy: api_url varchar(255), sender varchar(15) — tidak boleh diubah (shared dengan CI4)
        if (strlen((string) $apiUrl) > 255) {
            return 'Setting terlalu panjang ('.strlen((string) $apiUrl).' karakter, maks 255). Persingkat verify token / nama template Meta.';
        }
        if (strlen((string) $sender) > 15) {
            return 'Sender / Phone Number ID lebih dari 15 karakter — kolom DB legacy hanya menampung 15. Gunakan Phone Number ID yang valid (maks 15 digit).';
        }

        return null;
    }

    private function metaSettingsFromRequest(Request $request): array
    {
        return [
            'graph_url' => $request->post('meta_graph_url') ?: $request->post('api_url') ?: 'https://graph.facebook.com/v20.0',
            'verify_token' => $request->post('meta_verify_token') ?: 'landaknet-meta-webhook',
            'waba_id' => $request->post('meta_waba_id') ?: '',
            // Phone Number ID Meta bisa 16+ digit → tidak muat di kolom sender varchar(15),
            // jadi disimpan di blob ini. Field form "sender" dipakai sebagai fallback input.
            'phone_number_id' => $request->post('meta_phone_number_id') ?: $request->post('sender') ?: '',
            'language' => $request->post('meta_language') ?: 'id',
            'templates' => [
                'tagihan' => $request->post('meta_template_tagihan') ?: 'notif_tagihan',
                'pengingat' => $request->post('meta_template_pengingat') ?: 'notif_pengingat',
                'terbayar' => $request->post('meta_template_terbayar') ?: 'notif_tagihan_terbayar',
                'pelanggan_baru' => $request->post('meta_template_pelanggan_baru') ?: 'notif_daftar_berhasil',
                'isolir' => $request->post('meta_template_isolir') ?: 'notif_isolir',
                'buka_isolir' => $request->post('meta_template_buka_isolir') ?: 'notif_buka_isolir',
            ],
        ];
    }

    public function whatsappMetaTemplates(Request $request)
    {
        $metaGateways = WhatsappSetting::all()->filter(fn ($row) => WhatsAppGatewayResolver::isMeta($row));
        $gateway = null;

        if ($request->query('gateway_id')) {
            $gateway = $metaGateways->firstWhere('id', (int) $request->query('gateway_id'));
        }

        if (! $gateway) {
            $gateway = $metaGateways->firstWhere('mode', 'on') ?? $metaGateways->first();
        }

        $templates = [];
        $businessAccountId = $request->query('business_account_id') ?: ($gateway ? WhatsAppGatewayResolver::metaWabaId($gateway) : '');
        $graphUrl = $request->query('graph_url') ?: ($gateway ? WhatsAppGatewayResolver::metaGraphUrl($gateway) : 'https://graph.facebook.com/v20.0');
        $accessToken = $request->query('access_token') ?: ($gateway->api_key ?? '');

        if ($businessAccountId && $accessToken) {
            $api = new WhatsAppMetaApi($graphUrl, $accessToken);
            $response = $api->templates($businessAccountId);
            $templates = $response['data'] ?? [];

            if (isset($response['error']['message'])) {
                // now() (bukan flash) — error hanya untuk request ini, tidak bocor ke halaman berikutnya.
                session()->now('auth_errors', [$response['error']['message']]);
            }
        }

        return view('admin.gateway.whatsapp.meta-templates', [
            'title' => 'Meta WhatsApp Templates',
            'templates' => $templates,
            'localTemplates' => WhatsAppMetaApi::defaultTemplateDefinitions(),
            'gateway' => $gateway,
            'metaGateways' => $metaGateways,
            'businessAccountId' => $businessAccountId,
            'graphUrl' => $graphUrl,
            'hasAccessToken' => $accessToken !== '',
        ] + $this->websiteData());
    }

    public function whatsappMetaCreateTemplates(Request $request)
    {
        if (! $request->post('business_account_id')) {
            return redirect(url('admin/whatsapp/meta/templates'))->with('auth_errors', ['WABA ID kosong. Pilih gateway Meta atau isi WABA ID lalu klik Cek Template dulu.']);
        }

        $gateway = $request->input('gateway_id') ? WhatsappSetting::find((int) $request->input('gateway_id')) : null;
        $graphUrl = $request->post('graph_url') ?: ($gateway ? WhatsAppGatewayResolver::metaGraphUrl($gateway) : 'https://graph.facebook.com/v20.0');
        $accessToken = $request->post('access_token') ?: ($gateway->api_key ?? '');
        $wabaId = $request->post('business_account_id');
        $language = $request->post('language', 'id');

        if (empty($accessToken)) {
            return redirect(url('admin/whatsapp/meta/templates'))->with('auth_errors', ['Access Token kosong. Isi manual atau pilih gateway Meta.']);
        }

        $api = new WhatsAppMetaApi($graphUrl, $accessToken);

        $defaults = WhatsAppMetaApi::defaultTemplateDefinitions();

        // Template yang SUDAH ada di Meta dilewati (Meta menolak nama duplikat).
        // Membuat tombol Create idempoten: hanya template baru (mis. _v2) yang
        // benar-benar dikirim, template lama tidak diganggu.
        $existing = collect((array) data_get($api->templates($wabaId), 'data', []))
            ->pluck('name')
            ->filter()
            ->all();

        $results = [];
        $errors = [];
        $skipped = [];
        foreach ($defaults as $tpl) {
            if (in_array($tpl['name'], $existing, true)) {
                $skipped[] = $tpl['name'];

                continue;
            }

            $response = $api->createTemplateFromDefinition($wabaId, $tpl, $language);
            if (isset($response['id'])) {
                $results[] = $tpl['name'].' (OK)';
            } else {
                $errors[] = $tpl['name'].': '.($response['error']['message'] ?? 'Gagal');
            }
        }

        $redirect = redirect(url('admin/whatsapp/meta/templates?business_account_id='.$wabaId));

        $successMsgs = [];
        if ($results !== []) {
            $successMsgs[] = 'Berhasil membuat '.count($results).' template ke Meta: '.implode(', ', $results);
        }
        if ($skipped !== []) {
            $successMsgs[] = 'Dilewati karena sudah ada di Meta: '.implode(', ', $skipped);
        }

        if ($errors !== []) {
            return $redirect->with('auth_errors', $errors)->with('success', $successMsgs);
        }

        return $redirect->with('success', $successMsgs !== [] ? $successMsgs : ['Tidak ada template baru untuk dibuat.']);
    }

    public function whatsappMetaTest(Request $request)
    {
        $gateway = WhatsAppGatewayResolver::active();

        if (! $gateway || ! WhatsAppGatewayResolver::isMeta($gateway)) {
            return redirect(url('admin/whatsapp'))->with('auth_errors', ['Meta Official gateway tidak aktif']);
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        $response = $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $request->post('number'), $request->post('message'));
        $result = json_decode($response, true);

        if (isset($result['messages'][0]['id'])) {
            return redirect(url('admin/whatsapp'))->with('success', ['Test Meta berhasil: '.$result['messages'][0]['id']]);
        }

        $error = $result['error']['message'] ?? 'Gagal mengirim pesan Meta';

        return redirect(url('admin/whatsapp'))->with('auth_errors', [$error]);
    }

    public function whatsappTemplate()
    {
        return view('admin.gateway.whatsapp.template', [
            'title' => 'Whatsapp Gateway',
            'content' => TemplateMessage::all(),
        ] + $this->websiteData());
    }

    public function whatsappUpdateTemplate(Request $request)
    {
        TemplateMessage::where('id', $request->post('id'))->update([
            'notif_tagihan' => $request->post('notif_tagihan'),
            'notif_pengingat' => $request->post('notif_pengingat'),
            'notif_tagihan_terbayar' => $request->post('notif_tagihan_terbayar'),
            'notif_pelanggan_baru' => $request->post('notif_pelanggan_baru'),
        ]);

        return redirect(url('admin/whatsapp/template/message'))->with('success', ['Data berhasil diupdate']);
    }

    public function whatsappUpdate(Request $request)
    {
        $sender = $request->post('sender');
        $notif = stripslashes(strip_tags(htmlspecialchars((string) $request->post('notif'), ENT_QUOTES)));
        $terbayar = stripslashes(strip_tags(htmlspecialchars((string) $request->post('terbayar'), ENT_QUOTES)));
        $register = stripslashes(strip_tags(htmlspecialchars((string) $request->post('register'), ENT_QUOTES)));

        // Tabel `whatsapp` (legacy) tidak ada di skema ini — pengaturan aktif
        // memakai `whatsapp_setting`. Guard agar tidak SQL error "table not found".
        if (! Schema::hasTable('whatsapp')) {
            return redirect(url('admin/gateway/whatsapp'))->with('auth_errors', ['Fitur ini belum tersedia di database ini (tabel whatsapp tidak ada).']);
        }

        $update = Whatsapp::where('sender', $sender)->update([
            'tagihan_otomatis' => $notif,
            'tagihan_terbayar' => $terbayar,
            'register' => $register,
        ]);

        if ($update) {
            return redirect(url('admin/gateway/whatsapp'))->with('success', ['Data berhasil disimpan']);
        }

        return redirect(url('admin/gateway/whatsapp'))->with('auth_errors', ['Error Database']);
    }

    public function whatsappUpdateAddon(Request $request)
    {
        // Tabel `whatsapp` (legacy) tidak ada di skema ini — pengaturan aktif
        // memakai `whatsapp_setting`. Guard agar tidak SQL error "table not found".
        if (! Schema::hasTable('whatsapp')) {
            return redirect(url('admin/gateway/whatsapp'))->with('auth_errors', ['Fitur ini belum tersedia di database ini (tabel whatsapp tidak ada).']);
        }

        Whatsapp::where('sender', $request->post('sender'))->update([
            'sebelum' => $request->post('sebelum'),
            'notif_tagihan' => $request->post('notif_tagihan') ?? 'off',
            'notif_terbayar' => $request->post('notif_terbayar') ?? 'off',
        ]);

        return redirect(url('admin/gateway/whatsapp'))->with('success', ['Data berhasil disimpan']);
    }

    public function whatsappSendMessage(Request $request)
    {
        $datawhatsapp = WhatsAppGatewayResolver::active();

        if (! $datawhatsapp) {
            return redirect(url('admin/gateway/whatsapp/setup'));
        }

        $response = WhatsAppNotifier::sendText($request->post('received'), $request->post('message'));

        $result = json_decode($response, true);

        if ($result && isset($result['status']) && $result['status'] === false) {
            $msg = $result['msg'] ?? '';

            if (isset($result['errors'])) {
                if (is_string($result['errors'])) {
                    $errors = [$result['errors']];
                } elseif (is_array($result['errors']) && isset($result['errors']['message'])) {
                    $errors = $result['errors']['message'];
                } else {
                    $errors = [];
                }
            } else {
                $errors = [];
            }

            $errorMessage = 'Pengiriman gagal: '.$msg;

            if (! empty($errors)) {
                $errorMessage .= ' '.implode(', ', (array) $errors);
            }

            return redirect(url('admin/whatsapp/message/text-message'))->with('auth_errors', [$errorMessage]);
        }

        return redirect(url('admin/whatsapp/message/text-message'))->with('success', ['Pengiriman berhasil : Sukses mengirimkan pesan tersebut ']);
    }
}
