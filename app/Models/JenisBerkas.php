<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisBerkas extends Model
{
    protected $table = 'jenis_berkas';

    public $timestamps = false;

    protected $fillable = ['nama', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];
}
