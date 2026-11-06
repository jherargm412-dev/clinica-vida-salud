<?php

namespace Tests\Unit;

use App\Modulos\citas\models\Cita;
use App\Modulos\pacientes\models\Paciente;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistorialCitasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('cita', function (Blueprint $table) {
            $table->increments('id_cita');
            $table->unsignedInteger('id_paciente');
            $table->unsignedInteger('id_medico');
            $table->unsignedInteger('id_especialidad');
            $table->string('codigo');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado');
            $table->dateTime('fecha_creacion');
            $table->string('motivo_cancelacion')->nullable();
        });
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombres');
            $table->string('apellidos');
        });
        Schema::create('paciente', function (Blueprint $table) {
            $table->increments('id_paciente');
            $table->unsignedInteger('id_usuario');
            $table->string('codigo');
            $table->string('ci');
            $table->boolean('activo');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cita');
        Schema::dropIfExists('paciente');
        Schema::dropIfExists('usuario');

        parent::tearDown();
    }

    public function test_historial_incluye_todos_los_estados_y_ordena_de_mas_reciente_a_mas_antigua(): void
    {
        $this->insertarCita('CIT-000001', '2026-03-01', 'Cancelada');
        $this->insertarCita('CIT-000002', '2026-05-01', 'Pendiente de pago');
        $this->insertarCita('CIT-000003', '2026-04-01', 'Inasistencia');

        $citas = Cita::listarPorPaciente(7)->get();

        $this->assertSame(
            ['CIT-000002', 'CIT-000003', 'CIT-000001'],
            $citas->pluck('codigo')->all()
        );
        $this->assertSame('Cancelada', $citas->last()->estado);
    }

    public function test_historial_filtra_por_estado_sin_perder_el_orden(): void
    {
        $this->insertarCita('CIT-000001', '2026-03-01', 'Cancelada');
        $this->insertarCita('CIT-000002', '2026-05-01', 'Cancelada');
        $this->insertarCita('CIT-000003', '2026-04-01', 'Programada');

        $citas = Cita::listarPorPaciente(7, 'Cancelada')->get();

        $this->assertSame(['CIT-000002', 'CIT-000001'], $citas->pluck('codigo')->all());
    }

    public function test_busqueda_por_ci_incluye_pacientes_inactivos(): void
    {
        DB::table('usuario')->insert([
            ['nombres' => 'Ana', 'apellidos' => 'Pérez'],
            ['nombres' => 'Luis', 'apellidos' => 'Gómez'],
        ]);
        DB::table('paciente')->insert([
            ['id_usuario' => 1, 'codigo' => 'PAC-0001', 'ci' => '123456', 'activo' => true],
            ['id_usuario' => 2, 'codigo' => 'PAC-0002', 'ci' => '123789', 'activo' => false],
        ]);

        $pacientes = Paciente::buscarPorCI('123')->get();

        $this->assertSame(['PAC-0001', 'PAC-0002'], $pacientes->pluck('codigo')->all());
    }

    public function test_pacientes_se_pueden_obtener_por_sus_ids(): void
    {
        DB::table('usuario')->insert([
            ['nombres' => 'Ana', 'apellidos' => 'Pérez'],
            ['nombres' => 'Luis', 'apellidos' => 'Gómez'],
        ]);
        DB::table('paciente')->insert([
            ['id_usuario' => 1, 'codigo' => 'PAC-0001', 'ci' => '123456', 'activo' => true],
            ['id_usuario' => 2, 'codigo' => 'PAC-0002', 'ci' => '123789', 'activo' => false],
        ]);

        $pacientes = Paciente::listarPorIds([2, 1]);

        $this->assertSame(['PAC-0001', 'PAC-0002'], $pacientes->pluck('codigo')->all());
    }

    private function insertarCita(string $codigo, string $fecha, string $estado): void
    {
        DB::table('cita')->insert([
            'id_paciente' => 7,
            'id_medico' => 1,
            'id_especialidad' => 1,
            'codigo' => $codigo,
            'fecha' => $fecha,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:30:00',
            'estado' => $estado,
            'fecha_creacion' => $fecha.' 08:00:00',
        ]);
    }
}
