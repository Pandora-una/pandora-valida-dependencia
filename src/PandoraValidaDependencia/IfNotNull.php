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
                $this->abstractOptions['messages'] = $validator->getMessages();
                return false;
            }
        }
        return true;
    }
}
