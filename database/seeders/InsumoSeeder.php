<?php

namespace Database\Seeders;

use App\Models\Insumo;
use Illuminate\Database\Seeder;

class InsumoSeeder extends Seeder
{
    public function run(): void
    {
        $insumos = [
            ['nombre' => 'Tela Algodon Premium', 'sku' => 'INS-TELA-001', 'unidad' => 'metro', 'tipo_registro' => 'UNIDAD', 'cantidad_compra' => 120, 'costo_total_compra' => 1740000, 'stock_actual' => 420, 'stock_minimo' => 120, 'costo_unitario' => 14500, 'proveedor' => 'Textiles Andinos', 'activo' => true],
            ['nombre' => 'Hilo Poliester Negro', 'sku' => 'INS-HILO-002', 'unidad' => 'carrete', 'tipo_registro' => 'UNIDAD', 'cantidad_compra' => 80, 'costo_total_compra' => 712000, 'stock_actual' => 95, 'stock_minimo' => 60, 'costo_unitario' => 8900, 'proveedor' => 'Suministros Nova', 'activo' => true],
            ['nombre' => 'Botones Metalicos', 'sku' => 'INS-BTN-003', 'unidad' => 'unidad', 'tipo_registro' => 'PAQUETE', 'unidades_por_paquete' => 40, 'costo_total_compra' => 50000, 'stock_actual' => 520, 'stock_minimo' => 160, 'costo_unitario' => 1250, 'proveedor' => 'Accesorios Urban', 'activo' => true],
            ['nombre' => 'Cremallera YKK 20cm', 'sku' => 'INS-CRM-004', 'unidad' => 'unidad', 'tipo_registro' => 'UNIDAD', 'cantidad_compra' => 100, 'costo_total_compra' => 350000, 'stock_actual' => 340, 'stock_minimo' => 120, 'costo_unitario' => 3500, 'proveedor' => 'Zippers Co', 'activo' => true],
            ['nombre' => 'Etiqueta Bordada', 'sku' => 'INS-ETQ-005', 'unidad' => 'unidad', 'tipo_registro' => 'PAQUETE', 'unidades_por_paquete' => 500, 'costo_total_compra' => 325000, 'stock_actual' => 800, 'stock_minimo' => 250, 'costo_unitario' => 650, 'proveedor' => 'Label Studio', 'activo' => true],
        ];

        foreach ($insumos as $item) {
            Insumo::updateOrCreate(['sku' => $item['sku']], $item);
        }
    }
}
