<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $table = 'document_templates';

    protected $fillable = [
        'nama',
        'kode',
        'deskripsi',
        'file_path',
        'engine',
        'konten',
        'is_active',
        'detected_variables',
        'selected_variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'detected_variables' => 'array',
        'selected_variables' => 'array',
    ];
}
