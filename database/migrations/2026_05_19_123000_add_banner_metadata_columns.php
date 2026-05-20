<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_banners', function (Blueprint $table) {
            $table->string('nombre')->nullable()->after('posicion');
            $table->string('identificador')->nullable()->after('nombre');
        });

        Schema::table('bloque_home_imagenes', function (Blueprint $table) {
            $table->string('nombre')->nullable()->after('imagen');
            $table->string('identificador')->nullable()->after('nombre');
        });

        $catalogRows = DB::table('catalog_banners')->get(['id', 'posicion']);
        foreach ($catalogRows as $row) {
            DB::table('catalog_banners')
                ->where('id', $row->id)
                ->update([
                    'nombre' => $row->posicion === 1 ? 'Banner izquierdo' : 'Banner derecho',
                    'identificador' => $row->posicion === 1 ? 'banner-izq' : 'banner-der',
                ]);
        }

        $slideRows = DB::table('bloque_home_imagenes')->orderBy('id')->get(['id']);
        $index = 1;
        foreach ($slideRows as $row) {
            DB::table('bloque_home_imagenes')
                ->where('id', $row->id)
                ->update([
                    'nombre' => 'Slide '.$index,
                    'identificador' => 'carrusel',
                ]);
            $index++;
        }
    }

    public function down(): void
    {
        Schema::table('catalog_banners', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'identificador']);
        });

        Schema::table('bloque_home_imagenes', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'identificador']);
        });
    }
};
