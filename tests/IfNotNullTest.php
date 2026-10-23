<?php

namespace ValidaDependenciaTeste;

use PHPUnit\Framework\TestCase;

/**
 * Caracterização do validador IfNotNull: aplica outro validador só quando o valor não é nulo, com
 * as opções que os sistemas usam (StringLength, Digits, EmailAddress, Date, InArray, mensagem própria).
 */
class IfNotNullTest extends TestCase
{
    /**
     * Única diferença aceita entre as pilhas. O IfNotNull repassa as opções dele ao validador
     * interno, inclusive o messageTemplates vazio, e validadores como InArray e Date ficam sem o
     * template do erro. Na 1.x a mensagem saía null ({"notInArray": null}); o laminas-validator não
     * grava mensagem null, e o campo fica inválido sem mensagem. Caso real: meioCorrespondencia em
     * FormaPagamento\InputFilter\Alterar (area-restrita).
     */
    public static function semMensagensNulas(array $instantaneo)
    {
        $limpa = function ($valor) use (&$limpa) {
            if (! is_array($valor)) {
                return $valor;
            }
            $saida = [];
            foreach ($valor as $chave => $item) {
                if ($item !== null || ! is_string($chave) || ctype_digit($chave)) {
                    $saida[$chave] = $limpa($item);
                }
            }
            return $saida;
        };
        return $limpa($instantaneo);
    }

    private function confere($nome, array $resultado)
    {
        Instantaneo::confere($nome, $resultado, Aplicacao::laminas() ? [self::class, 'semMensagensNulas'] : null);
    }

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
        $this->confere('stringlength', [
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
        $this->confere('outros-validadores', [
            'Digits' => $this->valida(['validator' => 'Digits'], [null, '123', '12a', 12]),
            'EmailAddress' => $this->valida(['validator' => 'EmailAddress', 'override_message' => 'O email não parece ser um email válido'], ['a@b.com', 'x']),
            'Date' => $this->valida(['validator' => 'Date', 'format' => 'Y-m-d'], ['2026-02-28', '2026-02-30', '28/02/2026']),
            'InArray' => $this->valida(['validator' => 'InArray', 'haystack' => ['A', 'B']], ['A', 'C', 0]),
        ]);
    }
}
