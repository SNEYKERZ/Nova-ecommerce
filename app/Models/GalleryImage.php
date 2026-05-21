<?php

namespace App\Models;

use App\Traits\HasMediaUrls;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImage extends Model
{
    use HasFactory, HasTenant, HasMediaUrls;

    protected $table = 'gallery_images';

    protected $fillable = [
        'gallery_id',
        'imagen',
        'orden',
        'aspect_ratio',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(GalleryImageProduct::class)->orderBy('orden');
    }

    public function getImagenUrlAttribute(): string
    {
        return $this->mediaUrl($this->imagen);
    }
}
