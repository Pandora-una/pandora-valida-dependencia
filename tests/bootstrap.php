<?php
/**
 * A mesma suíte roda nas duas pilhas:
 * - 1.x (ZF2, PHP 7.2): VALIDA_VENDOR aponta para o vendor de um sistema (o area-restrita), com as
 *   versões de produção; o código deste repositório entra na frente do instalado lá;
 * - 3.x (Laminas, PHP 8.4): o vendor deste repositório.
 * O código de teste usa os nomes Laminas; na 1.x um autoloader cria os aliases para Zend\.
 */
date_default_timezone_set('America/Sao_Paulo');

$vendor = getenv('VALIDA_VENDOR') ?: __DIR__ . '/../vendor';
$autoload = require $vendor . '/autoload.php';
$autoload->addPsr4('ValidaDependenciaTeste\\', __DIR__ . '/src');

if (getenv('VALIDA_VENDOR')) {
    $autoload->add('PandoraValidaDependencia', __DIR__ . '/../src', true);
    require_once __DIR__ . '/../Module.php';
    // o ZF2 avisa com E_USER_DEPRECATED a cada uso do service locator; em produção isso só vai
    // para o log, e o PHPUnit 5.7 transformaria em exceção (notice e warning continuam virando)
    if (class_exists('PHPUnit_Framework_Error_Deprecated')) {
        \PHPUnit_Framework_Error_Deprecated::$enabled = false;
    }
}

if (! is_dir($vendor . '/laminas/laminas-mvc')) {
    spl_autoload_register(function ($classe) {
        if (strpos($classe, 'Laminas\\') === 0) {
            $original = 'Zend\\' . substr($classe, strlen('Laminas\\'));
            if (class_exists($original) || interface_exists($original)) {
                class_alias($original, $classe);
            }
        }
    });
}
