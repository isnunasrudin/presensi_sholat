<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RombonganBelajar extends Model
{
    use HasFactory;

    public $table = 'rombongan_belajar';

    protected $fillable = [
        'nama_rombel',
        'tingkat',
        'tahun_angkatan',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the full class name in format "X-A", "XII-B", etc.
     */
    public function getNamaKelasAttribute()
    {
        return ($this->tingkat ? $this->tingkat : '') . ($this->nama_rombel ? '-' . $this->nama_rombel : '');
    }
}
