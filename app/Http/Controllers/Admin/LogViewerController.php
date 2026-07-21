<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LogViewerController extends Controller
{
    private const MAX_BYTES = 524288;

    public function index(Request $request): Response
    {
        $files = $this->logFiles();
        $selected = $this->selectedFile($request, $files);
        $file = $selected !== null ? $files[$selected] : null;

        $response = response()->view('admin.logs.index', [
            'title' => 'Laravel Logs',
            'files' => $files,
            'selected' => $selected,
            'contents' => $file !== null ? $this->readTail($file) : '',
            'maxBytes' => self::MAX_BYTES,
        ] + $this->websiteData());

        return $response
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function clear(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:80'],
        ]);
        $files = $this->logFiles();
        $selected = (string) $validated['file'];

        if (! isset($files[$selected])) {
            return redirect('admin/logs')->with('auth_errors', ['File log tidak valid atau sudah tidak tersedia.']);
        }

        $file = $files[$selected];
        $handle = @fopen($file['path'], 'r+');
        if ($handle === false) {
            return redirect('admin/logs?file='.urlencode($selected))->with('auth_errors', ['File log tidak dapat dibuka untuk dibersihkan.']);
        }

        try {
            if (! $this->openedFileMatches($handle, $file)) {
                return redirect('admin/logs?file='.urlencode($selected))->with('auth_errors', ['File log berubah atau tidak aman untuk dibersihkan.']);
            }

            if (! flock($handle, LOCK_EX)) {
                return redirect('admin/logs?file='.urlencode($selected))->with('auth_errors', ['File log sedang digunakan. Coba lagi.']);
            }

            if (! $this->openedFileMatches($handle, $file)) {
                flock($handle, LOCK_UN);

                return redirect('admin/logs?file='.urlencode($selected))->with('auth_errors', ['File log berubah atau tidak aman untuk dibersihkan.']);
            }

            $cleared = ftruncate($handle, 0);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if (! $cleared) {
            return redirect('admin/logs?file='.urlencode($selected))->with('auth_errors', ['File log gagal dibersihkan.']);
        }

        return redirect('admin/logs?file='.urlencode($selected))->with('success', ["Log {$selected} berhasil dibersihkan."]);
    }

    /** @return array<string, array{name: string, path: string, size: int, modified_at: string, modified_timestamp: int, device: int, inode: int}> */
    private function logFiles(): array
    {
        $directory = $this->logDirectory();
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (glob($directory.DIRECTORY_SEPARATOR.'laravel*.log') ?: [] as $path) {
            if (! is_file($path) || is_link($path)) {
                continue;
            }

            $name = basename($path);
            if (! preg_match('/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/', $name)) {
                continue;
            }

            $realPath = realpath($path);
            if ($realPath === false || ! $this->isInsideDirectory($realPath, $directory)) {
                continue;
            }

            $stat = @stat($realPath);
            if ($stat === false || (int) ($stat['nlink'] ?? 0) !== 1) {
                continue;
            }

            $modifiedTimestamp = max(0, (int) ($stat['mtime'] ?? 0));
            $files[$name] = [
                'name' => $name,
                'path' => $realPath,
                'size' => max(0, (int) ($stat['size'] ?? 0)),
                'modified_at' => $modifiedTimestamp > 0
                    ? Carbon::createFromTimestamp($modifiedTimestamp)->format('d M Y H:i:s')
                    : '-',
                'modified_timestamp' => $modifiedTimestamp,
                'device' => (int) ($stat['dev'] ?? 0),
                'inode' => (int) ($stat['ino'] ?? 0),
            ];
        }

        uasort($files, fn (array $left, array $right): int => $right['modified_timestamp'] <=> $left['modified_timestamp']);

        return $files;
    }

    /** @param array<string, array{name: string, path: string, size: int, modified_at: string, modified_timestamp: int, device: int, inode: int}> $files */
    private function selectedFile(Request $request, array $files): ?string
    {
        $requested = trim((string) $request->query('file', ''));
        if ($requested !== '' && isset($files[$requested])) {
            return $requested;
        }

        return array_key_first($files);
    }

    private function logDirectory(): string
    {
        $configuredPath = (string) config('logging.channels.single.path', storage_path('logs/laravel.log'));

        return dirname($configuredPath);
    }

    private function isInsideDirectory(string $path, string $directory): bool
    {
        $realDirectory = realpath($directory);
        if ($realDirectory === false) {
            return false;
        }

        $prefix = Str::finish(str_replace('\\', '/', $realDirectory), '/');
        $normalizedPath = str_replace('\\', '/', $path);

        return str_starts_with($normalizedPath, $prefix);
    }

    /** @param array{name: string, path: string, size: int, modified_at: string, modified_timestamp: int, device: int, inode: int} $file */
    private function readTail(array $file): string
    {
        $handle = @fopen($file['path'], 'rb');
        if ($handle === false) {
            return '';
        }

        try {
            if (! $this->openedFileMatches($handle, $file)) {
                return '';
            }

            $stat = fstat($handle);
            $size = max(0, (int) ($stat['size'] ?? 0));
            $offset = max(0, $size - self::MAX_BYTES);
            if ($offset > 0) {
                fseek($handle, $offset);
            }

            $contents = (string) stream_get_contents($handle, self::MAX_BYTES);
            if ($offset > 0) {
                $firstNewline = strpos($contents, "\n");
                if ($firstNewline !== false) {
                    $contents = substr($contents, $firstNewline + 1);
                }
            }

            return $contents;
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle
     * @param  array{name: string, path: string, size: int, modified_at: string, modified_timestamp: int, device: int, inode: int}  $file
     */
    private function openedFileMatches($handle, array $file): bool
    {
        clearstatcache(true, $file['path']);
        if (is_link($file['path'])) {
            return false;
        }

        $pathStat = @stat($file['path']);
        $openedStat = fstat($handle);
        if ($pathStat === false || $openedStat === false || (int) ($openedStat['nlink'] ?? 0) !== 1) {
            return false;
        }

        return (int) ($pathStat['dev'] ?? -1) === (int) ($openedStat['dev'] ?? -2)
            && (int) ($pathStat['ino'] ?? -1) === (int) ($openedStat['ino'] ?? -2)
            && $file['device'] === (int) ($openedStat['dev'] ?? -2)
            && $file['inode'] === (int) ($openedStat['ino'] ?? -2);
    }

    /** @return array{titletext: string, logo: string} */
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }
}
