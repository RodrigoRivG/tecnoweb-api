<?php

namespace App\Models;

use App\Enums\EstadoPostulacion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Table('postulacion')]
#[Fillable(['codigo', 'proceso_id', 'persona_id', 'registrada_por', 'estado', 'motivo_cierre'])]
class Postulacion extends Model
{
    /**
     * Caracteres del código seudónimo, sin los ambiguos (0/O, 1/I).
     */
    private const ALFABETO_CODIGO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPostulacion::class,
        ];
    }

    /**
     * Código seudónimo único para la landing pública, p. ej. T26-7KQ9XM.
     */
    public static function generarCodigo(string $prefijo, int $gestion): string
    {
        do {
            $aleatorio = collect(range(1, 6))
                ->map(fn (): string => self::ALFABETO_CODIGO[random_int(0, strlen(self::ALFABETO_CODIGO) - 1)])
                ->implode('');
            $codigo = Str::upper($prefijo).substr((string) $gestion, -2).'-'.$aleatorio;
        } while (static::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return HasMany<PostulacionEtapa, $this>
     */
    public function etapas(): HasMany
    {
        return $this->hasMany(PostulacionEtapa::class);
    }

    /**
     * @return HasMany<DocumentoPostulacion, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPostulacion::class);
    }

    /**
     * @return HasMany<ResultadoPrueba, $this>
     */
    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoPrueba::class);
    }
}
