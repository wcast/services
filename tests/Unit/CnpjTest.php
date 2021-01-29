<?php

namespace WCast\Services\Tests\Unit;

use Tests\TestCase;

use WCast\Services\Cnpj;

class CnpjTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $consulta = new Cnpj();

        $consulta->consultaCNPJ(['cnpj' => '', 'captcha' => '']);

        $this->assertTrue(true);
    }
}
