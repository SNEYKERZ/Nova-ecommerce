# Script de Optimización de Imágenes a WebP
# Uso: .\scripts\optimize-images.ps1
# Requiere: ImageMagick instalado (en Laragon ya viene incluido)

param(
    [string]$Path = "public/images",
    [string]$Quality = "80",
    [bool]$ConvertOriginals = $false
)

$ErrorActionPreference = "Stop"
$magickPath = "magick"

# Verificar que ImageMagick esté disponible
try {
    $null = & $magickPath --version
} catch {
    Write-Error "ImageMagick no está instalado o no está en el PATH"
    exit 1
}

Write-Host "=== Optimizador de Imágenes a WebP ===" -ForegroundColor Cyan
Write-Host "Directorio: $Path"
Write-Host "Calidad: $Quality%"
Write-Host ""

$imageFormats = @("*.jpg", "*.jpeg", "*.png")
$count = 0
$skipped = 0

foreach ($format in $imageFormats) {
    $images = Get-ChildItem -Path $Path -Filter $format -Recurse -ErrorAction SilentlyContinue

    foreach ($image in $images) {
        $webpPath = $image.FullName -replace '\.[^.]+$', '.webp'

        if (Test-Path $webpPath) {
            Write-Host "[SKIP] $($image.Name) → WebP ya existe" -ForegroundColor Yellow
            $skipped++
            continue
        }

        try {
            Write-Host "[CONV] Convirtiendo: $($image.Name)..." -ForegroundColor Green
            & $magickPath convert "$($image.FullName)" -quality $Quality "$webpPath"

            $originalSize = [math]::Round((Get-Item $image.FullName).Length / 1KB, 2)
            $webpSize = [math]::Round((Get-Item $webpPath).Length / 1KB, 2)
            $savings = [math]::Round(((1 - $webpSize / $originalSize) * 100), 1)

            Write-Host "       Tamaño: $($originalSize)KB → $($webpSize)KB (Ahorro: $savings%)" -ForegroundColor Cyan
            $count++
        } catch {
            Write-Error "Error al convertir $($image.Name): $_"
        }
    }
}

Write-Host ""
Write-Host "=== Resumen ===" -ForegroundColor Cyan
Write-Host "Convertidas: $count imágenes"
Write-Host "Omitidas: $skipped imágenes (ya existen como WebP)"
Write-Host ""
Write-Host "✓ Optimización completada" -ForegroundColor Green
