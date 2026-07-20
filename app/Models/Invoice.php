<?php

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $table = 'invoice';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'idpel', 'idpel');
    }

    /**
     * Nama advisory lock yang dipakai semua jalur pembuatan/konfirmasi pembayaran.
     * Hash menjaga nama lock tetap pendek walau code legacy lebih panjang.
     */
    public static function paymentLockName(string $code): string
    {
        return self::lockName('invoice', $code);
    }

    public static function periodLockName(string $idpel, string $period): string
    {
        return self::lockName('period', $idpel.'|'.$period);
    }

    public static function customerAccessLockName(string $idpel): string
    {
        return self::lockName('access', $idpel);
    }

    private static function lockName(string $scope, string $value): string
    {
        // GET_LOCK() menerima nama maksimal 64 karakter pada versi MySQL yang
        // dipakai deployment. SHA-1 tetap memberi ruang collision yang memadai
        // untuk lock proses lokal dan menjaga total nama di bawah batas itu.
        return 'landaknet_'.$scope.'_'.hash('sha1', $value);
    }

    /**
     * SQLite tidak menyediakan advisory lock MySQL. Transaksi + row lock tetap
     * dipakai pada test; production MySQL mengoordinasikan request gateway/manual.
     */
    public static function acquireNamedLock(string $name, int $timeout = 10): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        $lock = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$name, $timeout]);

        return $lock && (int) $lock->acquired === 1;
    }

    public static function releaseNamedLock(string $name): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
    }

    public static function acquirePaymentLock(string $code, int $timeout = 10): bool
    {
        return self::acquireNamedLock(self::paymentLockName($code), $timeout);
    }

    public static function releasePaymentLock(string $code): void
    {
        self::releaseNamedLock(self::paymentLockName($code));
    }

    /**
     * Reference tanpa waktu expiry yang valid dianggap masih aktif agar manual
     * payment tidak mengambil alih transaksi gateway yang statusnya tidak jelas.
     */
    public function hasActiveGatewayTransaction(?int $now = null): bool
    {
        if (trim((string) $this->reference) === '') {
            return false;
        }

        $expires = trim((string) $this->exppay);
        if ($expires === '') {
            return true;
        }

        $expiredAt = DateTimeImmutable::createFromFormat(
            '!d-m-Y H:i:s',
            $expires,
            new \DateTimeZone(config('app.timezone', 'Asia/Jakarta')),
        );
        $errors = DateTimeImmutable::getLastErrors();

        if (! $expiredAt || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return true;
        }

        $currentTimestamp = $now ?? now(config('app.timezone', 'Asia/Jakarta'))->getTimestamp();

        return $expiredAt->getTimestamp() > $currentTimestamp;
    }
}
