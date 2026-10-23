<?php

namespace ValidaDependenciaTeste;

use Doctrine\ORM\Tools\SchemaTool;

/**
 * Aplicação MVC mínima com o módulo PandoraValidaDependencia carregado como nos sistemas, sobre
 * SQLite em memória. filtro() monta um input filter pelo InputFilterManager, e os validadores vêm
 * do ValidatorManager a partir da spec, como nos input filters dos sistemas.
 */
class Aplicacao
{
    private $aplicacao;

    public function __construct()
    {
        $classe = class_exists('Laminas\Mvc\Application') && self::laminas()
            ? 'Laminas\Mvc\Application' : 'Zend\Mvc\Application';
        if (class_exists('Zend\Console\Console')) {
            \Zend\Console\Console::overrideIsConsole(false);
        }
        // por arquivo e não por 'extra_config': no ZF2 o extra_config entra antes da configuração
        // dos módulos, e eles o sobrescreveriam; o glob entra por último nas duas pilhas
        $arquivo = sys_get_temp_dir() . '/valida-teste-' . getmypid() . '.config.php';
        file_put_contents($arquivo, '<?php return ' . var_export($this->config(), true) . ';');
        $modulos = self::laminas()
            ? ['Laminas\Router', 'Laminas\Validator', 'Laminas\InputFilter', 'Laminas\Cache',
                'Laminas\Cache\Storage\Adapter\Memory', 'DoctrineModule', 'DoctrineORMModule']
            : ['DoctrineModule', 'DoctrineORMModule'];
        $this->aplicacao = $classe::init([
            'modules' => array_merge($modulos, ['PandoraValidaDependencia', 'ValidaDependenciaTeste']),
            'module_listener_options' => ['module_paths' => [], 'config_glob_paths' => [$arquivo]],
        ]);
        $em = $this->entityManager();
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    public static function laminas()
    {
        static $laminas = null;
        if ($laminas === null) {
            $classe = class_exists('Laminas\Mvc\Application') ? 'Laminas\Mvc\Application' : 'Zend\Mvc\Application';
            $laminas = strpos((new \ReflectionClass($classe))->getName(), 'Laminas\\') === 0;
        }
        return $laminas;
    }

    /** @return \Doctrine\ORM\EntityManager */
    public function entityManager()
    {
        return $this->aplicacao->getServiceManager()->get('doctrine.entitymanager.orm_default');
    }

    /**
     * Input filter com os inputs da spec, montado pelo InputFilterManager.
     *
     * @return \Laminas\InputFilter\InputFilter
     */
    public function filtro(array $inputs)
    {
        $filtro = $this->aplicacao->getServiceManager()->get('InputFilterManager')
            ->get(self::laminas() ? 'Laminas\InputFilter\InputFilter' : 'Zend\InputFilter\InputFilter');
        foreach ($inputs as $input) {
            $filtro->add($input);
        }
        return $filtro;
    }

    /** Valida os dados e devolve [válido, mensagens]. */
    public function valida(array $inputs, array $dados)
    {
        $filtro = $this->filtro($inputs);
        $filtro->setData($dados);
        $valido = $filtro->isValid();
        return [$valido, $filtro->getMessages()];
    }

    private function config()
    {
        $sqlite = class_exists('Doctrine\DBAL\Driver\PDO\SQLite\Driver')
            ? 'Doctrine\DBAL\Driver\PDO\SQLite\Driver' : 'Doctrine\DBAL\Driver\PDOSqlite\Driver';
        return [
            'doctrine' => [
                'configuration' => [
                    'orm_default' => ['proxy_dir' => sys_get_temp_dir() . '/valida-teste-proxies'],
                ],
                'connection' => [
                    'orm_default' => ['driverClass' => $sqlite, 'params' => ['memory' => true]],
                ],
                'driver' => [
                    'teste' => [
                        'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                        'paths' => [__DIR__ . '/Entity'],
                    ],
                    'orm_default' => ['drivers' => ['ValidaDependenciaTeste\Entity' => 'teste']],
                ],
            ],
            'router' => ['routes' => []],
        ];
    }
}
