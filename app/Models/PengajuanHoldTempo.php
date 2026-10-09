<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanHoldTempo extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_hold_tempo';

    protected $fillable = [
        'no_registrasi',
        'tgl_booking',
        'nama_lengkap',
        'nik',
        'no_telp',
        'email',
        'alamat_ktp',
        'alamat_domisili',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'npwp',
        'pekerjaan',
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

        'foto_ktp',
        'foto_npwp',
        'foto_kk',
        'foto_bpjs',
        'foto_pemohon',
        'foto_ktp_p',
        'booking_fee',
        'file_bukti',
        'berkas_booking',
        'id_marketing',
        'id_lokasi',
        'id_kavling',
        'hrg_jual',
        'biaya_surat',
        'peningkatan_mutu',
        'total_harga',
        'jenis_perumahan',
        'jenis_pembelian',
        'pembayaran_cash',
        'an_surat_cash',
        'dp_cash_b',
        'termin_x_cash_b',
        'dp_kpr',
        'lama_cicilan_kpr',
        'cicilan_kpr',
        'tgl_tempo_cicilan_1',
        'an_surat_kpr',
        'stt_reg',
        'id_pengajuan_hold',
        'id_user',
    ];

    protected $casts = ['berkas_booking' => 'array'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function marketing()
    {
        return $this->belongsTo(MarketingOffline::class, 'id_marketing');
    }

    public function lokasi()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi');
    }

    public function kavling()
    {
        return $this->belongsTo(KavlingPeta::class, 'id_kavling');
    }
}
