<?php

namespace Tests\Feature\Bd;

use App\Enums\DepartamentoCi;
use App\Enums\EjecucionEtapa;
use App\Enums\EstadoDocumento;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoProceso;
use App\Enums\EstadoUsuario;
use App\Enums\MotivoNoAprobacion;
use App\Enums\TipoConsentimiento;
use App\Enums\TipoProceso;
use App\Enums\TipoPrueba;
use App\Enums\UmbralOrigen;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reglas F1–F4 de specs/02_formato_tablas_bd.md verificadas contra el esquema real.
 */
class FormatoTablasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_esquema_tiene_las_43_tablas_las_6_vistas_y_los_53_triggers(): void
    {
        $tablas = DB::scalar("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'talento' AND table_type = 'BASE TABLE'");
        $vistas = DB::scalar("SELECT count(*) FROM information_schema.views WHERE table_schema = 'talento'");
        $triggers = DB::scalar("SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'talento' AND NOT t.tgisinternal");

        $this->assertSame(43, $tablas);
        $this->assertSame(6, $vistas);
        $this->assertSame(53, $triggers);
    }

    public function test_tablas_y_columnas_estan_en_minuscula_y_snake_case(): void
    {
        $nombresInvalidos = DB::select(<<<'SQL'
            SELECT table_name, column_name
              FROM information_schema.columns
             WHERE table_schema = 'talento'
               AND (table_name !~ '^[a-z][a-z0-9_]*$' OR column_name !~ '^[a-z][a-z0-9_]*$')
            SQL);

        $this->assertSame([], $nombresInvalidos);
    }

    public function test_todas_las_tablas_tienen_created_at_y_updated_at_no_nulos(): void
    {
        $sinTimestamps = DB::select(<<<'SQL'
            SELECT t.table_name
              FROM information_schema.tables t
             WHERE t.table_schema = 'talento' AND t.table_type = 'BASE TABLE'
               AND (SELECT count(*) FROM information_schema.columns c
                     WHERE c.table_schema = t.table_schema AND c.table_name = t.table_name
                       AND c.column_name IN ('created_at', 'updated_at')
                       AND c.is_nullable = 'NO' AND c.data_type = 'timestamp with time zone') <> 2
            SQL);

        $this->assertSame([], $sinTimestamps);
    }

    public function test_todas_las_tablas_tienen_el_trigger_de_updated_at(): void
    {
        $sinTrigger = DB::select(<<<'SQL'
            SELECT t.table_name
              FROM information_schema.tables t
             WHERE t.table_schema = 'talento' AND t.table_type = 'BASE TABLE'
               AND NOT EXISTS (SELECT 1 FROM information_schema.triggers g
                                WHERE g.event_object_schema = 'talento'
                                  AND g.event_object_table = t.table_name
                                  AND g.trigger_name = 'trg_' || t.table_name || '_updated')
            SQL);

        $this->assertSame([], $sinTrigger);
    }

    public function test_el_trigger_actualiza_updated_at_aunque_no_se_use_eloquent(): void
    {
        DB::table('rol')->where('codigo', 'ADMIN')->update(['updated_at' => '2000-01-01 00:00:00+00']);

        DB::table('rol')->where('codigo', 'ADMIN')->update(['descripcion' => 'Cambio directo']);

        $this->assertNotSame('2000', substr((string) DB::table('rol')->where('codigo', 'ADMIN')->value('updated_at'), 0, 4));
    }

    public function test_no_se_puede_insertar_un_id_explicito(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('GENERATED ALWAYS');

        DB::table('rol')->insert(['id' => 999, 'codigo' => 'OTRO', 'nombre' => 'Otro']);
    }

    public function test_la_bd_calcula_las_columnas_generadas(): void
    {
        $columnas = DB::select(<<<'SQL'
            SELECT table_name || '.' || column_name AS columna
              FROM information_schema.columns
             WHERE table_schema = 'talento' AND is_generated = 'ALWAYS'
             ORDER BY 1
            SQL);

        $this->assertSame(
            ['intento_prueba.duracion_seg', 'resultado_prueba.puntaje_normalizado'],
            array_column($columnas, 'columna'),
        );
    }

    public function test_el_correo_es_unico_sin_distinguir_mayusculas(): void
    {
        Usuario::factory()->create(['correo' => 'ana@talento.test']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_usuario_correo');

        DB::table('usuario')->insert([
            'rol_id' => Rol::query()->value('id'),
            'correo' => 'Ana@Talento.test',
            'password_hash' => 'x',
        ]);
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    #[DataProvider('enumsConSuCheck')]
    public function test_cada_enum_php_coincide_con_su_check_en_la_bd(string $enum, string $restriccion): void
    {
        $definicion = DB::scalar('SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conname = ?', [$restriccion]);
        preg_match_all("/'([A-Z_]+)'/", (string) $definicion, $valores);

        $valoresBd = $valores[1];
        $valoresPhp = array_column($enum::cases(), 'value');
        sort($valoresBd);
        sort($valoresPhp);

        $this->assertSame($valoresBd, $valoresPhp);
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function enumsConSuCheck(): array
    {
        return [
            'EstadoUsuario' => [EstadoUsuario::class, 'ck_usuario_estado'],
            'DepartamentoCi' => [DepartamentoCi::class, 'ck_persona_ci_expedido'],
            'TipoConsentimiento' => [TipoConsentimiento::class, 'ck_consentimiento_tipo'],
            'EjecucionEtapa' => [EjecucionEtapa::class, 'ck_etapa_ejecucion'],
            'TipoPrueba' => [TipoPrueba::class, 'ck_prueba_tipo'],
            'TipoProceso' => [TipoProceso::class, 'ck_proceso_tipo'],
            'EstadoProceso' => [EstadoProceso::class, 'ck_proceso_estado'],
            'UmbralOrigen' => [UmbralOrigen::class, 'ck_proceso_prueba_umbral'],
            'EstadoPostulacion' => [EstadoPostulacion::class, 'ck_postulacion_estado'],
            'EstadoEtapa' => [EstadoEtapa::class, 'ck_postulacion_etapa_estado'],
            'EstadoDocumento' => [EstadoDocumento::class, 'ck_documento_postulacion_estado'],
            'MotivoNoAprobacion' => [MotivoNoAprobacion::class, 'ck_resultado_motivo_valor'],
        ];
    }

    public function test_el_seeder_de_catalogos_es_idempotente(): void
    {
        $antes = DB::table('rol')->count() + DB::table('etapa')->count() + DB::table('dimension_evaluada')->count()
            + DB::table('prueba')->count() + DB::table('rubrica_criterio')->count() + DB::table('tipo_documento')->count()
            + DB::table('parametro_sistema')->count();

        $this->seed(CatalogoSeeder::class);

        $despues = DB::table('rol')->count() + DB::table('etapa')->count() + DB::table('dimension_evaluada')->count()
            + DB::table('prueba')->count() + DB::table('rubrica_criterio')->count() + DB::table('tipo_documento')->count()
            + DB::table('parametro_sistema')->count();
        $this->assertSame(5 + 10 + 16 + 9 + 4 + 4 + 3, $antes);
        $this->assertSame($antes, $despues);
    }
}
