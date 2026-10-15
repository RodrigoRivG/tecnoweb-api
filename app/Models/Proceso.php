<?php

namespace App\Models;

use App\Enums\EstadoProceso;
use App\Enums\TipoProceso;
use App\Enums\UmbralOrigen;
use Database\Factories\ProcesoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('proceso')]
#[Fillable(['codigo', 'tipo', 'nombre', 'estado', 'top_n', 'creado_por'])]
class Proceso extends Model
{
    /** @use HasFactory<ProcesoFactory> */
    use HasFactory;

    /**
     * Relaciones necesarias para calcular el umbral de PPA sin consultas adicionales.
     *
     * @var list<string>
     */
    public const RELACIONES_UMBRAL = ['convocatoria.carrera.facultad', 'etapas.pruebas.prueba'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoProceso::class,
            'estado' => EstadoProceso::class,
            'top_n' => 'integer',
        ];
    }

    /**
     * @return HasOne<ConvocatoriaTalento, $this>
     */
    public function convocatoria(): HasOne
    {
        return $this->hasOne(ConvocatoriaTalento::class);
    }

    /**
     * @return HasMany<ProcesoEtapa, $this>
     */
    public function etapas(): HasMany
    {
        return $this->hasMany(ProcesoEtapa::class)->orderBy('orden');
    }

    /**
     * @return BelongsToMany<TipoDocumento, $this, RequisitoDocumento, 'requisito'>
     */
    public function tiposDocumento(): BelongsToMany
    {
        return $this->belongsToMany(TipoDocumento::class, 'requisito_documento')
            ->using(RequisitoDocumento::class)
            ->as('requisito')
            ->withPivot('obligatorio')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Postulacion, $this>
     */
    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    /**
     * Convocatorias de talento abiertas y dentro de sus fechas (regla R5).
     *
     * @param  Builder<Proceso>  $query
     */
    #[Scope]
    protected function convocatoriasAbiertas(Builder $query): void
    {
        $hoy = today(config('app.zona_horaria_negocio'))->toDateString();

        $query->where('tipo', TipoProceso::Talento)
            ->where('estado', EstadoProceso::Abierto)
            ->whereHas('convocatoria', fn (Builder $convocatoria) => $convocatoria
                ->where('fecha_apertura', '<=', $hoy)
                ->where(fn (Builder $cierre) => $cierre->whereNull('fecha_cierre')->orWhere('fecha_cierre', '>=', $hoy)));
    }

    public function estaAbiertaHoy(): bool
    {
        return static::query()->convocatoriasAbiertas()->whereKey($this->getKey())->exists();
    }

    /**
     * Configuración de la prueba PPA dentro del proceso, si la tiene.
     */
    public function pruebaPpa(): ?ProcesoPrueba
    {
        return $this->etapas
            ->where('activa', true)
            ->flatMap(fn (ProcesoEtapa $etapa) => $etapa->pruebas)
            ->first(fn (ProcesoPrueba $procesoPrueba): bool => $procesoPrueba->prueba->codigo === Prueba::PPA);
    }

    /**
     * PPA mínimo exigido (regla R3): el de la facultad, salvo que la prueba PPA tenga umbral FIJO.
     */
    public function umbralPpa(): string
    {
        $pruebaPpa = $this->pruebaPpa();

        if ($pruebaPpa?->umbral_origen === UmbralOrigen::Fijo && $pruebaPpa->puntaje_minimo !== null) {
            return $pruebaPpa->puntaje_minimo;
        }

        return $this->convocatoria->carrera->facultad->ppa_minimo;
    }
}
