<?php

namespace PandoraValidaDependencia;

use Psr\Container\ContainerInterface;

class DependenciaFactory
{
    /**
     * O container é o principal (no ServiceManager 3 o plugin manager entrega o dele às factories).
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $validator = new Dependencia($options ?? array());
        $validator->setEntityManager($container->get('Doctrine\ORM\EntityManager'));

        return $validator;
    }
}
