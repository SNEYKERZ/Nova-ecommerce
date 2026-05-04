<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'resenas';

    protected $fillable = [
        'producto_id',
        'user_id',
        'nombre',
        'email',
        'calificacion',
        'comentario',
        'aprobada',
    ];

    protected $casts = [
        'aprobada' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
