<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalClienteTest extends TestCase
{
    public function test_no_se_pueden_listar_ordenes_sin_autenticacion(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes'
        );

        $response->assertStatus(401);
    }

    public function test_no_se_puede_consultar_orden_sin_autenticacion(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/1'
        );

        $response->assertStatus(401);
    }

    public function test_no_se_pueden_consultar_seguimientos_sin_autenticacion(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/1/seguimientos'
        );

        $response->assertStatus(401);
    }

    public function test_no_se_pueden_consultar_evidencias_sin_autenticacion(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/1/evidencias'
        );

        $response->assertStatus(401);
    }

    public function test_no_se_puede_descargar_archivo_sin_autenticacion(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/1/evidencias/1/archivo'
        );

        $response->assertStatus(401);
    }

    public function test_el_identificador_de_orden_debe_ser_numerico(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/abc'
        );

        $response->assertStatus(404);
    }

    public function test_el_identificador_de_evidencia_debe_ser_numerico(): void
    {
        $response = $this->getJson(
            '/api/portal/ordenes/1/evidencias/abc/archivo'
        );

        $response->assertStatus(404);
    }
}