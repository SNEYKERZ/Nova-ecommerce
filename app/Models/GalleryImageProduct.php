<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImageProduct extends Model
{
    use HasFactory;

    protected $table = 'gallery_image_products';

    protected $fillable = [
        'gallery_image_id',
        'producto_id',
        'orden',
    ];

    public function galleryImage(): BelongsTo
    {
        return $this->belongsTo(GalleryImage::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
