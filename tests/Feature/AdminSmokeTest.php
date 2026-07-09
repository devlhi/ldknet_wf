<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Smoke test: semua route GET tanpa parameter (non-destruktif) harus balas
 * 200/302, bukan 500. Pakai DB asli (shared, read-only ke halaman list saja).
 */
class AdminSmokeTest extends TestCase
{
    public function test_safe_get_routes_do_not_error(): void
    {
        // Hanya jalan terhadap DB landaknet asli:
        // DB_CONNECTION=mysql DB_DATABASE=landaknet php artisan test --filter=AdminSmokeTest
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Smoke test butuh DB mysql landaknet (default suite pakai sqlite :memory:).');
        }

        $user = User::whereIn('level', ['admin', 'developer'])->firstOrFail();

        $routes = array_filter(array_map('trim', file(storage_path('safe_routes.txt'))));
        $failures = [];

        foreach ($routes as $uri) {
            try {
                $response = $this->actingAs($user)->get('/'.ltrim($uri, '/'));
                $status = $response->getStatusCode();
                if ($status >= 500) {
                    $msg = '';
                    $exception = $response->exception ?? null;
                    if ($exception) {
                        $msg = ' — '.get_class($exception).': '.mb_substr($exception->getMessage(), 0, 160);
                    }
                    $failures[] = "[$status] $uri$msg";
                }
            } catch (\Throwable $e) {
                $failures[] = "[EXC] $uri — ".get_class($e).': '.mb_substr($e->getMessage(), 0, 160);
            }
        }

        $this->assertSame([], $failures, "Route error:\n".implode("\n", $failures));
    }
}
