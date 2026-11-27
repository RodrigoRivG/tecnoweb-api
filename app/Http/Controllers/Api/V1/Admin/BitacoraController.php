<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use stdClass;

class BitacoraController extends Controller
{
    private const TIPOS_LOGIN = ['LOGIN_EXITOSO', 'LOGIN_FALLIDO'];

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'resultado' => ['nullable', Rule::in(self::TIPOS_LOGIN)],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), ['after_or_equal:desde'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $consulta = DB::table('talento.evento_acceso as evento')
            ->leftJoin('talento.usuario as usuario', 'usuario.id', '=', 'evento.usuario_id')
            ->whereIn('evento.tipo', self::TIPOS_LOGIN)
            ->select('evento.id', 'evento.tipo', 'evento.created_at', 'usuario.correo')
            ->selectRaw('host(evento.direccion_ip) as direccion_ip');

        if (isset($filtros['resultado'])) {
            $consulta->where('evento.tipo', $filtros['resultado']);
        }

        if (isset($filtros['desde'])) {
            $consulta->where('evento.created_at', '>=', $this->inicioDelDia($filtros['desde'])->toIso8601String());
        }

        if (isset($filtros['hasta'])) {
            $consulta->where('evento.created_at', '<', $this->inicioDelDia($filtros['hasta'])->addDay()->toIso8601String());
        }

        $eventos = $consulta->orderByDesc('evento.created_at')->orderByDesc('evento.id')->paginate(25)
            ->appends(Arr::except($filtros, ['page']))
            ->through(fn (stdClass $evento): array => [
                'id' => $evento->id,
                'tipo' => $evento->tipo,
                'correo' => $evento->correo,
                'direccion_ip' => $evento->direccion_ip,
                'fecha' => CarbonImmutable::parse($evento->created_at)->toIso8601String(),
            ]);

        return JsonResource::collection($eventos);
    }

    private function inicioDelDia(string $fecha): CarbonImmutable
    {
        return CarbonImmutable::parse($fecha, config('app.zona_horaria_negocio'))->startOfDay();
    }
}
