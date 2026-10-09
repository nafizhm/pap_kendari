<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersyaratanLegal extends Model
{
    use HasFactory;

    protected $table = 'persyaratan_legal';

    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'IPH',
        'SHGB',
        'SSP',
        'BPHTB',
        'SIKUMBANG',
        'DAFTAR_SIKASEP',
        'FOTO_SIKASEP',
        'TRILOGI',
        'status_jenis_berkas',
        'file_jenis_berkas',
        'catatan_kekurangan',
        'percakapan_wa',
    ];

    protected $casts = [
        'status_jenis_berkas' => 'array',
        'file_jenis_berkas' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
}
