<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLogViewerTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->string('nama')->nullable();
            $table->string('nomor')->nullable();
            $table->string('password');
            $table->integer('balance')->default(0);
            $table->string('level');
            $table->integer('verify_account')->default(1);
            $table->string('status_account')->default('Active');
        });
        Schema::create('website', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });
        DB::table('website')->insert(['title' => 'LandakNet Test', 'logo' => '']);

        $this->logDirectory = storage_path('framework/testing/log-viewer-'.bin2hex(random_bytes(6)));
        mkdir($this->logDirectory, 0777, true);
        config()->set('logging.channels.single.path', $this->logDirectory.'/laravel.log');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDirectory.'/*') ?: [] as $path) {
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->logDirectory)) {
            rmdir($this->logDirectory);
        }

        parent::tearDown();
    }

    public function test_only_authorized_roles_can_open_log_viewer(): void
    {
        $this->get('/admin/logs')->assertRedirect('/auth/login');

        $this->actingAs($this->user('user'))
            ->get('/admin/logs')
            ->assertRedirect('/user');

        file_put_contents($this->logDirectory.'/laravel.log', 'Log admin');
        $this->actingAs($this->user('admin'))
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('Log admin');
    }

    public function test_viewer_lists_only_allowed_logs_and_escapes_contents(): void
    {
        file_put_contents($this->logDirectory.'/laravel.log', '<script>alert("x")</script>');
        file_put_contents($this->logDirectory.'/laravel-2026-07-21.log', 'Daily log');
        file_put_contents($this->logDirectory.'/other.log', 'Rahasia lain');

        $this->actingAs($this->user('developer'))
            ->get('/admin/logs?file=laravel.log')
            ->assertOk()
            ->assertSee('laravel.log')
            ->assertSee('laravel-2026-07-21.log')
            ->assertDontSee('other.log')
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_viewer_reads_only_bounded_tail_of_large_log(): void
    {
        file_put_contents(
            $this->logDirectory.'/laravel.log',
            'OLD-MARKER'.str_repeat('A', 600000).PHP_EOL.'NEW-MARKER'
        );

        $response = $this->actingAs($this->user('admin'))
            ->get('/admin/logs?file=laravel.log')
            ->assertOk()
            ->assertDontSee('OLD-MARKER')
            ->assertSee('NEW-MARKER');

        $this->assertLessThan(550000, strlen($response->getContent()));
    }

    public function test_viewer_keeps_the_tail_of_an_oversized_single_line(): void
    {
        file_put_contents($this->logDirectory.'/laravel.log', str_repeat('A', 600000).'SINGLE-LINE-END');

        $this->actingAs($this->user('admin'))
            ->get('/admin/logs?file=laravel.log')
            ->assertOk()
            ->assertSee('SINGLE-LINE-END');
    }

    public function test_viewer_selects_the_most_recent_log_by_timestamp(): void
    {
        $current = $this->logDirectory.'/laravel.log';
        $daily = $this->logDirectory.'/laravel-2026-07-21.log';
        file_put_contents($current, 'CURRENT-LOG');
        file_put_contents($daily, 'DAILY-LOG');
        touch($current, strtotime('2025-12-31 23:59:59'));
        touch($daily, strtotime('2026-01-01 00:00:00'));

        $this->actingAs($this->user('admin'))
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('DAILY-LOG')
            ->assertDontSee('CURRENT-LOG');
    }

    public function test_clear_truncates_only_selected_allowed_log(): void
    {
        file_put_contents($this->logDirectory.'/laravel.log', 'Hapus saya');
        file_put_contents($this->logDirectory.'/laravel-2026-07-21.log', 'Pertahankan saya');
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post('/admin/logs/clear', ['file' => 'laravel.log'])
            ->assertRedirect('/admin/logs?file=laravel.log')
            ->assertSessionHas('success');

        $this->assertSame('', file_get_contents($this->logDirectory.'/laravel.log'));
        $this->assertSame('Pertahankan saya', file_get_contents($this->logDirectory.'/laravel-2026-07-21.log'));
    }

    public function test_invalid_file_cannot_be_cleared(): void
    {
        file_put_contents($this->logDirectory.'/laravel.log', 'Tetap ada');

        $this->actingAs($this->user('admin'))
            ->post('/admin/logs/clear', ['file' => '../laravel.log'])
            ->assertRedirect('/admin/logs')
            ->assertSessionHas('auth_errors');

        $this->assertSame('Tetap ada', file_get_contents($this->logDirectory.'/laravel.log'));
        $this->get('/admin/logs/clear')->assertStatus(405);
    }

    public function test_symlink_log_is_not_listed_or_cleared(): void
    {
        if (! function_exists('symlink')) {
            $this->markTestSkipped('Symlink tidak didukung.');
        }

        $outside = dirname($this->logDirectory).'/outside-'.bin2hex(random_bytes(4)).'.log';
        file_put_contents($outside, 'Jangan disentuh');
        $link = $this->logDirectory.'/laravel.log';

        if (! @symlink($outside, $link)) {
            unlink($outside);
            $this->markTestSkipped('Pembuatan symlink tidak diizinkan.');
        }

        try {
            $admin = $this->user('admin');
            $this->actingAs($admin)->get('/admin/logs')->assertOk()->assertDontSee('Jangan disentuh');
            $this->actingAs($admin)
                ->post('/admin/logs/clear', ['file' => 'laravel.log'])
                ->assertRedirect('/admin/logs')
                ->assertSessionHas('auth_errors');
            $this->assertSame('Jangan disentuh', file_get_contents($outside));
        } finally {
            if (is_link($link)) {
                unlink($link);
            }
            if (is_file($outside)) {
                unlink($outside);
            }
        }
    }

    private function user(string $level): User
    {
        $id = DB::table('users')->insertGetId([
            'email' => $level.'@example.test',
            'nama' => ucfirst($level).' Test',
            'nomor' => '0800000000',
            'password' => bcrypt('password'),
            'balance' => 0,
            'level' => $level,
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);

        return User::findOrFail($id);
    }
}
