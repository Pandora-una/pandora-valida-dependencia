<?php

namespace ValidaDependenciaTeste;

use PandoraValidaDependencia\Dependencia;
use PHPUnit\Framework\TestCase;

/**
 * Caracterização do validador Dependencia, com as opções que os sistemas usam (area-restrita,
 * panoptico-web, emg-subsedes, sep-subsedes, adp): regra quando outro campo tem um valor, regra
 * contrária, vários campos, valor antigo da entidade, associação, callback e "não é editável".
 */
class DependenciaTest extends TestCase
{
    private function input($nome, array $opcoes)
    {
        return [
            'name' => $nome,
            'required' => false,
            'continue_if_empty' => true,
            'validators' => [['name' => 'Dependencia', 'options' => $opcoes]],
        ];
    }

    /** Roda a mesma regra para cada contexto e devolve [válido, mensagens] de cada um. */
    private function casos(array $inputsExtras, array $opcoes, array $contextos, $campo = 'filho')
    {
        $app = new Aplicacao();
        Dados::grava($app);
        $resultado = [];
        foreach ($contextos as $nome => $dados) {
            // o campo validado vai como null (JSON com o campo vazio), a menos que o caso diga que
            // ele não veio: input opcional ausente não chega aos validadores
            if (array_key_exists('(ausente)', $dados)) {
                unset($dados['(ausente)']);
            } elseif (! array_key_exists($campo, $dados)) {
                $dados[$campo] = null;
            }
            $inputs = array_merge([$this->input($campo, $opcoes)], $inputsExtras);
            $resultado[$nome] = $app->valida($inputs, $dados);
        }
        return $resultado;
    }

    private function semFiltro($nome)
    {
        return ['name' => $nome, 'required' => false, 'allow_empty' => true];
    }

    public function testObrigatorioQuandoOutroCampoETrue()
    {
        Instantaneo::confere('obrigatorio-true', $this->casos(
            [$this->semFiltro('tem_filho')],
            ['se_campo' => 'tem_filho', 'tem_valor' => true, 'este_campo' => Dependencia::EH_OBRIGATORIO],
            [
                'true sem filho' => ['tem_filho' => true],
                'true com filho' => ['tem_filho' => true, 'filho' => 'Caio'],
                '"1" sem filho' => ['tem_filho' => '1'],
                '1 sem filho' => ['tem_filho' => 1],
                '"S" sem filho' => ['tem_filho' => 'S'],
                'false sem filho' => ['tem_filho' => false],
                '"0" sem filho' => ['tem_filho' => '0'],
                '0 sem filho' => ['tem_filho' => 0],
                '"" sem filho' => ['tem_filho' => ''],
                'null sem filho' => ['tem_filho' => null],
                'só o filho' => ['filho' => 'Caio'],
                'vazio' => [],
                'true e filho ausente' => ['tem_filho' => true, '(ausente)' => true],
            ]
        ));
    }

    public function testNullComRegraContraria()
    {
        Instantaneo::confere('null-caso-contrario', $this->casos(
            [$this->semFiltro('afastamento')],
            ['se_campo' => 'afastamento', 'tem_valor' => null, 'este_campo' => Dependencia::DEVE_SER_NULL,
                'caso_contrario' => Dependencia::EH_OBRIGATORIO],
            [
                'null sem motivo' => ['afastamento' => null],
                'null com motivo' => ['afastamento' => null, 'motivo' => 'x'],
                '"" com motivo' => ['afastamento' => '', 'motivo' => 'x'],
                '0 com motivo' => ['afastamento' => 0, 'motivo' => 'x'],
                'data sem motivo' => ['afastamento' => '2026-01-01'],
                'data com motivo' => ['afastamento' => '2026-01-01', 'motivo' => 'x'],
            ],
            'motivo'
        ));
    }

    /**
     * tem_valor inteiro ou texto contra valores do formulário: é onde a comparação solta (==) do
     * PHP 8 difere da do 7 (número contra texto não numérico)
     */
    public function testComparacaoDeNumeroComTexto()
    {
        $contextos = [];
        foreach ([1, '1', ' 1', '1 ', '1.0', '1abc', 'abc', '', 0, '0', null, true, false] as $valor) {
            $contextos['meio = ' . var_export($valor, true)] = ['meio' => $valor];
        }
        $resultado = [];
        foreach ([1, 0, 4, [1, 4], 'S', '1', 'abc', ''] as $esperado) {
            $resultado['tem_valor ' . json_encode($esperado)] = $this->casos(
                [$this->semFiltro('meio')],
                ['se_campo' => 'meio', 'tem_valor' => $esperado, 'este_campo' => Dependencia::EH_OBRIGATORIO],
                $contextos,
                'endereco'
            );
        }
        Instantaneo::confere('comparacao-numero-texto', $resultado);
    }

