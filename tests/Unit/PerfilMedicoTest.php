<?php

namespace Tests\Unit;

use App\Modulos\pacientes\models\PerfilMedico;
use App\Modulos\pacientes\request\gestionar_perfil_medico\PerfilMedicoRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PerfilMedicoTest extends TestCase
{
    public function test_perfil_medico_puede_estar_vacio(): void
    {
        $this->assertTrue(PerfilMedico::validarDatos([]));
    }

    public function test_perfil_medico_valida_el_tipo_de_sangre_y_el_limite_de_texto(): void
    {
        $this->assertTrue(PerfilMedico::validarDatos([
            'tipo_sangre' => 'AB+',
            'alergias' => str_repeat('a', 500),
        ]));
        $this->assertFalse(PerfilMedico::validarDatos(['tipo_sangre' => 'C+']));
        $this->assertFalse(PerfilMedico::validarDatos(['medicamentos' => str_repeat('a', 501)]));
    }

    #[DataProvider('datosContactoInvalido')]
    public function test_perfil_medico_rechaza_contacto_incompleto_o_telefono_invalido(array $datos): void
    {
        $this->assertFalse(PerfilMedico::validarDatos($datos));
    }

    public function test_request_acepta_perfil_vacio_y_contacto_completo(): void
    {
        $this->assertTrue($this->validarRequest([])->passes());
        $this->assertTrue($this->validarRequest([
            'tipo_sangre' => 'O-',
            'nombre_contacto_emergencia' => 'Ana',
            'parentesco_contacto_emergencia' => 'Hermana',
            'telefono_contacto_emergencia' => '7000-0000',
        ])->passes());
    }

    #[DataProvider('datosRequestInvalidos')]
    public function test_request_rechaza_datos_que_no_cumplen_las_reglas(array $datos, string $campo): void
    {
        $this->assertTrue($this->validarRequest($datos)->errors()->has($campo));
    }

    private function validarRequest(array $datos): ValidatorInstance
    {
        $request = new PerfilMedicoRequest;

        return Validator::make($datos, $request->rules(), $request->messages());
    }

    public static function datosRequestInvalidos(): array
    {
        return [
            'tipo de sangre invalido' => [['tipo_sangre' => 'C+'], 'tipo_sangre'],
            'texto demasiado largo' => [['alergias' => str_repeat('a', 501)], 'alergias'],
            'contacto incompleto' => [['nombre_contacto_emergencia' => 'Ana'], 'parentesco_contacto_emergencia'],
            'telefono invalido' => [[
                'nombre_contacto_emergencia' => 'Ana',
                'parentesco_contacto_emergencia' => 'Hermana',
                'telefono_contacto_emergencia' => 'abc',
            ], 'telefono_contacto_emergencia'],
        ];
    }

    public static function datosContactoInvalido(): array
    {
        return [
            'contacto incompleto' => [['nombre_contacto_emergencia' => 'Ana']],
            'telefono invalido' => [[
                'nombre_contacto_emergencia' => 'Ana',
                'parentesco_contacto_emergencia' => 'Hermana',
                'telefono_contacto_emergencia' => 'abc',
            ]],
        ];
    }
}
