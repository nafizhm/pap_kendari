<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customer';
    protected $fillable = [
        'kode_customer',
        'tanggal_verif',
        'id_lokasi',
        'id_kavling',
        'hrg_jual',
        'biaya_surat',
        'peningkatan_mutu',
        'total_harga',
        'estimasi_plafon',
        'sbum',
        'id_status_progres',
        'nama_lengkap',
        'nik',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'alamat_ktp',
        'alamat_domisili',
        'status_pernikahan',
        'nama_p',
        'nik_p',
        'no_bpjs_kes',
        'nama_saudara',
        'no_telp_saudara',
        'kontak_darurat_1',
        'nama_pemilik_kontak_darurat_1',
        'keterangan_kontak_darurat_1',
        'kontak_darurat_2',
        'nama_pemilik_kontak_darurat_2',
        'keterangan_kontak_darurat_2',
        'kontak_darurat_3',
        'nama_pemilik_kontak_darurat_3',
        'keterangan_kontak_darurat_3',
        'kontak_darurat_4',
        'nama_pemilik_kontak_darurat_4',
        'keterangan_kontak_darurat_4',
        'kontak_darurat_5',
        'nama_pemilik_kontak_darurat_5',
        'keterangan_kontak_darurat_5',

        'jenis_perumahan',
        'no_telp',
        'email',
        'npwp',
        'pekerjaan',
        'id_marketing',
        'jenis_pembelian',
        'an_surat_cash',
        'termin_x_cash_b',
        'stt_arsip',
    ];

    public function persyaratan()
    {
        return $this->hasOne(PersyaratanLegal::class, 'id_customer');
    }

    public function marketing()
    {
        return $this->belongsTo(MarketingOffline::class, 'id_marketing');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'id_bank');
    }

    public function lokasi()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi');
    }

    public function kavling()
    {
        return $this->belongsTo(KavlingPeta::class, 'id_kavling');
    }

    public function progres()
    {
        return $this->belongsTo(ProgresListPenjualan::class, 'id_status_progres');
    }

    public function scopeWithProgressDates(Builder $query): Builder
    {
        return $query->addSelect('customer.*')->addSelect([
            'tanggal_progres_wawancara' => DB::table('wawancara')
                ->selectRaw('MAX(tgl_wawancara)')->whereColumn('id_customer', 'customer.id'),
            'tanggal_progres_sp3k' => DB::table('wawancara_sp3k')
                ->join('wawancara', 'wawancara.id', '=', 'wawancara_sp3k.id_wawancara')
                ->selectRaw('MAX(wawancara_sp3k.tgl_terbit_sp3k)')
                ->where('wawancara_sp3k.status', 1)->whereColumn('wawancara.id_customer', 'customer.id'),
            'tanggal_progres_akad' => DB::table('detail_akad')
                ->join('akad', 'akad.id', '=', 'detail_akad.id_akad')
                ->selectRaw('MAX(akad.tgl_akad)')->where('detail_akad.status', 2)
                ->whereColumn('detail_akad.id_customer', 'customer.id'),
            'tanggal_progres_bast' => DB::table('bast')
                ->selectRaw('MAX(tanggal_bast)')->whereColumn('id_customer', 'customer.id'),
        ]);
    }

    public function getTanggalProgresAttribute(): ?string
    {
        return match (strtoupper(trim($this->progres->status_progres ?? ''))) {
            'BOOKING FEE' => $this->tanggal_verif,
            'WAWANCARA' => $this->tanggal_progres_wawancara,
            'SP3K' => $this->tanggal_progres_sp3k,
            'AKAD' => $this->tanggal_progres_akad,
            'SERAH TERIMA', 'BAST' => $this->tanggal_progres_bast,
            default => null,
        };
    }

    public function lokasiKavling()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi');
    }

    public function kavlingPeta()
    {
        return $this->belongsTo(KavlingPeta::class, 'id_kavling');
    }

    public function akadDetail()
    {
        return $this->hasMany(AkadDetail::class, 'id_customer');
    }

    public function piutangs()
    {
        return $this->hasMany(Piutang::class, 'id_customer');
    }

    public function pemasukans()
    {
        return $this->hasMany(Pemasukan::class, 'id_customer');
    }

    public function wawancara()
    {
        return $this->hasMany(Wawancara::class, 'id_customer');
    }

    public $timestamps = false;
}
