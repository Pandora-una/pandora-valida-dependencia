<?php

namespace PandoraValidaDependencia;

return array(
    'validators' => array(
        'factories' => array(
            'PandoraValidaDependencia\Dependencia' => 'PandoraValidaDependencia\DependenciaFactory',
            'PandoraValidaDependencia\IfNotNull' => 'PandoraValidaDependencia\IfNotNullFactory',
        ),
        'aliases' => array(
            'Dependencia' => 'PandoraValidaDependencia\Dependencia',
            'IfNotNull' => 'PandoraValidaDependencia\IfNotNull',
        ),
    ),
);
