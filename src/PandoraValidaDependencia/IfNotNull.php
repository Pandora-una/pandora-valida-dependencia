<?php

namespace PandoraValidaDependencia;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\ValidatorPluginManager;

class IfNotNull extends AbstractValidator
{
    /**
     * @var ValidatorPluginManager
     */
    protected $validatorManager;

    public function setValidatorManager(ValidatorPluginManager $validatorManager)
    {
        $this->validatorManager = $validatorManager;
        return $this;
    }

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
        if ($valor !== null) {
            $validator = $this->validatorManager->get($this->getOption('validator'), $this->getOptions());
            if (array_key_exists('override_message', $this->getOptions())) {
                $validator->setMessage($this->getOption('override_message'));
            }
            if (array_key_exists('override_messages', $this->getOptions())) {
                $validator->setMessages($this->getOption('override_messages'));
            }
            if (!$validator->isValid($valor)) {
                $this->abstractOptions['messages'] = $this->mensagensComoNoZf2($validator, $valor);
                return false;
            }
        }
        return true;
    }

    /**
     * As opções do IfNotNull vão inteiras para o validador interno, inclusive o messageTemplates vazio
     * dele, e validadores como Digits, InArray e Date ficam sem o template do erro. No ZF2 a mensagem
     * saía com a chave e valor null ({"notDigits": null}); o laminas-validator não grava mensagem
     * null, e a chave sumiria (há cenário que a confere). As chaves vêm de uma cópia do validador
     * sem as opções internas do IfNotNull.
     */
    protected function mensagensComoNoZf2($validator, $valor)
    {
        $mensagens = $validator->getMessages();
        if ($mensagens !== array()) {
            return $mensagens;
        }
        $opcoes = $this->getOptions();
        unset($opcoes['messageTemplates'], $opcoes['messageVariables'], $opcoes['messages']);
        $copia = $this->validatorManager->get($this->getOption('validator'), $opcoes);
        $copia->isValid($valor);
        return array_fill_keys(array_keys($copia->getMessages()), null);
    }
}
