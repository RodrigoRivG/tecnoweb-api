<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoUsuario;
use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\ReglasValidacionCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsuarioAdminController extends Controller
{
    /**
     * Lista todos los usuarios del sistema con filtros de rol, estado y búsqueda.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::with(['rol', 'persona']);

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->integer('rol_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('q')) {
            $termino = '%'.trim($request->string('q')).'%';
            $query->where(function ($q) use ($termino) {
                $q->where('correo', 'ilike', $termino)
                    ->orWhereHas('persona', function ($qp) use ($termino) {
                        $qp->where('nombres', 'ilike', $termino)
                            ->orWhere('apellido_paterno', 'ilike', $termino)
                            ->orWhere('apellido_materno', 'ilike', $termino)
                            ->orWhere('ci_numero', 'ilike', $termino);
                    });
            });
        }

        $usuarios = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'data' => $usuarios->map(fn (Usuario $usuario) => $this->presentar($usuario)),
            'roles' => Rol::query()->orderBy('id')->get()->map(fn (Rol $rol) => $this->presentarRol($rol)),
        ]);
    }

    /**
     * Registra un usuario del equipo interno (panel).
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $this->validarCuenta($request);
        $rol = $this->rolInterno($datos['rol_id']);

        $usuario = DB::transaction(function () use ($datos, $rol) {
            $usuario = Usuario::create([
                'correo' => strtolower(trim($datos['correo'])),
                'password_hash' => $datos['password'],
                'rol_id' => $rol->id,
                'estado' => EstadoUsuario::Activo,
            ]);

            Persona::create([
                'usuario_id' => $usuario->id,
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'] ?? null,
                'ci_numero' => $datos['ci_numero'],
                'ci_complemento' => '',
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'telefono' => $datos['telefono'] ?? null,
                'correo_contacto' => strtolower(trim($datos['correo'])),
            ]);

            return $usuario->load(['rol', 'persona']);
        });

        return response()->json([
            'mensaje' => 'Usuario creado exitosamente.',
            'data' => $this->presentar($usuario),
        ], 201);
    }

    /**
     * Actualiza el rol, el estado o la identidad de un usuario del panel.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::with('persona')->findOrFail($id);
        $datos = $this->validarCuenta($request, $usuario);

        DB::transaction(function () use ($usuario, $datos) {
            if (isset($datos['correo'])) {
                $usuario->correo = strtolower(trim($datos['correo']));
            }

            if (! empty($datos['password'])) {
                $usuario->password_hash = $datos['password'];
            }

            if (isset($datos['rol_id'])) {
                $usuario->rol_id = $datos['rol_id'];
            }

            if (isset($datos['estado'])) {
                $usuario->estado = EstadoUsuario::from($datos['estado']);
            }

            $usuario->save();

            if (isset($datos['nombres'])) {
                $persona = $usuario->persona ?? new Persona(['usuario_id' => $usuario->id, 'ci_complemento' => '']);
                $persona->fill([
                    'usuario_id' => $usuario->id,
                    'nombres' => $datos['nombres'],
                    'apellido_paterno' => $datos['apellido_paterno'],
                    'apellido_materno' => $datos['apellido_materno'] ?? null,
                    'ci_numero' => $datos['ci_numero'],
                    'ci_complemento' => $persona->ci_complemento ?? '',
                    'fecha_nacimiento' => $datos['fecha_nacimiento'],
                    'telefono' => $datos['telefono'] ?? null,
                    'correo_contacto' => $usuario->correo,
                ]);
                $persona->save();
            }

        });

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente.',
            'data' => $this->presentar($usuario->fresh(['rol', 'persona'])),
        ]);
    }

    /**
     * Obtiene el catálogo de roles con sus permisos asignados y todos los permisos del sistema agrupados.
     */
    public function rolesPermisos(): JsonResponse
    {
        $roles = Rol::with('permisos')->orderBy('id')->get();
        $permisos = Permiso::orderBy('modulo')->orderBy('id')->get();

        return response()->json([
            'data' => [
                'roles' => $roles->map(fn (Rol $rol) => [
                    ...$this->presentarRol($rol),
                    'permisos_ids' => $rol->permisos->pluck('id')->all(),
                ]),
                'permisos' => $permisos->map(fn (Permiso $permiso) => [
                    'id' => $permiso->id,
                    'codigo' => $permiso->codigo,
                    'nombre' => $permiso->nombre,
                    'modulo' => $permiso->modulo,
                    'descripcion' => $permiso->descripcion,
                ]),
            ],
        ]);
    }

    /**
     * Crea un rol nuevo. Los de gestión interna pueden entrar al panel.
     */
    public function storeRol(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'regex:/^[A-Z][A-Z0-9_]{1,29}$/', Rule::unique(Rol::class, 'codigo')],
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'es_panel' => ['required', 'boolean'],
        ]);

        $rol = Rol::create([
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'es_panel' => $datos['es_panel'],
            'alcance_carrera' => false,
        ]);

        return response()->json([
            'mensaje' => 'Rol creado. Marca ahora los permisos que podrá usar.',
            'data' => [
                ...$this->presentarRol($rol),
                'permisos_ids' => [],
            ],
        ], 201);
    }

    /**
     * Asigna la lista de permisos a un rol.
     */
    public function actualizarPermisosRol(Request $request, int $rolId): JsonResponse
    {
        $rol = Rol::findOrFail($rolId);

        $datos = $request->validate([
            'permisos_ids' => ['required', 'array'],
            'permisos_ids.*' => ['integer', Rule::exists(Permiso::class, 'id')],
        ]);

        $rol->permisos()->sync($datos['permisos_ids']);

        return response()->json([
            'mensaje' => "Permisos actualizados para el rol {$rol->nombre}.",
            'data' => [
                'rol_id' => $rol->id,
                'codigo' => $rol->codigo,
                'permisos_ids' => $rol->permisos()->pluck('permiso_id')->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarCuenta(Request $request, ?Usuario $usuario = null): array
    {
        $personaId = $usuario?->persona?->id;
        $alta = $usuario === null;

        return $request->validate(
            [
                'correo' => [$alta ? 'required' : 'sometimes', 'required', 'string', 'email', 'max:190', Rule::unique(Usuario::class, 'correo')->ignore($usuario?->id)],
                'password' => $alta ? ReglasValidacionCuenta::passwordPanelAlta() : ReglasValidacionCuenta::passwordEdicion(),
                'rol_id' => [$alta ? 'required' : 'sometimes', 'integer', Rule::exists(Rol::class, 'id')],
                'estado' => ['sometimes', Rule::in(['ACTIVO', 'BLOQUEADO', 'INACTIVO'])],
                'nombres' => [$alta ? 'required' : 'sometimes', 'required', 'string', 'max:100'],
                'apellido_paterno' => [$alta ? 'required' : 'required_with:nombres', 'string', 'max:60'],
                'apellido_materno' => ['nullable', 'string', 'max:60'],
                'ci_numero' => [
                    ...ReglasValidacionCuenta::ciNumero($alta || $request->filled('nombres')),
                    Rule::unique(Persona::class, 'ci_numero')
                        ->where(fn ($q) => $q->where('ci_complemento', ''))
                        ->ignore($personaId),
                ],
                'fecha_nacimiento' => ReglasValidacionCuenta::fechaMayoriaDeEdad($alta || $request->filled('nombres')),
                'telefono' => ReglasValidacionCuenta::telefonoBolivia(),
            ],
            ReglasValidacionCuenta::mensajes(),
        );
    }

    private function rolInterno(int $rolId): Rol
    {
        $rol = Rol::query()->findOrFail($rolId);

        if (! $rol->es_panel) {
            throw ValidationException::withMessages([
                'rol_id' => 'Elige un rol de gestión interna.',
            ]);
        }

        return $rol;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentar(Usuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'correo' => $usuario->correo,
            'estado' => $usuario->estado?->value ?? $usuario->estado,
            'ultimo_acceso' => $usuario->ultimo_acceso?->toIso8601String(),
            'rol' => $usuario->rol ? $this->presentarRol($usuario->rol) : null,
            'persona' => $usuario->persona ? [
                'id' => $usuario->persona->id,
                'nombres' => $usuario->persona->nombres,
                'apellido_paterno' => $usuario->persona->apellido_paterno,
                'apellido_materno' => $usuario->persona->apellido_materno,
                'nombre_completo' => $usuario->persona->nombre_completo,
                'ci_numero' => $usuario->persona->ci_numero,
                'ci' => $usuario->persona->ci,
                'fecha_nacimiento' => $usuario->persona->fecha_nacimiento?->toDateString(),
                'telefono' => $usuario->persona->telefono,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarRol(Rol $rol): array
    {
        return [
            'id' => $rol->id,
            'codigo' => $rol->codigo,
            'nombre' => $rol->nombre,
            'descripcion' => $rol->descripcion,
            'es_panel' => (bool) $rol->es_panel,
            'alcance_carrera' => (bool) $rol->alcance_carrera,
        ];
    }
}
