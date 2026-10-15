<?php

namespace App\Support\Migraciones;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Formato común de las tablas del esquema talento (specs/02_formato_tablas_bd.md).
 */
class Talento
{
    /**
     * Agrega created_at y updated_at como TIMESTAMPTZ NOT NULL DEFAULT now().
     */
    public static function timestamps(Blueprint $table, ?int $precision = null): void
    {
        $table->timestampTz('created_at', $precision)->useCurrent();
        $table->timestampTz('updated_at', $precision)->useCurrent();
    }

    /**
     * Crea los CHECK con nombre y el trigger que mantiene updated_at.
     *
     * @param  array<string, string>  $checks  nombre de la restricción => expresión
     */
    public static function finalizar(string $tabla, array $checks = []): void
    {
        foreach ($checks as $restriccion => $expresion) {
            DB::statement("ALTER TABLE talento.{$tabla} ADD CONSTRAINT {$restriccion} CHECK ({$expresion})");
        }

        DB::statement("CREATE TRIGGER trg_{$tabla}_updated BEFORE UPDATE ON talento.{$tabla} FOR EACH ROW EXECUTE FUNCTION talento.set_updated_at()");
    }

    /**
     * Expresión "columna IN ('A','B')" para un CHECK de valores permitidos.
     *
     * @param  list<string>  $valores
     */
    public static function en(string $columna, array $valores): string
    {
        $lista = implode(',', array_map(fn (string $valor): string => "'{$valor}'", $valores));

        return "{$columna} IN ({$lista})";
    }
}
