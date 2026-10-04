<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KavlingPeta extends Model
{
    use HasFactory;

    protected $table = 'kavling_peta';

    protected $fillable = [
        'id_lokasi',
        'nama_cluster',
        'id_perusahaan',
        'kode_kavling',
        'panjang',
        'lebar',
        'luas_tanah',
        'tipe_bangunan',
        'luas_bangunan',
        'rincian_biaya',
        'hrg_jual',
        'id_rumah_sikumbang',
        'jenis_map',
        'map',
        'matrik',
        'keterangan',
    ];

    protected $casts = [
        'rincian_biaya' => 'array',
    ];

    protected $appends = ['total_harga'];

    public function lokasi()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi', 'id');
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id_kavling', 'id')
            ->where(fn ($query) => $query->where('stt_arsip', 0)->orWhereNull('stt_arsip'));
    }

    public function activeBookings()
    {
        return $this->hasMany(PengajuanHold::class, 'id_kavling')->where('stt_reg', 1);
    }

    public function scopeAvailable($query, $exceptBookingId = null)
    {
        return $query->whereDoesntHave('customer')->whereDoesntHave('activeBookings', function ($booking) use ($exceptBookingId) {
            if ($exceptBookingId !== null) {
                $booking->where('pengajuan_hold.id', '!=', $exceptBookingId);
            }
        });
    }

    public function scopeWithBookingState($query)
    {
        return $query->withExists(['activeBookings as is_booked']);
    }

    public function getIsBookedAttribute($value): bool
    {
        return $value !== null ? (bool) $value : $this->activeBookings()->exists();
    }

    public function getSalesStatusAttribute(): string
    {
        return $this->customer
            ? ($this->customer->progres->status_progres ?? 'Terjual')
            : ($this->is_booked ? 'Booking' : 'Tersedia');
    }
    public function progres()
    {
        return $this->belongsTo(ProgresListPenjualan::class, 'id_status_progres', 'id');
    }
    public function perusahaan() {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }
    public function listrikAir()
    {
        return $this->hasOne(ListrikAir::class, 'id_kavling', 'id');
    }
    public function BphtbSsp()
    {
        return $this->hasOne(BphtbSsp::class, 'id_kavling', 'id');
    }
    public function BalikNama()
    {
        return $this->hasOne(BalikNama::class, 'id_kavling', 'id');
    }

    public function getTotalHargaAttribute(): int
    {
        return collect($this->rincian_biaya ?? [])->sum('nilai');
    }

    public function getRincianHargaHtmlAttribute(): string
    {
        if (!$this->rincian_biaya) return '<span class="text-muted">-</span>';

        $html = '<div class="w-100">';
        foreach ($this->rincian_biaya as $item) {
            $nama  = $item['nama'] ?? '-';
            $nilai = (int) ($item['nilai'] ?? 0);
            if ($nilai <= 0) continue;
            $html .= '<div class="d-flex justify-content-between harga-format">
                <span>' . e($nama) . ' : </span>
                <span>Rp. ' . number_format($nilai, 0, ',', '.') . '</span>
            </div>';
        }
        $html .= '</div>';
        return $html;
    }

    // Backward-compatible accessors: baca dari JSON, fallback ke kolom DB lama
    public function getHrgJualAttribute()
    {
        $nilai = $this->getNilaiByNama('Harga Rumah');
        if ($nilai === 0 && $this->rincian_biaya === null) {
            return (int) ($this->attributes['hrg_jual'] ?? 0);
        }
        return $nilai;
    }

    public function getBiayaSuratAttribute()
    {
        $nilai = $this->getNilaiByNama('Biaya Surat');
        if ($nilai === 0 && $this->rincian_biaya === null) {
            return (int) ($this->attributes['biaya_surat'] ?? 0);
        }
        return $nilai;
    }

    public function getPeningkatanMutuAttribute()
    {
        $nilai = $this->getNilaiByNama('Peningkatan Mutu');
        if ($nilai === 0 && $this->rincian_biaya === null) {
            return (int) ($this->attributes['peningkatan_mutu'] ?? 0);
        }
        return $nilai;
    }

    private function getNilaiByNama(string $nama): int
    {
        if (!$this->rincian_biaya) return 0;
        $item = collect($this->rincian_biaya)->firstWhere('nama', $nama);
        return (int) ($item['nilai'] ?? 0);
    }

    public $timestamps = false;
}
