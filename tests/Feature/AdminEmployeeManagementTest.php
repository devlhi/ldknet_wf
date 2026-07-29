<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminEmployeeManagementTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 40);
            $table->string('nama', 50);
            $table->string('nomor', 15)->nullable();
            $table->string('password');
            $table->integer('balance')->default(0);
            $table->string('level');
            $table->integer('verify_account')->default(1);
            $table->string('status_account')->default('Active');
        });
        Schema::create('hr_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->string('status')->default('hadir');
            $table->string('foto_in')->nullable();
            $table->string('foto_out')->nullable();
            $table->timestamps();
        });

        $this->admin = $this->user('admin', 'admin@example.test');
    }

    public function test_admin_can_update_employee_name_email_and_password(): void
    {
        $employee = $this->user('technician', 'old@example.test');
        $oldHash = $employee->password;

        $this->actingAs($this->admin)
            ->post('/admin/karyawan/update/'.$employee->id, [
                'nama' => 'Nama Karyawan Baru',
                'email' => 'new@example.test',
                'password' => 'password-baru',
            ])
            ->assertRedirect('/admin/karyawan')
            ->assertSessionHas('success', ['Data karyawan berhasil diperbarui']);

        $employee->refresh();
        $this->assertSame('Nama Karyawan Baru', $employee->nama);
        $this->assertSame('new@example.test', $employee->email);
        $this->assertNotSame($oldHash, $employee->password);
        $this->assertTrue(Hash::check('password-baru', $employee->password));
    }

    public function test_empty_password_keeps_current_hash_and_duplicate_email_is_rejected(): void
    {
        $employee = $this->user('technician', 'employee@example.test');
        $other = $this->user('technician', 'other@example.test');
        $oldHash = $employee->password;

        $this->actingAs($this->admin)
            ->post('/admin/karyawan/update/'.$employee->id, [
                'nama' => 'Nama Tanpa Ganti Password',
                'email' => $employee->email,
                'password' => '',
            ])
            ->assertRedirect('/admin/karyawan');

        $this->assertSame($oldHash, $employee->fresh()->password);

        $this->actingAs($this->admin)
            ->post('/admin/karyawan/update/'.$employee->id, [
                'nama' => 'Nama Ditolak',
                'email' => $other->email,
                'password' => '',
            ])
            ->assertRedirect('/admin/karyawan')
            ->assertSessionHas('auth_errors', ['Email sudah terdaftar']);

        $this->assertSame('Nama Tanpa Ganti Password', $employee->fresh()->nama);
        $this->assertSame('employee@example.test', $employee->fresh()->email);
    }

    public function test_admin_can_delete_employee_attendance_history_and_photos(): void
    {
        $employee = $this->user('technician', 'delete@example.test');
        $photoIn = 'employee-delete-in.jpg';
        $photoOut = 'employee-delete-out.jpg';
        $photoDirectory = public_path('data/absensi');
        if (! is_dir($photoDirectory)) {
            mkdir($photoDirectory, 0777, true);
        }
        file_put_contents($photoDirectory.'/'.$photoIn, 'photo-in');
        file_put_contents($photoDirectory.'/'.$photoOut, 'photo-out');

        DB::table('hr_attendances')->insert([
            'user_id' => $employee->id,
            'tanggal' => '2026-07-29',
            'status' => 'hadir',
            'foto_in' => $photoIn,
            'foto_out' => $photoOut,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->actingAs($this->admin)
                ->post('/admin/karyawan/delete/'.$employee->id)
                ->assertRedirect('/admin/karyawan')
                ->assertSessionHas('success', ['Data karyawan berhasil dihapus']);

            $this->assertDatabaseMissing('users', ['id' => $employee->id]);
            $this->assertDatabaseMissing('hr_attendances', ['user_id' => $employee->id]);
            $this->assertFileDoesNotExist($photoDirectory.'/'.$photoIn);
            $this->assertFileDoesNotExist($photoDirectory.'/'.$photoOut);
        } finally {
            @unlink($photoDirectory.'/'.$photoIn);
            @unlink($photoDirectory.'/'.$photoOut);
        }
    }

    public function test_non_employee_accounts_cannot_be_updated_or_deleted_from_employee_routes(): void
    {
        $finance = $this->user('finance', 'finance@example.test');

        $this->actingAs($this->admin)
            ->post('/admin/karyawan/update/'.$finance->id, [
                'nama' => 'Diubah',
                'email' => 'changed@example.test',
                'password' => 'password-baru',
            ])
            ->assertRedirect('/admin/karyawan')
            ->assertSessionHas('auth_errors', ['Karyawan tidak ditemukan']);

        $this->actingAs($this->admin)
            ->post('/admin/karyawan/delete/'.$finance->id)
            ->assertRedirect('/admin/karyawan')
            ->assertSessionHas('auth_errors', ['Karyawan tidak ditemukan']);

        $this->assertDatabaseHas('users', [
            'id' => $finance->id,
            'email' => 'finance@example.test',
            'level' => 'finance',
        ]);
    }

    private function user(string $level, string $email): User
    {
        return User::create([
            'email' => $email,
            'nama' => ucfirst($level).' Test',
            'nomor' => '081234567890',
            'password' => Hash::make('password-lama'),
            'balance' => 0,
            'level' => $level,
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }
}
