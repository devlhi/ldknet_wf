<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaInboxMessage;
use App\Models\WhatsappSetting;
use App\Support\WhatsAppGatewayResolver;
use App\Support\WhatsAppNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppGatewayFlowsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('webhook', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status');
        });

        Schema::create('whatsapp_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama');
            $table->text('api_url');
            $table->text('api_key');
            $table->string('sender');
            $table->string('type');
            $table->string('mode');
        });

        Schema::create('wa_inbox_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('from_number', 30);
            $table->string('from_name', 150)->nullable();
            $table->string('direction');
            $table->text('body');
            $table->string('message_type', 30)->default('text');
            $table->string('meta_message_id', 100)->nullable();
            $table->string('status', 30)->default('sent');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('website', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 80);
            $table->string('nama', 80);
            $table->string('nomor', 30);
            $table->text('password');
            $table->integer('balance');
            $table->string('level');
            $table->integer('verify_account');
            $table->string('status_account');
        });

        WhatsappSetting::create([
            'nama' => 'Meta Official',
            'api_url' => WhatsAppGatewayResolver::encodeMetaSettings([
                'graph_url' => 'https://graph.facebook.com/v20.0',
                'phone_number_id' => 'meta-phone-id',
            ]),
            'api_key' => 'meta-test-token',
            'sender' => 'meta',
            'type' => 'blast',
            'mode' => 'on',
        ]);
        \DB::table('website')->insert(['title' => 'LandakNet', 'logo' => '']);
        \DB::table('webhook')->insert(['status' => 'off']);
    }

    public function test_legacy_webhook_accepts_plain_messages_without_token(): void
    {
        $legacy = WhatsappSetting::first();
        $legacy->update(['api_url' => 'https://legacy.example.test']);
        $this->createUser('member@example.test', '081111111111', 'user');

        $this->postJson('webhook/whatsapp', [
            'from' => '628111111111',
            'message' => 'Halo',
        ])->assertSuccessful();
    }

    public function test_legacy_webhook_rejects_admin_commands_without_token(): void
    {
        $legacy = WhatsappSetting::first();
        $legacy->update(['api_url' => 'https://legacy.example.test']);

        $this->postJson('webhook/whatsapp', [
            'from' => '628111111111',
            'message' => '/regisuser nama Test email t@t.com alamat X nomor 08x paket 1 router 1',
        ])->assertStatus(403)
            ->assertJson(['status' => 'invalid token']);
    }

    public function test_legacy_webhook_is_ignored_while_meta_gateway_is_active(): void
    {
        $this->postJson('webhook/whatsapp', [
            'from' => '628111111111',
            'message' => '/regisuser',
        ])->assertOk()->assertJson([
            'status' => 'ignored',
            'message' => 'Legacy gateway inactive',
        ]);
    }

    public function test_legacy_response_parser_rejects_error_shapes(): void
    {
        $legacy = new WhatsappSetting([
            'nama' => 'Legacy Gateway',
            'api_url' => 'https://gateway.example.test',
        ]);

        $this->assertFalse(WhatsAppNotifier::responseSucceeded([], $legacy));
        $this->assertFalse(WhatsAppNotifier::responseSucceeded(['status' => 'error'], $legacy));
        $this->assertFalse(WhatsAppNotifier::responseSucceeded(['success' => false], $legacy));
        $this->assertFalse(WhatsAppNotifier::responseSucceeded(['errors' => ['message' => 'Rejected']], $legacy));
        $this->assertTrue(WhatsAppNotifier::responseSucceeded(['status' => true], $legacy));
        $this->assertTrue(WhatsAppNotifier::responseSucceeded(['status' => 'success'], $legacy));
    }

    public function test_provider_detection_uses_configuration_not_gateway_name(): void
    {
        $legacy = new WhatsappSetting([
            'nama' => 'Meta Compatible Gateway',
            'api_url' => 'https://gateway.example.test',
        ]);

        $this->assertFalse(WhatsAppGatewayResolver::isMeta($legacy));
        $this->assertTrue(WhatsAppGatewayResolver::isMeta(WhatsappSetting::firstOrFail()));
    }

    public function test_webhook_setting_page_does_not_expose_meta_token_or_secret_blob(): void
    {
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');

        $this->actingAs($admin)->get('admin/webhook')
            ->assertOk()
            ->assertDontSee('meta-test-token')
            ->assertDontSee('meta###')
            ->assertSee('********');
    }

    public function test_admin_can_delete_whatsapp_gateway_without_touching_services(): void
    {
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');
        $gateway = WhatsappSetting::firstOrFail();

        $this->actingAs($admin)->post('admin/whatsapp/delete/'.$gateway->id)
            ->assertRedirect('admin/whatsapp')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('whatsapp_setting', ['id' => $gateway->id]);
    }

    public function test_admin_text_send_reports_meta_error_instead_of_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/meta-phone-id/messages' => Http::response([
                'error' => ['message' => 'Invalid recipient'],
            ], 400),
        ]);
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');

        $this->actingAs($admin)->from('admin/whatsapp/message/text-message')
            ->post('admin/whatsapp/message/text-message/send', [
                'received' => '081234567890',
                'message' => 'Tes pesan',
            ])
            ->assertRedirect('admin/whatsapp/message/text-message')
            ->assertSessionHas('auth_errors')
            ->assertSessionMissing('success');

        $this->assertSame('failed', WaInboxMessage::firstOrFail()->status);
    }

    public function test_meta_send_normalizes_number_without_prefix(): void
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/meta-phone-id/messages' => Http::response([
                'messages' => [['id' => 'wamid.normalized']],
            ]),
        ]);
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');

        $this->actingAs($admin)->post('admin/whatsapp/message/text-message/send', [
            'received' => '81234567890',
            'message' => 'Tes normalisasi',
        ])->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request['to'] === '6281234567890');
        $this->assertSame('6281234567890', WaInboxMessage::firstOrFail()->from_number);
    }

    public function test_broadcast_reports_failures_and_does_not_pollute_inbox(): void
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/meta-phone-id/messages' => Http::sequence()
                ->push(['messages' => [['id' => 'wamid.broadcast-ok']]])
                ->push(['error' => ['message' => 'Invalid recipient']], 400),
        ]);
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');
        $admin->update(['status_account' => 'Inactive']);
        $this->createUser('one@example.test', '081200000001', 'user');
        $this->createUser('two@example.test', '081200000002', 'user');

        $this->actingAs($admin)->post('admin/cms/broadcast/whatsapp/send', [
            'message' => 'Informasi layanan',
        ])->assertRedirect('admin/cms/broadcast/whatsapp')
            ->assertSessionHas('auth_errors', fn ($errors) => str_contains($errors[0], 'Berhasil: 1, gagal: 1'));

        $this->assertSame(0, WaInboxMessage::count());
    }

    public function test_media_broadcast_rejects_non_https_url_and_unknown_type(): void
    {
        $admin = $this->createUser('admin@example.test', '628111111111', 'admin');

        $this->actingAs($admin)->from('admin/cms/broadcast/whatsapp')
            ->post('admin/cms/broadcast/sendmedia', [
                'url' => 'http://localhost/file.pdf',
                'type' => 'pdf',
                'caption' => 'Dokumen',
            ])
            ->assertRedirect('admin/cms/broadcast/whatsapp')
            ->assertSessionHasErrors(['url', 'type']);
    }

    private function createUser(string $email, string $number, string $level): User
    {
        return User::create([
            'email' => $email,
            'nama' => ucfirst($level),
            'nomor' => $number,
            'password' => 'secret',
            'balance' => 0,
            'level' => $level,
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }
}
