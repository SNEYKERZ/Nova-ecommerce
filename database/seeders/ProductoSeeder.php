<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No hay tiendas. Ejecuta el StoreSeeder primero.');
            return;
        }

        Producto::ignoreTenantScope();

        // Productos para tienda demo (WebCaps — gorras y accesorios urbanos)
        $demoProductos = [
            ['referencia' => 'GORRA-BLK-001', 'nombre' => 'Gorra Classic Black', 'precio' => 59900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 7],
            ['referencia' => 'GORRA-TRK-002', 'nombre' => 'Gorra Trucker Sand', 'precio' => 65900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 39],
            ['referencia' => 'GORRA-SNP-003', 'nombre' => 'Gorra Snapback Royal', 'precio' => 72900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 5],
            ['referencia' => 'GORRA-DDY-004', 'nombre' => 'Gorra Dad Hat Olive', 'precio' => 54900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 14],
            ['referencia' => 'CAMISA-OVR-001', 'nombre' => 'Camiseta Oversize Beige', 'precio' => 89900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 8],
            ['referencia' => 'CAMISA-GRF-002', 'nombre' => 'Camiseta Graphic Noir', 'precio' => 109900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 16],
            ['referencia' => 'CAMISA-BSC-003', 'nombre' => 'Camiseta Basica Blanca', 'precio' => 69900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L', 'dias' => 45],
            ['referencia' => 'PANT-CRG-001', 'nombre' => 'Pantalon Cargo Arena', 'precio' => 159900, 'categoria' => 'PANTALONES', 'tallas' => '30,32,34,36', 'dias' => 9],
            ['referencia' => 'PANT-JNS-002', 'nombre' => 'Jeans Straight Mid Blue', 'precio' => 179900, 'categoria' => 'PANTALONES', 'tallas' => '28,30,32,34,36', 'dias' => 51],
            ['referencia' => 'PANT-JGG-003', 'nombre' => 'Jogger Essential Black', 'precio' => 119900, 'categoria' => 'PANTALONES', 'tallas' => 'S,M,L,XL', 'dias' => 23],
            ['referencia' => 'CHQ-DNM-001', 'nombre' => 'Chaqueta Denim Stone', 'precio' => 239900, 'categoria' => 'CHAQUETAS', 'tallas' => 'S,M,L', 'dias' => 5],
            ['referencia' => 'CHQ-BMB-002', 'nombre' => 'Chaqueta Bomber Olive', 'precio' => 219900, 'categoria' => 'CHAQUETAS', 'tallas' => 'M,L,XL', 'dias' => 31],
            ['referencia' => 'CHQ-PUF-003', 'nombre' => 'Chaqueta Puffer Cloud', 'precio' => 289900, 'categoria' => 'CHAQUETAS', 'tallas' => 'M,L,XL', 'dias' => 3],
            ['referencia' => 'BUZO-HDG-001', 'nombre' => 'Buzo Hoodie Granite', 'precio' => 149900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L,XL', 'dias' => 11],
            ['referencia' => 'BUZO-CRW-002', 'nombre' => 'Buzo Crewneck Urban', 'precio' => 139900, 'categoria' => 'BUZOS', 'tallas' => 'M,L,XL', 'dias' => 27],
        ];

        // Productos para tienda urbanstyle (ropa urbana y lifestyle)
        $urbanProductos = [
            ['referencia' => 'US-CAM-OVR-001', 'nombre' => 'Oversize Tee Cement', 'precio' => 79900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 2],
            ['referencia' => 'US-CAM-GRF-002', 'nombre' => 'Graphic Tee Skate', 'precio' => 99900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L', 'dias' => 10],
            ['referencia' => 'US-CAM-LIN-003', 'nombre' => 'Linen Shirt Ecru', 'precio' => 129900, 'categoria' => 'CAMISETAS', 'tallas' => 'M,L,XL', 'dias' => 25],
            ['referencia' => 'US-PANT-BAG-001', 'nombre' => 'Baggy Pants Black', 'precio' => 149900, 'categoria' => 'PANTALONES', 'tallas' => '28,30,32,34', 'dias' => 4],
            ['referencia' => 'US-PANT-CRG-002', 'nombre' => 'Cargo Pants Khaki', 'precio' => 169900, 'categoria' => 'PANTALONES', 'tallas' => '30,32,34,36', 'dias' => 18],
            ['referencia' => 'US-PANT-JGG-003', 'nombre' => 'Tech Jogger Navy', 'precio' => 139900, 'categoria' => 'PANTALONES', 'tallas' => 'S,M,L,XL', 'dias' => 33],
            ['referencia' => 'US-CHQ-VNT-001', 'nombre' => 'Vintage Denim Jacket', 'precio' => 259900, 'categoria' => 'CHAQUETAS', 'tallas' => 'S,M,L', 'dias' => 6],
            ['referencia' => 'US-CHQ-WND-002', 'nombre' => 'Windbreaker Neon', 'precio' => 199900, 'categoria' => 'CHAQUETAS', 'tallas' => 'S,M,L,XL', 'dias' => 14],
            ['referencia' => 'US-BUZ-HDG-001', 'nombre' => 'Heavy Hoodie Charcoal', 'precio' => 169900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L,XL', 'dias' => 1],
            ['referencia' => 'US-BUZ-HFZ-002', 'nombre' => 'Half Zip Cream', 'precio' => 159900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L', 'dias' => 12],
            ['referencia' => 'US-ACC-BCK-001', 'nombre' => 'Urban Backpack', 'precio' => 189900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 20],
            ['referencia' => 'US-ACC-CAP-002', 'nombre' => 'Beanie Knit Black', 'precio' => 49900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 8],
        ];

        foreach ($stores as $store) {
            $categoriaIds = Categoria::forStore($store)->pluck('id', 'categoria');

            if ($categoriaIds->isEmpty()) {
                $this->command->warn("No hay categorías para {$store->slug}. Ejecuta CategoriaSeeder primero.");
                continue;
            }

            // Elegir lista de productos según la tienda
            $productosList = match ($store->slug) {
                'urbanstyle' => $urbanProductos,
                default => $demoProductos,
            };

            foreach ($productosList as $item) {
                $categoriaId = $categoriaIds[$item['categoria']] ?? null;

                if (!$categoriaId) {
                    continue;
                }

                Producto::updateOrCreate(
                    ['referencia' => $item['referencia'], 'store_id' => $store->id],
                    [
                        'nombre' => $item['nombre'],
                        'precio' => $item['precio'],
                        'foto' => null,
                        'tallas' => $item['tallas'],
                        'categoria_id' => $categoriaId,
                        'store_id' => $store->id,
                        'estado' => 'DISPONIBLE',
                        'destacado' => in_array($item['categoria'], ['CAMISETAS', 'CHAQUETAS', 'ACCESORIOS']),
                        'nuevo' => $item['dias'] <= 10,
                        'created_at' => now()->subDays($item['dias']),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->line("Productos creados para: {$store->slug}");
        }

        Producto::restoreTenantScope();
    }
}
