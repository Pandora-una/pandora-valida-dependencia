<?php

namespace ValidaDependenciaTeste;

use PHPUnit\Framework\TestCase;

/**
 * Caracterização do validador IfNotNull: aplica outro validador só quando o valor não é nulo, com
 * as opções que os sistemas usam (StringLength, Digits, EmailAddress, Date, InArray, mensagem própria).
 */
class IfNotNullTest extends TestCase
{
    private function valida(array $opcoes, array $valores)
    {
        $app = new Aplicacao();
        $resultado = [];
        foreach ($valores as $valor) {
            $resultado[var_export($valor, true)] = $app->valida([[
                'name' => 'campo',
                'required' => false,
                'continue_if_empty' => true,
                'validators' => [['name' => 'IfNotNull', 'options' => $opcoes]],
            ]], ['campo' => $valor]);
        }
        return $resultado;
    }

    public function testStringLength()
    {
        Instantaneo::confere('stringlength', [
            'sem mensagem' => $this->valida(['validator' => 'StringLength', 'max' => 3], [null, '', 'abc', 'abcd']),
            'override_message' => $this->valida(
                ['validator' => 'StringLength', 'min' => 6, 'max' => 12, 'override_message' => 'A senha não pode ter mais de 12 caracteres e menos de 6 caracteres'],
                ['abc', 'abcdefg', str_repeat('a', 13)]
            ),
            'override_messages' => $this->valida(
                ['validator' => 'StringLength', 'max' => 3, 'override_messages' => ['stringLengthTooLong' => 'Grande demais']],
                ['abcd']
            ),
        ]);
    }

    public function testOutrosValidadores()
    {
        Instantaneo::confere('outros-validadores', [
            'Digits' => $this->valida(['validator' => 'Digits'], [null, '123', '12a', 12]),
            'EmailAddress' => $this->valida(['validator' => 'EmailAddress', 'override_message' => 'O email não parece ser um email válido'], ['a@b.com', 'x']),
            'Date' => $this->valida(['validator' => 'Date', 'format' => 'Y-m-d'], ['2026-02-28', '2026-02-30', '28/02/2026']),
            'InArray' => $this->valida(['validator' => 'InArray', 'haystack' => ['A', 'B']], ['A', 'C', 0]),
        ]);
    }
}
