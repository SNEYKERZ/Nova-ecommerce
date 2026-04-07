<?php

namespace Database\Seeders;

use App\Models\Insumo;
use Illuminate\Database\Seeder;

class InsumoSeeder extends Seeder
{
    public function run(): void
    {
        $insumos = [
            ['nombre' => 'Tela Algodon Premium', 'sku' => 'INS-TELA-001', 'unidad' => 'metro', 'stock_actual' => 420, 'stock_minimo' => 120, 'costo_unitario' => 14500, 'proveedor' => 'Textiles Andinos', 'activo' => true],
            ['nombre' => 'Hilo Poliester Negro', 'sku' => 'INS-HILO-002', 'unidad' => 'carrete', 'stock_actual' => 95, 'stock_minimo' => 60, 'costo_unitario' => 8900, 'proveedor' => 'Suministros Nova', 'activo' => true],
            ['nombre' => 'Botones Metalicos', 'sku' => 'INS-BTN-003', 'unidad' => 'paquete', 'stock_actual' => 52, 'stock_minimo' => 40, 'costo_unitario' => 12500, 'proveedor' => 'Accesorios Urban', 'activo' => true],
            ['nombre' => 'Cremallera YKK 20cm', 'sku' => 'INS-CRM-004', 'unidad' => 'unidad', 'stock_actual' => 34, 'stock_minimo' => 30, 'costo_unitario' => 3500, 'proveedor' => 'Zippers Co', 'activo' => true],
            ['nombre' => 'Etiqueta Bordada', 'sku' => 'INS-ETQ-005', 'unidad' => 'unidad', 'stock_actual' => 800, 'stock_minimo' => 250, 'costo_unitario' => 650, 'proveedor' => 'Label Studio', 'activo' => true],
        ];

        foreach ($insumos as $item) {
            Insumo::updateOrCreate(['sku' => $item['sku']], $item);
        }
    }
}
