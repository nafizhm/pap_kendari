<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BerkasBooking extends Model
{
    use SoftDeletes;

    protected $table = 'berkas_booking';
    public $timestamps = false;
    protected $fillable = ['kode', 'nama', 'urutan', 'wajib', 'aktif'];
    protected $casts = ['wajib' => 'boolean', 'aktif' => 'boolean'];

    public const LEGACY = [
        'foto_pemohon' => 'Foto Pemohon',
        'foto_ktp' => 'Foto KTP',
        'foto_npwp' => 'Foto NPWP',
        'foto_kk' => 'Foto KK',
        'foto_bpjs' => 'Foto BPJS',
        'foto_ktp_p' => 'Foto KTP Pasangan',
        'file_bukti' => 'Bukti Transfer Booking',
        'file_sppr' => 'File SPPR',
    ];

    public function inputName(): string
    {
        return array_key_exists($this->kode, self::LEGACY) ? $this->kode : 'berkas_booking_files[' . $this->kode . ']';
    }

    public function inputKey(): string
    {
        return array_key_exists($this->kode, self::LEGACY) ? $this->kode : 'berkas_booking_files.' . $this->kode;
    }

    public function filename(?PengajuanHold $booking): ?string
    {
        if (! $booking) {
            return null;
        }
        return array_key_exists($this->kode, self::LEGACY)
            ? $booking->{$this->kode}
            : ($booking->berkas_booking[$this->kode]['file'] ?? null);
    }
}
