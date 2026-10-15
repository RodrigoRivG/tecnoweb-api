<?php

namespace Tests\Feature\Egresado;

use App\Enums\EstadoEtapa;
use App\Models\Carrera;
use App\Models\DocumentoPostulacion;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\Usuario;
use Database\Factories\ConvocatoriaTalentoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Postulacion $postulacion;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $carrera = Carrera::factory()->create();
        $this->usuario = $this->crearEgresado($carrera);
        $proceso = $this->crearConvocatoria($carrera);
        $this->actingAs($this->usuario, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();
        $this->postulacion = Postulacion::query()->sole();
    }

    public function test_subir_un_documento_lo_guarda_en_disco_privado_y_lo_registra_pendiente(): void
    {
        $archivo = UploadedFile::fake()->create('mi cv.pdf', 300, 'application/pdf');

        $respuesta = $this->subir('CURRICULUM_VITAE', $archivo);

        $respuesta->assertCreated()
            ->assertJsonPath('data.tipo_documento', 'CURRICULUM_VITAE')
            ->assertJsonPath('data.nombre_archivo', 'mi cv.pdf')
            ->assertJsonPath('data.estado', 'PENDIENTE')
            ->assertJsonMissingPath('data.clave_almacenamiento');
        $documento = DocumentoPostulacion::query()->with('archivo')->sole();
        $this->assertTrue($documento->es_vigente);
        $this->assertStringStartsWith("postulaciones/{$this->postulacion->codigo}/", $documento->archivo->clave_almacenamiento);
        Storage::disk('local')->assertExists($documento->archivo->clave_almacenamiento);
        $this->assertSame(hash_file('sha256', $archivo->getRealPath()), $documento->archivo->hash_sha256);
        $this->assertSame($this->usuario->id, $documento->archivo->subido_por);
    }

    public function test_reemplazar_un_documento_deja_un_solo_vigente_y_conserva_el_anterior(): void
    {
        $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv-v1.pdf', 100, 'application/pdf'))->assertCreated();

        $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv-v2.pdf', 100, 'application/pdf'))->assertCreated();

        $this->assertDatabaseCount('documento_postulacion', 2);
        $vigente = DocumentoPostulacion::query()->where('es_vigente', true)->with('archivo')->sole();
        $this->assertSame('cv-v2.pdf', $vigente->archivo->nombre_original);
    }

    public function test_completar_los_obligatorios_pasa_requisitos_a_revision(): void
    {
        foreach (ConvocatoriaTalentoFactory::REQUISITOS_FLUJO_A as $tipo) {
            $this->assertSame(EstadoEtapa::Pendiente, $this->etapaRequisitos()->estado);

            $this->subir($tipo, UploadedFile::fake()->image("{$tipo}.jpg"))->assertCreated();
        }

        $this->assertSame(EstadoEtapa::EnRevision, $this->etapaRequisitos()->estado);
        $this->assertDatabaseHas('historial_postulacion_etapa', [
            'postulacion_etapa_id' => $this->etapaRequisitos()->id,
            'estado_anterior' => 'PENDIENTE',
            'estado_nuevo' => 'EN_REVISION',
            'usuario_id' => $this->usuario->id,
        ]);
        $this->actingAs($this->usuario, 'sanctum')
            ->getJson(route('egresado.postulaciones.show', $this->postulacion))
            ->assertOk()
            ->assertJsonPath('data.requisitos_completos', true);
    }

    public function test_tipo_de_documento_que_la_convocatoria_no_pide_es_rechazado(): void
    {
        $this->postulacion->proceso->tiposDocumento()->detach();

        $respuesta = $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo_documento' => 'La convocatoria no solicita este tipo de documento.']);
        $this->assertDatabaseCount('archivo', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_la_fotografia_solo_admite_imagenes(): void
    {
        $respuesta = $this->subir('FOTOGRAFIA', UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['archivo' => 'El archivo debe ser de tipo: jpg, jpeg, png.']);
    }

    public function test_archivo_de_mas_de_5_mb_es_rechazado(): void
    {
        $respuesta = $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv.pdf', 5121, 'application/pdf'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['archivo' => 'El archivo no debe pesar más de 5120 kilobytes.']);
    }

    public function test_formato_no_permitido_es_rechazado(): void
    {
        $respuesta = $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['archivo' => 'El archivo debe ser de tipo: pdf, jpg, jpeg, png.']);
    }

    public function test_no_se_cambian_documentos_si_requisitos_ya_fue_revisada(): void
    {
        $this->etapaRequisitos()->update(['estado' => EstadoEtapa::Aprobada]);

        $respuesta = $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'archivo' => 'La etapa de requisitos ya fue revisada; no se pueden cambiar los documentos.',
        ]);
    }

    public function test_otro_egresado_no_puede_subir_ni_descargar_documentos_ajenos(): void
    {
        $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))->assertCreated();
        $otro = $this->crearEgresado(Carrera::factory()->create());

        $subida = $this->actingAs($otro, 'sanctum')->post(
            route('egresado.documentos.store', $this->postulacion),
            ['tipo_documento' => 'CURRICULUM_VITAE', 'archivo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
            ['Accept' => 'application/json'],
        );
        $descarga = $this->actingAs($otro, 'sanctum')->getJson(route('egresado.documentos.archivo', DocumentoPostulacion::query()->value('id')));

        $subida->assertNotFound();
        $descarga->assertNotFound();
        $this->assertDatabaseCount('documento_postulacion', 1);
    }

    public function test_el_duenio_descarga_su_archivo(): void
    {
        $this->subir('CURRICULUM_VITAE', UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 contenido'))->assertCreated();

        $respuesta = $this->actingAs($this->usuario, 'sanctum')->get(route('egresado.documentos.archivo', DocumentoPostulacion::query()->value('id')));

        $respuesta->assertOk()->assertDownload('cv.pdf');
        $this->assertSame('%PDF-1.4 contenido', $respuesta->streamedContent());
    }

    private function subir(string $tipo, UploadedFile $archivo): TestResponse
    {
        return $this->actingAs($this->usuario, 'sanctum')->post(
            route('egresado.documentos.store', $this->postulacion),
            ['tipo_documento' => $tipo, 'archivo' => $archivo],
            ['Accept' => 'application/json'],
        );
    }

    private function etapaRequisitos(): PostulacionEtapa
    {
        return $this->postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn (Builder $etapa) => $etapa->where('codigo', 'REQUISITOS'))
            ->sole();
    }
}
