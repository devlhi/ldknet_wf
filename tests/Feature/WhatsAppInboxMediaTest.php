<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaInboxMessage;
use App\Models\WhatsappSetting;
use App\Support\WhatsAppGatewayResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppInboxMediaTest extends TestCase
{
    private string $uploadedImagePath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->uploadedImagePath = storage_path('framework/testing-meta-image.png');
        file_put_contents($this->uploadedImagePath, $this->pngContents());

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

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 40);
            $table->string('nama', 50);
            $table->string('nomor', 15);
            $table->text('password');
            $table->integer('balance');
            $table->string('level');
            $table->integer('verify_account');
            $table->string('status_account');
        });

        Schema::table('wa_inbox_messages', function (Blueprint $table) {
            $table->index(['from_number', 'direction']);
        });

        Schema::table('whatsapp_setting', function (Blueprint $table) {
            $table->index(['type', 'mode']);
        });

        \DB::table('webhook')->insert(['status' => 'on']);
        WhatsappSetting::create([
            'nama' => 'Meta Official',
            'api_url' => WhatsAppGatewayResolver::encodeMetaSettings([
                'graph_url' => 'https://graph.facebook.com/v20.0',
                'verify_token' => 'verify-token',
            ]),
            'api_key' => 'meta-test-token',
            'sender' => 'meta',
            'type' => 'blast',
            'mode' => 'on',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->uploadedImagePath) && is_file($this->uploadedImagePath)) {
            unlink($this->uploadedImagePath);
        }

        parent::tearDown();
    }

    public function test_image_webhook_stores_private_media_and_admin_can_preview_it(): void
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        Http::fake([
            'https://graph.facebook.com/v20.0/media-1' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/media/temp-1',
                'mime_type' => 'image/gif',
            ]),
            'https://lookaside.fbsbx.com/media/temp-1' => Http::response($image, 200, ['Content-Type' => 'image/gif']),
        ]);

        $payload = $this->imagePayload();
        $this->postJson('webhook/whatsapp/meta', $payload)->assertOk()->assertJson(['status' => 'ok']);

        $message = WaInboxMessage::where('meta_message_id', 'wamid.image-1')->firstOrFail();
        $this->assertSame('image', $message->message_type);
        $this->assertSame('[Image] Bukti pembayaran', $message->body);
        Storage::disk('local')->assertExists(WaInboxMessage::mediaPath($message->meta_message_id));

        $admin = User::create([
            'email' => 'admin@example.test',
            'nama' => 'Admin',
            'nomor' => '628111111111',
            'password' => 'secret',
            'balance' => 0,
            'level' => 'admin',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);

        $mediaUrl = 'admin/whatsapp/inbox/media/'.$message->id;
        $this->get($mediaUrl)->assertRedirect('/auth/login');
        $this->actingAs($admin)->get($mediaUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->actingAs($admin)
            ->getJson('admin/whatsapp/inbox/poll?number=628123456789')
            ->assertOk()
            ->assertJsonPath('messages.0.media_url', url($mediaUrl));
    }

    public function test_duplicate_webhook_retries_missing_image_without_duplicating_message(): void
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        Http::fake([
            'https://graph.facebook.com/v20.0/media-1' => Http::sequence()
                ->push([], 500)
                ->push([
                    'url' => 'https://lookaside.fbsbx.com/media/temp-1',
                    'mime_type' => 'image/gif',
                ]),
            'https://lookaside.fbsbx.com/media/temp-1' => Http::response($image, 200, ['Content-Type' => 'image/gif']),
        ]);

        $this->postJson('webhook/whatsapp/meta', $this->imagePayload())->assertOk();
        Storage::disk('local')->assertMissing(WaInboxMessage::mediaPath('wamid.image-1'));

        $this->postJson('webhook/whatsapp/meta', $this->imagePayload())->assertOk();

        $this->assertSame(1, WaInboxMessage::where('meta_message_id', 'wamid.image-1')->count());
        Storage::disk('local')->assertExists(WaInboxMessage::mediaPath('wamid.image-1'));
        Http::assertSentCount(3);
    }

    public function test_duplicate_image_webhook_does_not_duplicate_message_or_redownload_file(): void
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        Http::fake([
            'https://graph.facebook.com/v20.0/media-1' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/media/temp-1',
                'mime_type' => 'image/gif',
            ]),
            'https://lookaside.fbsbx.com/media/temp-1' => Http::response($image, 200, ['Content-Type' => 'image/gif']),
        ]);

        $this->postJson('webhook/whatsapp/meta', $this->imagePayload())->assertOk();
        $this->postJson('webhook/whatsapp/meta', $this->imagePayload())->assertOk();

        $this->assertSame(1, WaInboxMessage::where('meta_message_id', 'wamid.image-1')->count());
        Http::assertSentCount(2);
    }

    public function test_admin_can_reply_text_to_user_during_open_reply_window(): void
    {
        $admin = $this->createAdmin();
        WaInboxMessage::create([
            'from_number' => '628123456789',
            'from_name' => 'Pelanggan Test',
            'direction' => 'in',
            'body' => 'Mohon dibantu',
            'message_type' => 'text',
            'meta_message_id' => 'wamid.inbound-text-window',
            'status' => 'received',
            'created_at' => now()->subHour(),
        ]);
        Http::fake([
            'https://graph.facebook.com/v20.0/meta/messages' => Http::response([
                'messages' => [['id' => 'wamid.outbound-text-1']],
            ]),
        ]);

        $this->actingAs($admin)->post('admin/whatsapp/inbox/send', [
            'number' => '628123456789',
            'message' => 'Baik, segera kami cek.',
            'signature_mode' => 'auto',
        ])->assertRedirect('admin/whatsapp/inbox?number=628123456789')
            ->assertSessionHas('success');

        $message = WaInboxMessage::where('meta_message_id', 'wamid.outbound-text-1')->firstOrFail();
        $this->assertSame('out', $message->direction);
        $this->assertSame('text', $message->message_type);
        $this->assertSame("Baik, segera kami cek.\n\n~Admin", $message->body);
        $this->assertSame($admin->id, $message->sent_by);

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v20.0/meta/messages'
            && $request['to'] === '628123456789'
            && $request['type'] === 'text'
            && data_get($request->data(), 'text.body') === "Baik, segera kami cek.\n\n~Admin");
    }

    public function test_admin_cannot_reply_text_after_reply_window_expires(): void
    {
        $admin = $this->createAdmin();
        WaInboxMessage::create([
            'from_number' => '628123456789',
            'direction' => 'in',
            'body' => 'Pesan lama',
            'message_type' => 'text',
            'status' => 'received',
            'created_at' => now()->subDay()->subSecond(),
        ]);
        Http::fake();

        $this->actingAs($admin)->from('admin/whatsapp/inbox?number=628123456789')
            ->post('admin/whatsapp/inbox/send', [
                'number' => '628123456789',
                'message' => 'Balasan terlambat',
                'signature_mode' => 'auto',
            ])
            ->assertRedirect('admin/whatsapp/inbox?number=628123456789')
            ->assertSessionHas('auth_errors');

        Http::assertNothingSent();
        $this->assertSame(0, WaInboxMessage::where('direction', 'out')->count());
    }

    public function test_admin_can_send_image_during_open_reply_window_and_preview_private_copy(): void
    {
        $admin = $this->createAdmin();
        WaInboxMessage::create([
            'from_number' => '628123456789',
            'from_name' => 'Pelanggan Test',
            'direction' => 'in',
            'body' => 'Halo admin',
            'message_type' => 'text',
            'meta_message_id' => 'wamid.inbound-window',
            'status' => 'received',
            'created_at' => now()->subHour(),
        ]);

        Http::fake([
            'https://graph.facebook.com/v20.0/meta/media' => Http::response(['id' => 'uploaded-media-1']),
            'https://graph.facebook.com/v20.0/meta/messages' => Http::response([
                'messages' => [['id' => 'wamid.outbound-image-1']],
            ]),
        ]);

        $image = new UploadedFile($this->uploadedImagePath, 'bukti.png', 'image/png', null, true);
        $this->actingAs($admin)->post('admin/whatsapp/inbox/send-image', [
            'number' => '628123456789',
            'caption' => 'Mohon dicek',
            'image' => $image,
        ])->assertRedirect('admin/whatsapp/inbox?number=628123456789');

        $message = WaInboxMessage::where('meta_message_id', 'wamid.outbound-image-1')->firstOrFail();
        $this->assertSame('out', $message->direction);
        $this->assertSame('image', $message->message_type);
        $this->assertSame('[Image] Mohon dicek', $message->body);
        $this->assertSame($admin->id, $message->sent_by);
        Storage::disk('local')->assertExists(WaInboxMessage::mediaPath('wamid.outbound-image-1'));
        $this->actingAs($admin)->get('admin/whatsapp/inbox/media/'.$message->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v20.0/meta/media'
            && str_contains($request->body(), 'name="messaging_product"')
            && str_contains($request->body(), 'whatsapp')
            && str_contains($request->body(), 'name="type"')
            && str_contains($request->body(), 'image/png')
            && str_contains($request->body(), 'filename="reply.png"'));
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v20.0/meta/messages'
            && $request['to'] === '628123456789'
            && $request['type'] === 'image'
            && data_get($request->data(), 'image.id') === 'uploaded-media-1'
            && data_get($request->data(), 'image.caption') === 'Mohon dicek');
    }

    public function test_admin_cannot_send_image_after_reply_window_expires(): void
    {
        $admin = $this->createAdmin();
        WaInboxMessage::create([
            'from_number' => '628123456789',
            'direction' => 'in',
            'body' => 'Pesan lama',
            'message_type' => 'text',
            'status' => 'received',
            'created_at' => now()->subDay()->subSecond(),
        ]);
        Http::fake();

        $this->actingAs($admin)->from('admin/whatsapp/inbox?number=628123456789')
            ->post('admin/whatsapp/inbox/send-image', [
                'number' => '628123456789',
                'image' => new UploadedFile($this->uploadedImagePath, 'lama.png', 'image/png', null, true),
            ])
            ->assertRedirect('admin/whatsapp/inbox?number=628123456789')
            ->assertSessionHas('auth_errors');

        Http::assertNothingSent();
        $this->assertSame(0, WaInboxMessage::where('direction', 'out')->count());
    }

    private function pngContents(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLIsAAAAABJRU5ErkJggg==');
    }

    private function createAdmin(): User
    {
        return User::create([
            'email' => 'admin@example.test',
            'nama' => 'Admin',
            'nomor' => '628111111111',
            'password' => 'secret',
            'balance' => 0,
            'level' => 'admin',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }

    private function imagePayload(): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [[
                            'wa_id' => '628123456789',
                            'profile' => ['name' => 'Pelanggan Test'],
                        ]],
                        'messages' => [[
                            'from' => '628123456789',
                            'id' => 'wamid.image-1',
                            'type' => 'image',
                            'image' => [
                                'id' => 'media-1',
                                'caption' => 'Bukti pembayaran',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
