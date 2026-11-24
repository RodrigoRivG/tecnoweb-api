<?php

namespace App\Models;

use App\Enums\CodigoRol;
use App\Enums\EstadoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Table('usuario')]
#[Fillable(['rol_id', 'correo', 'password_hash', 'estado', 'intentos_fallidos', 'ultimo_acceso'])]
#[Hidden(['password_hash'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasApiTokens, HasFactory;

    /**
     * Intentos fallidos seguidos tras los cuales la cuenta se bloquea.
     */
    public const MAX_INTENTOS_FALLIDOS = 5;

    /**
     * Columna que guarda el hash de la contraseña.
     *
     * @var string
     */
    protected $authPasswordName = 'password_hash';

    /**
     * La tabla usuario no tiene columna remember_token (la API usa tokens de Sanctum).
     *
     * @var string
     */
    protected $rememberTokenName = '';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'estado' => EstadoUsuario::class,
            'intentos_fallidos' => 'integer',
            'ultimo_acceso' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    /**
     * @return HasOne<Persona, $this>
     */
    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class);
    }

    /**
     * Carreras que atiende cuando su rol tiene alcance por carrera.
     *
     * @return BelongsToMany<Carrera, $this>
     */
    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'talento.ayudante_carrera', 'usuario_id', 'carrera_id')
            ->withTimestamps();
    }

    public function tieneRol(CodigoRol ...$roles): bool
    {
        return in_array($this->rol->codigo, array_map(fn (CodigoRol $rol): string => $rol->value, $roles), true);
    }

    public function tienePermiso(string ...$permisos): bool
    {
        // El administrador del sistema siempre cuenta con todos los permisos
        if ($this->rol?->codigo === 'ADMIN') {
            return true;
        }

        if (! $this->relationLoaded('rol.permisos')) {
            $this->load('rol.permisos');
        }

        $codigosAsignados = $this->rol?->permisos?->pluck('codigo')->all() ?? [];

        foreach ($permisos as $permiso) {
            if (in_array($permiso, $codigosAsignados, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Códigos que el cliente usa para mostrar u ocultar pantallas.
     * El administrador recibe el catálogo completo aunque la matriz esté vacía.
     *
     * @return list<string>
     */
    public function codigosPermiso(): array
    {
        if ($this->rol?->codigo === CodigoRol::Admin->value) {
            return Permiso::query()->orderBy('id')->pluck('codigo')->all();
        }

        if (! $this->relationLoaded('rol.permisos')) {
            $this->load('rol.permisos');
        }

        return $this->rol?->permisos?->pluck('codigo')->values()->all() ?? [];
    }

    /**
     * Sin carreras asignadas el alcance queda abierto. Con alguna, solo esas carreras.
     */
    public function abarcaCarrera(?int $carreraId): bool
    {
        if (! $this->rol?->alcance_carrera) {
            return true;
        }

        $ids = $this->carreras()->pluck('carrera.id');

        if ($ids->isEmpty()) {
            return true;
        }

        return $carreraId !== null && $ids->contains($carreraId);
    }
}
