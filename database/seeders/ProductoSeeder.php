<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categoriaIds = Categoria::pluck('id', 'categoria');

        $productos = [
            ['referencia' => 'Camiseta Oversize Beige', 'precio' => 89900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 8],
            ['referencia' => 'Camiseta Graphic Noir', 'precio' => 109900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 16],
            ['referencia' => 'Camiseta Basica Blanca', 'precio' => 69900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L', 'dias' => 45],
            ['referencia' => 'Pantalon Cargo Arena', 'precio' => 159900, 'categoria' => 'PANTALONES', 'tallas' => '30,32,34,36', 'dias' => 9],
            ['referencia' => 'Jeans Straight Mid Blue', 'precio' => 179900, 'categoria' => 'PANTALONES', 'tallas' => '28,30,32,34,36', 'dias' => 51],
            ['referencia' => 'Jogger Essential Black', 'precio' => 119900, 'categoria' => 'PANTALONES', 'tallas' => 'S,M,L,XL', 'dias' => 23],
            ['referencia' => 'Chaqueta Denim Stone', 'precio' => 239900, 'categoria' => 'CHAQUETAS', 'tallas' => 'S,M,L', 'dias' => 5],
            ['referencia' => 'Chaqueta Bomber Olive', 'precio' => 219900, 'categoria' => 'CHAQUETAS', 'tallas' => 'M,L,XL', 'dias' => 31],
            ['referencia' => 'Chaqueta Windbreaker Neon', 'precio' => 199900, 'categoria' => 'CHAQUETAS', 'tallas' => 'S,M,L,XL', 'dias' => 14],
            ['referencia' => 'Buzo Hoodie Granite', 'precio' => 149900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L,XL', 'dias' => 11],
            ['referencia' => 'Buzo Half Zip Cream', 'precio' => 169900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L', 'dias' => 62],
            ['referencia' => 'Buzo Crewneck Urban', 'precio' => 139900, 'categoria' => 'BUZOS', 'tallas' => 'M,L,XL', 'dias' => 27],
            ['referencia' => 'Gorra Curved Black', 'precio' => 59900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 7],
            ['referencia' => 'Gorra Trucker Sand', 'precio' => 65900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 39],
            ['referencia' => 'Morral Tech Minimal', 'precio' => 189900, 'categoria' => 'ACCESORIOS', 'tallas' => 'UNICA', 'dias' => 19],
            ['referencia' => 'Camiseta Rayas Retro', 'precio' => 99900, 'categoria' => 'CAMISETAS', 'tallas' => 'S,M,L,XL', 'dias' => 88],
            ['referencia' => 'Jeans Baggy Night', 'precio' => 184900, 'categoria' => 'PANTALONES', 'tallas' => '30,32,34,36', 'dias' => 67],
            ['referencia' => 'Chaqueta Puffer Cloud', 'precio' => 289900, 'categoria' => 'CHAQUETAS', 'tallas' => 'M,L,XL', 'dias' => 3],
            ['referencia' => 'Buzo Knit Graphite', 'precio' => 154900, 'categoria' => 'BUZOS', 'tallas' => 'S,M,L', 'dias' => 56],
            ['referencia' => 'Cinturon Leather Brown', 'precio' => 84900, 'categoria' => 'ACCESORIOS', 'tallas' => 'S,M,L', 'dias' => 35],
        ];

        foreach ($productos as $item) {
            $categoriaId = $categoriaIds[$item['categoria']] ?? null;

            if (!$categoriaId) {
                continue;
            }

            Producto::updateOrCreate(
                ['referencia' => $item['referencia']],
                [
                    'precio' => $item['precio'],
                    'foto' => null,
                    'tallas' => $item['tallas'],
                    'categoria_id' => $categoriaId,
                    'estado' => 'DISPONIBLE',
                    'created_at' => now()->subDays($item['dias']),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
