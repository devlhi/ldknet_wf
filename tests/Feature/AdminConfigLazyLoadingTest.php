<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminConfigLazyLoadingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('nomor')->nullable();
            $table->string('password');
            $table->string('level');
            $table->string('status_account')->default('Active');
            $table->integer('verify_account')->default(1);
        });
        Schema::create('website', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('coupon', fn (Blueprint $table) => $table->id());
        Schema::create('services', fn (Blueprint $table) => $table->id());
        Schema::create('router', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->nullable();
        });
        Schema::create('payment_method', fn (Blueprint $table) => $table->id());
        Schema::create('payment_cat', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
        });
        Schema::create('payment_gateway', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_default')->nullable();
        });
        Schema::create('whatsapp_setting', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('type')->nullable();
            $table->text('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('sender')->nullable();
            $table->string('mode')->nullable();
        });
        Schema::create('cron_setting', function (Blueprint $table): void {
            $table->id();
            $table->string('task');
            $table->string('label');
            $table->string('time');
            $table->boolean('enabled');
        });
        Schema::create('cron_log', function (Blueprint $table): void {
            $table->id();
            $table->string('task');
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('message')->nullable();
        });

        $this->user = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'nomor' => '0800000000',
            'password' => 'secret',
            'level' => 'admin',
            'status_account' => 'Active',
            'verify_account' => 1,
        ]);
        DB::table('whatsapp_setting')->insert([
            'nama' => 'Gateway Test',
            'type' => 'blast',
            'api_url' => 'https://example.test',
            'api_key' => 'token',
            'sender' => '0800000000',
            'mode' => 'on',
        ]);
    }

    public function test_admin_config_tables_are_eagerly_queried_without_activation_prompt(): void
    {
        $pages = [
            '/admin/manage/user' => 'from "users" where "level" in',
            '/admin/manage/coupon' => 'from "coupon"',
            '/admin/services' => 'from "services"',
            '/admin/gateway/payment/method' => 'from "payment_method"',
            '/admin/gateway/whatsapp' => 'from "whatsapp_setting"',
            '/admin/whatsapp' => 'from "whatsapp_setting"',
            '/admin/webhook' => 'from "whatsapp_setting"',
            '/admin/setting/cron' => 'from "cron_setting"',
        ];

        foreach ($pages as $uri => $queryNeedle) {
            $queries = $this->captureQueries(fn () => $this->actingAs($this->user)->get($uri)
                ->assertOk()
                ->assertDontSee('Tampilkan Data'));

            $this->assertTrue(
                collect($queries)->contains(fn (string $query): bool => str_contains($query, $queryNeedle)),
                "Expected row query for {$uri}:\n".implode("\n", $queries)
            );
        }
    }

    public function test_payment_switches_and_meta_templates_eagerly_load(): void
    {
        DB::table('payment_gateway')->insert([
            'name' => 'tripay', 'status' => 'enable', 'payment_default' => '1',
        ]);
        DB::table('whatsapp_setting')->insert([
            'nama' => 'Meta Official', 'type' => 'blast',
            'api_url' => 'meta###https%3A%2F%2Fgraph.facebook.com%2Fv20.0|verify|123456|id|notif_tagihan|notif_pengingat|notif_tagihan_terbayar|notif_daftar_berhasil|phone',
            'api_key' => 'token', 'sender' => 'meta', 'mode' => 'on',
        ]);

        $queries = $this->captureQueries(fn () => $this->actingAs($this->user)
            ->get('/admin/gateway/payment')->assertOk()->assertDontSee('Tampilkan Data'));
        $this->assertTrue(collect($queries)->contains(fn (string $query): bool => str_contains($query, 'payment_method')));

        Http::fake(['*' => Http::response(['data' => []])]);
        $queries = $this->captureQueries(fn () => $this->actingAs($this->user)
            ->get('/admin/whatsapp/meta/templates')->assertOk()->assertSee('Meta Official'));
        $this->assertTrue(collect($queries)->contains(fn (string $query): bool => str_contains($query, 'whatsapp_setting')));
        Http::assertSentCount(1);
    }

    private function captureQueries(callable $request): array
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });
        $request();

        return $queries;
    }
}
