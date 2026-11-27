<?php

namespace Database\Seeders\Concerns;

use App\Models\Pregunta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LimpiaPreguntasPrueba
{
    /**
     * Borra respuestas e ítems de una prueba sin violar FK (re-seed en servidor).
     */
    public static function ejecutar(int $pruebaId): void
    {
        $ids = Pregunta::query()->where('prueba_id', $pruebaId)->pluck('id');
        self::borrarPorIds($ids);
        Pregunta::query()->where('prueba_id', $pruebaId)->delete();
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $preguntaIds
     */
    public static function borrarPorIds(Collection|array $preguntaIds): void
    {
        $ids = collect($preguntaIds)->filter()->values();
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
    }
}