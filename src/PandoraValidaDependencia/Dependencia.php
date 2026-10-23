<?php

namespace PandoraValidaDependencia;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Validator\AbstractValidator;

class Dependencia extends AbstractValidator
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    //Regras
    const EH_OBRIGATORIO  = 'ehObrigatorio';
    const DEVE_SER_NULL   = 'deveSerNull';
    const EH_OPCIONAL     = 'ehOpcional';
    const NAO_EH_EDITAVEL = 'naoEhEditavel';

    protected $messageVariables = array(
        'campo'      => 'campoContexo',
        'valor'      => 'valorEsperado',
        'comparacao' => 'resultadoComparacao',
    );

    protected $messageTemplates = array(
        self::EH_OBRIGATORIO  => 'Este campo é obrigatório quando %campo% %comparacao% %valor%',
        self::DEVE_SER_NULL   => 'Este campo não deve ser preenchido quando %campo% %comparacao% %valor%',
        self::NAO_EH_EDITAVEL => 'Este campo não deve ser alterado quando %campo% %comparacao% %valor%',
    );

    public $campoContexo;
    public $valorEsperado;
    public $resultadoComparacao;
    public $comparacao;

    /**
     * Determines if valid.
     *
     * @param      mixed    $valor     O valor a ser validado
     * @param      array    $contexto  O contexo do formulário
     *
     * @return     boolean  True if valid, False otherwise.
     */
    public function isValid($valor, $contexto = null)
    {
        $this->montaComparacao();
        $regra = $this->getOption('este_campo');

        $valoresContexto = $this->extraiValores($contexto);
        if ($this->comparaValores($valoresContexto)) {
            if (count($this->comparacao) > 1) {
                $this->resultadoComparacao = 'são iguais a';
            } else {
                $this->resultadoComparacao = 'é igual a';
            }

            return $this->aplicaRegra($valor, $regra, $contexto);
        } elseif ($this->hasOption('caso_contrario')) {
            $this->resultadoComparacao = 'é diferente de';
            $regraNegativa             = $this->getOption('caso_contrario');

            return $this->aplicaRegra($valor, $regraNegativa, $contexto);
        }

        return true;
    }

    /**
     * Decide se o valor é valido de acordo com a regra informada.
     *
     * @param      mixed   $valor     O valor do campo sendo validado
     * @param      string  $regra     A regra para validar
     * @param      array   $contexto  The contexto
     * @param      bool  $negativa  true quando a regra é negativa
     *
     * @return     bool    true se for válido, false se não for
     */
    protected function aplicaRegra($valor, $regra, array $contexto)
    {
        return $this->{$regra}($valor, $contexto);
    }

    /**
     * Compara o valor do contexto com os valores separados
     *
     * @param      array|mixed  $valoresEsperados  Os valores esperados pela
     *                                             regra
     * @param      mixed        $valorContexto     O valor no contexto
     *
     * @return     boolean      true se algum dos valores esperados for igual o do contexto, false caso contrário
     */
    protected function comparaValores(array $valoresContexto)
    {
        foreach ($this->comparacao as $campo => $valores) {
            $comparacao = false;
            foreach ($valores as $valor) {
                if (self::igualNoPhp7($valor, $valoresContexto[$campo])) {
                    $this->comparacao[$campo] = array($valor);
                    $comparacao               = true;
                    break;
                }
            }
            if (!$comparacao) {
                $this->comparacao         = array();
                $this->comparacao[$campo] = $valores;
                return false;
            }
        }
        return true;
    }

    /**
     * Valida se o campo não foi preenchido.
     *
     * @param      mixed  $valor  valor do campo sendo validado
     *
     * @return     bool   true se for válido, false se não for
     */
    protected function deveSerNull($valor)
    {
        if ($valor !== null) {
            $this->preparaErro(self::DEVE_SER_NULL);

            return false;
        }

        return true;
    }

    /**
     * Valida se o valor foi preencido.
     *
     * @param      mixed  $valor  valor do campo sendo validado
     *
     * @return     bool   true se for válido, false se não for
     */
    protected function ehObrigatorio($valor)
    {
        if ($valor === null) {
            $this->preparaErro(self::EH_OBRIGATORIO);

            return false;
        }

        return true;
    }

    /**
     * Aceita qualquer valor.
     *
     * @param      mixed  $valor  valor do campo sendo validado
     *
     * @return     true
     */
    protected function ehOpcional($valor)
    {
        return true;
    }

    /**
     * Encontra a entidade do formulário de acordo com o id no contexto
     *
     * @param      string  $entidade  The entidade
     * @param      array   $contexto  The contexto
     *
     * @return     mixed   A entidade encontrada ou null se não achar
     */
    protected function encontraEntidade($entidade, array $contexto)
    {
        $em         = $this->getEntityManager();
        $repository = $this->getRepository($entidade);
        $metadata   = $em->getClassMetadata($entidade);
        $identifier = $metadata->getIdentifier();

        $identifier = array_flip($identifier);

        foreach ($identifier as $field => $value) {
            if (!isset($contexto[$field])) {
                return;
            }
            $identifier[$field] = $contexto[$field];
        }

        if ($identifier) {
            return $repository->find($identifier);
        }

        return;
    }

    /**
     * Descobre o valor do contexto que será usado para aplicar a regra.
     *
     * @param      string       $campoContexo  O campo que contém o valor
     * @param      array        $contexto      Os valores enviados para o
     *                                         formulário
     * @param      string|null  $associacao    A associação que o valor deve ser
     *                                         buscado
     *
     * @return     mixed        o valor do campo no contexto
     */
    protected function extraiValor($campoContexo, array $contexto, $associacao = null)
    {
        if ($associacao) {
            $associacoes = explode('.', $associacao);
            $entidade    = $this->getOption('entidade');

            if ($this->hasOption('do_callback')) {
                $entityAssociacao = $this->getAssociacao($entidade, $associacoes, $contexto);
            } elseif (array_key_exists($associacoes[0], $contexto)) {
                if ($contexto[$associacoes[0]] === null) {
                    return;
                }
                $entityAssociacao = $this->getAssociacao($entidade, $associacoes, $contexto);
            } else {
                $entityAssociacao = $this->encontraEntidade($entidade, $contexto);
                foreach ($associacoes as $associacao) {
                    $entityAssociacao = $entityAssociacao->{'get'.ucfirst($associacao)}();
                    if (!$entityAssociacao) {
                        return;
                    }
                }
            }

            if (!$entityAssociacao) {
                return;
            }

            return $entityAssociacao->{'get'.ucfirst($campoContexo)}();
        }

        if ($this->hasOption('entidade') && !isset($contexto[$campoContexo])) {
            $entidade = $this->getOption('entidade');
            $antigo   = $this->encontraEntidade($entidade, $contexto);
            if (!$antigo) {
                return;
            }
            return $this->getValorAntigo($antigo, $campoContexo);
        }

        // campo ausente do contexto: null, como no PHP 7 (que só dava NOTICE)
        return $contexto[$campoContexo] ?? null;
    }

    /**
     * Extrai todos os valoes do contexto necessários para a comparacção
     *
     * @param      array  $contexto  The contexto
     *
     * @return     array  Os valores extraidos
     */
    protected function extraiValores(array $contexto)
    {
        $valores = array();
        foreach ($this->comparacao as $chave => $valor) {
            $associacao = null;
            if (strpos($chave, '.') !== false) {
                $associacao = explode('.', $chave);
                $campo      = array_pop($associacao);
                $associacao = implode('.', $associacao);
            } else {
                $campo = $chave;
            }
            $valores[$chave] = $this->extraiValor($campo, $contexto, $associacao);
        }
        return $valores;
    }

    /**
     * Encontra a entidade de uma associação
     *
     * @param      string  $entidadeNome  The entidade nome
     * @param      array   $associacoes   The associacoes
     * @param      array   $contexto      The contexto
     *
     * @return     mixed   The associacao.
     */
    protected function getAssociacao($entidadeNome, array $associacoes, array $contexto)
    {
        $em = $this->getEntityManager();
        $associacao = array_shift($associacoes);

        if ($this->hasOption('do_callback')) {
            $callbackOptions = $this->getOption('do_callback');
            if (!isset($callbackOptions['callback']) || !is_callable($callbackOptions['callback'])) {
                throw new \Exception("Opção do_callback -> callback não informada ou inválida", 500);
            }
            $entidade = call_user_func($callbackOptions['callback']);
        } else {
            $metadata           = $em->getClassMetadata($entidadeNome);
            $entidadeAssociacao = $metadata->getAssociationMapping($associacao)['targetEntity'];
            $entidade           = $this->encontraEntidade($entidadeAssociacao, $contexto[$associacao]);
        }

        foreach ($associacoes as $assoc) {
            if (!$entidade) {
                return null;
            }
            $entidade = $entidade->{'get'.ucfirst($assoc)}();
        }

        return $entidade;
    }

    /**
     * Gets the entity manager.
     *
     * @return     Doctrine\ORM\EntityManager  The entity manager.
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * A comparação solta (==) do PHP 7, que as regras dos sistemas foram escritas para usar.
     *
     * O PHP 8 mudou dois casos: número contra texto não numérico (no 7 o texto virava número, e
     * 0 == "" e 1 == "1abc" eram verdadeiros; no 8 o número vira texto) e texto numérico com
     * espaço no fim (no 7 "1 " não era numérico, e "1" == "1 " era falso; no 8 é verdadeiro).
     * Fixado pelos instantâneos gravados no PHP 7.2 (tests/_instantaneos/comparacao-*.json).
     *
     * @param      mixed  $a
     * @param      mixed  $b
     *
     * @return     bool
     */
    protected static function igualNoPhp7($a, $b)
    {
        $numero = function ($valor) {
            return is_int($valor) || is_float($valor);
        };
        if (is_string($a) && $numero($b)) {
            list($a, $b) = array($b, $a);
        }
        if ($numero($a) && is_string($b)) {
            if (! self::numericoNoPhp7($b)) {
                // o texto vira número pelo prefixo numérico ("1abc" é 1, "abc" e "" são 0)
                return $a == (float) $b;
            }
            return $a == $b;
        }
        if (is_string($a) && is_string($b) && ! (self::numericoNoPhp7($a) && self::numericoNoPhp7($b))) {
            return $a === $b;
        }

        return $a == $b;
    }

    /**
     * is_numeric() do PHP 7: o do 8 aceita também espaço no fim.
     *
     * @param      string  $valor
     *
     * @return     bool
     */
    protected static function numericoNoPhp7($valor)
    {
        return is_numeric($valor) && rtrim($valor, " \t\n\r\v\f") === $valor;
    }

    /**
     * retorna o repositório de uma entidade
     *
     * @param      mixed                           $entity  The entity
     *
     * @return     Doctrine\ORM\EntityRespository  The repository.
     */
    protected function getRepository($entity)
    {
        $em = $this->getEntityManager();

        return $em->getRepository($entity);
    }

    /**
     * Encontra o valor antigo de um campo
     *
     * @param      mixed   $entidade  O entidade
     * @param      string  $campo     O campo
     *
     * @return     mixed   O valor antigo.
     */
    protected function getValorAntigo($entidade, $campo)
    {
        $em           = $this->getEntityManager();
        $metadata     = $em->getClassMetadata(get_class($entidade));
        $ehAssociacao = $metadata->hasAssociation($campo);

        if ($ehAssociacao) {
            return $entidade->{'get'.ucfirst($campo)}()->getId();
        } else {
            return $entidade->{'get'.ucfirst($campo)}();
        }
    }

    /**
     * Determines if it has option.
     *
     * @param      string  $option
     *
     * @return     bool    True if has option, False otherwise.
     */
    protected function hasOption($option)
    {
        return array_key_exists($option, $this->getOptions());
    }

    /**
     * Monta a estrutura para a comparação
     *
     * @throws     \Exception  Lança erro se a option não informar as comparações
     */
    protected function montaComparacao()
    {
        if ($this->hasOption('se_campos_tem_valores')) {
            $this->comparacao = $this->getOption('se_campos_tem_valores');
        } elseif ($this->hasOption('se_campo') && $this->hasOption('tem_valor')) {
            $this->comparacao = array();
            $campo            = $this->getOption('se_campo');
            if ($this->hasOption('da_associacao')) {
                $campo = $this->getOption('da_associacao') . '.' . $campo;
            }
            if ($this->hasOption('do_callback')) {
                $callback = $this->getOption('do_callback');
                if (isset($callback['campo'])) {
                    $campo = $callback['campo'] . '.' . $campo;
                } else {
                    $campo = 'callback' . '.' . $campo;
                }
            }
            $this->comparacao[$campo] = $this->getOption('tem_valor');
        } else {
            throw new \Exception("É necessário informar os campos 'se_valor, 'tem_valor. Ou então o campo 'se_campos_tem_valores'", 500);
        }
        foreach ($this->comparacao as $campo => $valor) {
            if (!is_array($valor)) {
                $this->comparacao[$campo] = array($valor);
            }
        }
    }

    /**
     * Valida se  o valor informado é o mesmo que está no banco de dados.
     *
     * @param      mixed  $valor     valor do campo sendo validado
     * @param      array  $contexto  The contexto
     *
     * @return     bool   true se for válido, false se não for
     */
    protected function naoEhEditavel($valor, $contexto)
    {
        $entidade = $this->getOption('entidade');
        $campo    = $this->getOption('campo');
        $antigo   = $this->encontraEntidade($entidade, $contexto);

        if ($antigo && ! self::igualNoPhp7($this->getValorAntigo($antigo, $campo), $valor)) {
            $this->preparaErro(self::NAO_EH_EDITAVEL);

            return false;
        }

        return true;
    }

    /**
     * Prepara variáveis para a mensagem de erro.
     *
     * @param      string  $erro   O tipo do erro
     */
    protected function preparaErro($erro)
    {
        if (count($this->comparacao) > 1) {
            $chaves  = array_keys($this->comparacao);
            $valores = array_values($this->comparacao);

            $this->campoContexo  = $this->stringfy($chaves);
            $this->valorEsperado = $this->stringfy($valores);
            $this->valorEsperado .= ' respectivamente';
        } else {
            $this->campoContexo  = $this->stringfy(array_keys($this->comparacao));
            $this->valorEsperado = $this->stringfy(array_values($this->comparacao), ' e de ');
        }

        $this->error($erro);
    }

    /**
     * Transforma um array em uma string
     *
     * @param      array   $dados         o array a ser transformado
     * @param      string  $ligacaoFinal  a ultima string para ligar os valores
     *
     * @return     string  uma versão do array em string
     */
    protected function stringfy(array $dados, $ligacaoFinal = ' e ')
    {
        $dados = array_map(function ($value) use ($ligacaoFinal) {
            if (is_array($value)) {
                return $this->stringfy($value, $ligacaoFinal);
            }
            return var_export($value, true);
        }, $dados);
        $ultimoDado = array_pop($dados);
        if (count($dados) > 0) {
            return implode(',', $dados) . $ligacaoFinal . $ultimoDado;
        }
        return $ultimoDado;
    }
}
