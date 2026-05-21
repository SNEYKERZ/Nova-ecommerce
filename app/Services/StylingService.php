<?php

namespace App\Services;

use App\Models\Store;

/**
 * Servicio centralizado para gestión de colores y estilos dinámicos.
 * Maneja:
 * - Colores por defecto del sistema
 * - Cálculo automático de contraste
 * - Validación de colores hex
 */
class StylingService
{
    // Colores por defecto del sistema
    const DEFAULT_BG_COLOR = '#ffffff';
    const DEFAULT_NAVBAR_COLOR = '#fff';
    const DEFAULT_FOOTER_COLOR = '#e8e6e3';
    const DEFAULT_TEXT_DARK = '#111111';
    const DEFAULT_TEXT_LIGHT = '#ffffff';

    /**
     * Obtiene los colores por defecto del sistema.
     */
    public static function getDefaultColors(): array
    {
        return [
            'bg_color' => self::DEFAULT_BG_COLOR,
            'navbar_color' => self::DEFAULT_NAVBAR_COLOR,
            'footer_color' => self::DEFAULT_FOOTER_COLOR,
        ];
    }

    /**
     * Valida que un color sea un hex válido (#RGB, #RRGGBB, #RRGGBBAA).
     *
     * @param string $color Color en formato hex
     * @return bool True si es válido
     */
    public static function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}|[A-Fa-f0-9]{8})$/', $color);
    }

    /**
     * Normaliza un color hex a formato #RRGGBB.
     *
     * @param string $color Color en formato #RGB o #RRGGBB
     * @return string Color normalizado a #RRGGBB
     */
    public static function normalizeHex(string $color): string
    {
        if (!self::isValidHexColor($color)) {
            return self::DEFAULT_BG_COLOR;
        }

        if (strlen($color) === 4) {
            return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return substr($color, 0, 7);
    }

    /**
     * Calcula la luminancia relativa de un color (WCAG 2.0).
     * Retorna valor entre 0 (muy oscuro) y 1 (muy claro).
     *
     * @param string $hexColor Color en formato #RRGGBB
     * @return float Luminancia entre 0 y 1
     */
    public static function getLuminance(string $hexColor): float
    {
        $hex = str_replace('#', '', $hexColor);

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Determina si se debe usar texto claro o oscuro sobre un color de fondo.
     * Basado en luminancia WCAG 2.0.
     *
     * @param string $backgroundColor Color de fondo en formato #RRGGBB
     * @param float $threshold Umbral de luminancia (default 0.5, más alto = más sensible a oscuro)
     * @return string 'light' para texto claro, 'dark' para texto oscuro
     */
    public static function getTextColor(string $backgroundColor, float $threshold = 0.5): string
    {
        $hex = str_replace('#', '', $backgroundColor);

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return 'dark';
        }

        $luminance = self::getLuminance('#' . $hex);

        return $luminance > $threshold ? 'dark' : 'light';
    }

    /**
     * Retorna el color hex de texto recomendado para un fondo dado.
     *
     * @param string $backgroundColor Color de fondo
     * @return string Color hex para texto (#ffffff o #111111)
     */
    public static function getTextColorHex(string $backgroundColor): string
    {
        return self::getTextColor($backgroundColor) === 'light'
            ? self::DEFAULT_TEXT_LIGHT
            : self::DEFAULT_TEXT_DARK;
    }

    /**
     * Obtiene los estilos CSS dinámicos basados en colores del store.
     * Incluye colores de texto automático basados en contraste.
     *
     * @param Store|null $store
     * @return array Array con estilos CSS
     */
    public static function getStoreCssVariables(?Store $store): array
    {
        $defaults = self::getDefaultColors();

        $bgColor = self::normalizeHex($store?->bg_color ?? $defaults['bg_color']);
        $navbarColor = self::normalizeHex($store?->navbar_color ?? $defaults['navbar_color']);
        $footerColor = self::normalizeHex($store?->footer_color ?? $defaults['footer_color']);

        $navbarTextColor = self::getTextColorHex($navbarColor);
        $footerTextColor = self::getTextColorHex($footerColor);
        $bgTextColor = self::getTextColorHex($bgColor);

        return [
            '--store-bg' => $bgColor,
            '--store-bg-text' => $bgTextColor,
            '--store-navbar' => $navbarColor,
            '--store-navbar-text' => $navbarTextColor,
            '--store-footer' => $footerColor,
            '--store-footer-text' => $footerTextColor,
        ];
    }

    /**
     * Valida y sanitiza un color hex.
     * Si no es válido, retorna el color por defecto.
     *
     * @param string|null $color Color a validar
     * @param string $default Color por defecto si no es válido
     * @return string Color validado
     */
    public static function validateColor(?string $color, string $default = self::DEFAULT_BG_COLOR): string
    {
        if (!$color || !self::isValidHexColor($color)) {
            return $default;
        }

        return self::normalizeHex($color);
    }
}
