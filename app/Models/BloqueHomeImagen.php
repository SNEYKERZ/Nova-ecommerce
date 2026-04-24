<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueHomeImagen extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'bloque_home_imagenes';

    protected $fillable = [
        'bloque_home_id',
        'imagen',
        'url_destino',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function bloqueHome()
    {
        return $this->belongsTo(BloqueHome::class);
    }

    /**
     * Sin global scopes para admin
     */
    public static function getAllForBloque(int $bloqueId)
    {
        return static::withoutGlobalScopes()
            ->where('bloque_home_id', $bloqueId)
            ->orderBy('orden')
            ->get();
    }
}