<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoUsuario;
use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioAdminController extends Controller
{
    /**
     * Lista todos los usuarios del sistema con filtros de rol, estado y búsqueda.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::with(['rol.permisos', 'persona']);

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->integer('rol_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('q')) {
            $termino = '%' . trim($request->string('q')) . '%';
            $query->where(function ($q) use ($termino) {
                $q->where('correo', 'ilike', $termino)
                    ->orWhereHas('persona', function ($qp) use ($termino) {
                        $qp->where('nombres', 'ilike', $termino)
                            ->orWhere('primer_apellido', 'ilike', $termino)
                            ->orWhere('segundo_apellido', 'ilike', $termino)
                            ->orWhere('ci', 'ilike', $termino);
                    });
            });
        }

        $usuarios = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'data' => $usuarios->map(fn (Usuario $u) => [
                'id' => $u->id,
                'correo' => $u->correo,
                'estado' => $u->estado?->value,
                'ultimo_acceso' => $u->ultimo_acceso?->toIso8601String(),
                'rol' => [
                    'id' => $u->rol?->id,
                    'codigo' => $u->rol?->codigo,
                    'nombre' => $u->rol?->nombre,
                ],
                'persona' => $u->persona ? [
                    'id' => $u->persona->id,
                    'nombre_completo' => $u->persona->nombre_completo,
                    'ci' => $u->persona->ci,
                    'telefono' => $u->persona->telefono,
                ] : null,
            ]),
        ]);
    }

    /**
     * Registra un nuevo usuario del equipo interno (Admin o Ayudante).
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'string', 'email', 'max:190', 'unique:talento.usuario,correo'],
            'password' => ['required', 'string', 'min:6'],
            'rol_id' => ['required', 'integer', 'exists:talento.rol,id'],
            'nombres' => ['required', 'string', 'max:80'],
            'primer_apellido' => ['required', 'string', 'max:80'],
            'segundo_apellido' => ['nullable', 'string', 'max:80'],
            'ci' => ['required', 'string', 'max:20', 'unique:talento.persona,ci'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        return DB::transaction(function () use ($datos) {
            $persona = Persona::create([
                'nombres' => $datos['nombres'],
                'primer_apellido' => $datos['primer_apellido'],
                'segundo_apellido' => $datos['segundo_apellido'] ?? null,
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'] ?? null,
            ]);

            $usuario = Usuario::create([
                'persona_id' => $persona->id,
                'correo' => strtolower(trim($datos['correo'])),
                'password_hash' => Hash::make($datos['password']),
                'rol_id' => $datos['rol_id'],
                'estado' => EstadoUsuario::Activo,
            ]);

            return response()->json([
                'mensaje' => 'Usuario creado exitosamente.',
                'data' => [
                    'id' => $usuario->id,
                    'correo' => $usuario->correo,
                    'rol' => $usuario->rol?->nombre,
                ],
            ], 201);
        });
    }

    /**
     * Actualiza el rol o estado de un usuario.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        $datos = $request->validate([
            'rol_id' => ['sometimes', 'integer', 'exists:talento.rol,id'],
            'estado' => ['sometimes', Rule::in(['ACTIVO', 'BLOQUEADO', 'INACTIVO'])],
        ]);

        if (isset($datos['rol_id'])) {
            $usuario->rol_id = $datos['rol_id'];
        }

        if (isset($datos['estado'])) {
            $usuario->estado = EstadoUsuario::from($datos['estado']);
        }

        $usuario->save();

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente.',
            'data' => [
                'id' => $usuario->id,
                'correo' => $usuario->correo,
                'estado' => $usuario->estado->value,
                'rol' => $usuario->rol?->nombre,
            ],
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
                'roles' => $roles->map(fn (Rol $r) => [
                    'id' => $r->id,
                    'codigo' => $r->codigo,
                    'nombre' => $r->nombre,
                    'descripcion' => $r->descripcion,
                    'permisos_ids' => $r->permisos->pluck('id')->all(),
                ]),
                'permisos' => $permisos->map(fn (Permiso $p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'modulo' => $p->modulo,
                    'descripcion' => $p->descripcion,
                ]),
            ],
        ]);
    }

    /**
     * Asigna la lista de permisos a un rol.
     */
    public function actualizarPermisosRol(Request $request, int $rolId): JsonResponse
    {
        $rol = Rol::findOrFail($rolId);

        $datos = $request->validate([
            'permisos_ids' => ['required', 'array'],
            'permisos_ids.*' => ['integer', 'exists:talento.permiso,id'],
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
}
