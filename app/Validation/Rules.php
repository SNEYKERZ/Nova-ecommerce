<?php

namespace App\Validation;

/**
 * Reglas de validación centralizadas para reutilizar en controllers.
 * Mantiene consistencia de validación en todo el proyecto.
 */
class Rules
{
    /**
     * Reglas para validación de colores hex
     */
    public static function colorRules(): array
    {
        return [
            'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ];
    }

    /**
     * Mensaje de error para colores hex inválidos
     */
    public static function colorMessage(string $fieldName = 'El color'): string
    {
        return "{$fieldName} debe ser un valor hex válido (ej: #ffffff o #fff)";
    }

    /**
     * Reglas para validación de URLs de destino (banners, links, etc)
     * Acepta: URLs absolutas (http/https), rutas relativas, hashes
     */
    public static function urlRules(): array
    {
        return [
            'nullable',
            'string',
            'max:2048',
            'regex:/^(https?:\/\/.+|\/[a-z0-9\/_-]*|#[a-z0-9-]*)$/i',
        ];
    }

    /**
     * Mensaje de error para URLs inválidas
     */
    public static function urlMessage(string $fieldName = 'La URL'): string
    {
        return "{$fieldName} debe ser una URL válida (http://, https://, ruta relativa o #ancla)";
    }

    /**
     * Reglas para imágenes de productos y banners
     */
    public static function imageRules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:' . config('styling.uploads.max_size', 10240),
        ];
    }

    /**
     * Mensaje de error para imágenes inválidas
     */
    public static function imageMessage(): string
    {
        $maxSize = config('styling.uploads.max_size', 10240) / 1024;
        return "La imagen debe ser JPG, PNG, WEBP y menor a {$maxSize}MB";
    }

    /**
     * Reglas para múltiples imágenes
     */
    public static function imagesRules(int $maxFiles = 4): array
    {
        return [
            'nullable',
            'array',
            "max:{$maxFiles}",
        ];
    }

    /**
     * Reglas para cada archivo en array de imágenes
     */
    public static function imageItemRules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:' . config('styling.uploads.max_size', 10240),
        ];
    }

    /**
     * Reglas para slug de store
     */
    public static function slugRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9-]+$/',
            'unique:stores,slug',
        ];
    }

    /**
     * Mensaje para slug inválido
     */
    public static function slugMessage(): string
    {
        return 'El slug debe contener solo letras minúsculas, números y guiones';
    }

    /**
     * Reglas para nombres de tienda/entidades
     */
    public static function nameRules(): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:255',
        ];
    }

    /**
     * Reglas para dominios personalizados
     */
    public static function domainRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            'unique:stores,dominio',
        ];
    }

    /**
     * Mensaje para dominio inválido
     */
    public static function domainMessage(): string
    {
        return 'El dominio debe ser un nombre de dominio válido (ej: mitienda.com)';
    }

    /**
     * Reglas para teléfono/WhatsApp
     */
    public static function phoneRules(): array
    {
        return [
            'nullable',
            'string',
            'max:50',
            'regex:/^[\d\s\+\-\(\)]+$/',
        ];
    }
}
