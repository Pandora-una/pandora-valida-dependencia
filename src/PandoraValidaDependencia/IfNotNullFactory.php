<?php

namespace PandoraValidaDependencia;

use Psr\Container\ContainerInterface;

class IfNotNullFactory
{
    /**
     * O container é o principal (no ServiceManager 3 o plugin manager entrega o dele às factories).
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $validator = new IfNotNull($options ?? array());
        $validator->setValidatorManager($container->get('ValidatorManager'));

        return $validator;
    }
}
