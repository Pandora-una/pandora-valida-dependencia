<?php

namespace PandoraValidaDependencia;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Validator\AbstractValidator;

class Dependencia extends AbstractValidator implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

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

    /**
     * Determines if valid.
     *
     * @param      <type>   $valor     The valor
     * @param      <type>   $contexto  The contexto
     * @param      $valor     O       valor a ser validado
     * @param      $contexto  O       contexo do formulário
     *
     * @return     boolean  True if valid, False otherwise.
     */
    public function isValid($valor, $contexto = null)
    {
        $this->campoContexo  = $this->getOption('se_campo');
        $this->valorEsperado = $this->getOption('tem_valor');
        $regra               = $this->getOption('este_campo');

        $valorContexto = $this->extraiValor($this->campoContexo, $contexto);
        if ($this->comparaValores($this->valorEsperado, $valorContexto)) {
            $this->resultadoComparacao = 'é igual a';

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
    protected function comparaValores($valoresEsperados, $valorContexto)
    {
        if (!is_array($valoresEsperados)) {
            $valoresEsperados = array($valoresEsperados);
        }
        foreach ($valoresEsperados as $valorEsperado) {
            if ($valorEsperado == $valorContexto) {
                $this->valorEsperado = $valorEsperado;
                return true;
            }
        }
        return false;
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
     * @param      string  $campoContexo  O campo que contém o valor
     * @param      array   $contexto      Os valores enviados para o formulário
     *
     * @return     mixed   o valor do campo no contexto
     */
    protected function extraiValor($campoContexo, array $contexto)
    {
        if ($this->hasOption('da_associacao')) {
            $associacoes = explode('.', $this->getOption('da_associacao'));
            $entidade    = $this->getOption('entidade');

            if (array_key_exists($associacoes[0], $contexto)) {
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

        if (!isset($contexto[$campoContexo])) {
            $entidade = $this->getOption('entidade');
            $antigo   = $this->encontraEntidade($entidade, $contexto);
            if (!$antigo) {
                return;
            }
            return $this->getValorAntigo($antigo, $campoContexo);
        }

        return $contexto[$campoContexo];
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

        $associacao         = array_shift($associacoes);
        $metadata           = $em->getClassMetadata($entidadeNome);
        $entidadeAssociacao = $metadata->getAssociationMapping($associacao)['targetEntity'];
        $entidade           = $this->encontraEntidade($entidadeAssociacao, $contexto[$associacao]);


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
        return $this->getServiceLocator()->getServiceLocator()->get('Doctrine\ORM\EntityManager');
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
        return isset($this->getOptions()[$option]);
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

        if ($antigo && $this->getValorAntigo($antigo, $campo) != $valor) {
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
        if (!is_array($this->valorEsperado)) {
            $this->valorEsperado = array($this->valorEsperado);
        }

        foreach ($this->valorEsperado as $key => $value) {
            if (is_bool($value)) {
                $this->valorEsperado[$key] = $this->valorEsperado ? 'true' : 'false';
            }

            if ($value === null) {
                $this->valorEsperado[$key] = 'null';
            }
        }

        $this->valorEsperado = implode(' e de ', $this->valorEsperado);

        if ($this->hasOption('da_associacao')) {
            $this->campoContexo = $this->getOption('da_associacao').'.'.$this->campoContexo;
        }

        $this->error($erro);
    }
}