    public function testVariosCampos()
    {
        Instantaneo::confere('varios-campos', $this->casos(
            [$this->semFiltro('tipo'), $this->semFiltro('ativo')],
            ['se_campos_tem_valores' => ['tipo' => [1, 2], 'ativo' => true], 'este_campo' => Dependencia::EH_OBRIGATORIO],
            [
                'os dois' => ['tipo' => 2, 'ativo' => true],
                'os dois, preenchido' => ['tipo' => 2, 'ativo' => true, 'filho' => 'x'],
                'só tipo' => ['tipo' => 1, 'ativo' => false],
                'só ativo' => ['tipo' => 3, 'ativo' => true],
            ]
        ));
    }

    public function testValorAntigoDaEntidade()
    {
        // o campo comparado não veio: vale o que está gravado na entidade do id do contexto
        Instantaneo::confere('valor-antigo', $this->casos(
            [$this->semFiltro('id'), $this->semFiltro('situacao')],
            ['se_campo' => 'situacao', 'tem_valor' => 5, 'este_campo' => Dependencia::EH_OBRIGATORIO,
                'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa'],
            [
                'pessoa 2 (situação 5) sem filho' => ['id' => 2],
                'pessoa 1 (situação 0) sem filho' => ['id' => 1],
                'pessoa 1 mandando situação 5' => ['id' => 1, 'situacao' => 5],
                'pessoa inexistente' => ['id' => 99],
                'sem id' => [],
            ]
        ));
    }

    public function testValorAntigoDeAssociacaoEOId()
    {
        Instantaneo::confere('valor-antigo-associacao', $this->casos(
            [$this->semFiltro('id'), $this->semFiltro('banco')],
            ['se_campo' => 'banco', 'tem_valor' => 10, 'este_campo' => Dependencia::EH_OBRIGATORIO,
                'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa'],
            ['pessoa 1 (banco 10)' => ['id' => 1]]
        ));
    }

    public function testCampoDaAssociacao()
    {
        $opcoes = ['se_campo' => 'tipo', 'da_associacao' => 'banco', 'tem_valor' => 4,
            'este_campo' => Dependencia::EH_OBRIGATORIO, 'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa'];
        Instantaneo::confere('da-associacao', $this->casos(
            [$this->semFiltro('id'), $this->semFiltro('banco')],
            $opcoes,
            [
                'banco 10 (tipo 4) no contexto' => ['banco' => ['id' => 10]],
                'banco 11 (tipo 0) no contexto' => ['banco' => ['id' => 11]],
                'banco inexistente' => ['banco' => ['id' => 99]],
                'banco null' => ['banco' => null],
                'sem banco: o da pessoa 1' => ['id' => 1],
            ]
        ));
    }

    public function testDoCallback()
    {
        $app = new Aplicacao();
        Dados::grava($app);
        $em = $app->entityManager();
        $resultado = [];
        foreach ([10, 11, 99] as $id) {
            $opcoes = [
                'se_campo' => 'codigo',
                'tem_valor' => '001',
                'do_callback' => ['callback' => function () use ($em, $id) {
                    return $em->find('ValidaDependenciaTeste\Entity\Banco', $id);
                }],
                'este_campo' => Dependencia::EH_OBRIGATORIO,
                'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa',
            ];
            $resultado["banco $id"] = $app->valida([$this->input('filho', $opcoes)], ['filho' => null]);
        }
        Instantaneo::confere('do-callback', $resultado);
    }

    public function testNaoEhEditavel()
    {
        $opcoes = ['se_campo' => 'id', 'tem_valor' => [1, 2], 'este_campo' => Dependencia::NAO_EH_EDITAVEL,
            'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa', 'campo' => 'situacao'];
        $contextos = [];
        foreach ([0, '0', '', null, 'abc', 5, '5', '5abc', false] as $valor) {
            $contextos['pessoa 1 (situação 0), nova ' . var_export($valor, true)] = ['id' => 1, 'situacao' => $valor];
            $contextos['pessoa 2 (situação 5), nova ' . var_export($valor, true)] = ['id' => 2, 'situacao' => $valor];
        }
        Instantaneo::confere('nao-eh-editavel', $this->casos([$this->semFiltro('id')], $opcoes, $contextos, 'situacao'));
    }

    public function testNaoEhEditavelComTexto()
    {
        $opcoes = ['se_campo' => 'id', 'tem_valor' => 1, 'este_campo' => Dependencia::NAO_EH_EDITAVEL,
            'entidade' => 'ValidaDependenciaTeste\Entity\Pessoa', 'campo' => 'sexo'];
        $contextos = [];
        foreach (['F', 'f', 'M', 0, '', null, true] as $valor) {
            $contextos['pessoa 1 (F), novo ' . var_export($valor, true)] = ['id' => 1, 'sexo' => $valor];
        }
        Instantaneo::confere('nao-eh-editavel-texto', $this->casos([$this->semFiltro('id')], $opcoes, $contextos, 'sexo'));
    }

    public function testSemAsOpcoesDeComparacao()
    {
        $app = new Aplicacao();
        try {
            $app->valida([$this->input('filho', ['este_campo' => Dependencia::EH_OBRIGATORIO])], ['filho' => null]);
            $this->fail('deveria recusar');
        } catch (\Exception $e) {
            Instantaneo::confere('sem-opcoes', [get_class($e), $e->getCode(), $e->getMessage()]);
        }
    }
}
