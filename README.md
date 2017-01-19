# pandora-valida-dependencia

Um validador para criar regras em campos que dependem de outros campos

### Instalação

A forma recomendada de instalação é por [composer](https://getcomposer.org/):
```
    {
        "require": {
            "pandora-una/pandora-valida-dependencia": "1.1.*"
        }
    }
```

É necessário também adicionar o módulo PandoraValidaDependencia no seu application.config.php

```php
    $modules = array(
        'DoctrineModule',
        'DoctrineORMModule',
        'PandoraValidaDependencia',
        'Application', // o application fica por ultimo pq ele pode sobrescrever as configurações dos demais
    );
```

### Uso

O validador dependência cria regras condicionais para os campos de um formulário. ele tem 4 parâmetros obrigatórios nos options:

* **se_campo**: O campo que será checado para validar o campo atual
* **tem_valor**: O valor que o campo checado deve ter para que alguma regra se aplique
* **este_campo**: A regra que será aplicada ao campo a ser validado caso o campo checado tenha o valor esperado
* **entidade**: O nome da entidade do doctrine que está sendo validada

Um exemplo de uso seria:

```php
    $this->add(array(
        'name' => 'tem_filho',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'filho',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'tem_filho',
                    'tem_valor' => true,
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                    'entidade' => 'Application\Entity\Pessoa',
                ),
            ),
        ),
    ));
```

Neste caso o campo "filho" é obrigatório apenas se o campo "tem_filho" tiver *true* como valor

#### Regras

O validador tem no momento 4 regras possíveis

* **Dependencia::EH_OBRIGATORIO**: O valor do campo sendo validado deve ser diferente de null
* **Dependencia::DEVE_SER_NULL**: O valor do campo sendo validado deve ser igual a null
* **Dependencia::EH_OPCIONAL**: Aceita qualquer valor
* **Dependencia::NAO_EH_EDITAVEL**: O valor do campo sendo validado deve ser igual ao valor registrado no banco de dados.

A regra Dependencia::NAO_EH_EDITAVEL funciona apenas se a chave primária da entidade sendo validada estiver no formulário. Além disso é necessário passar o nome do campo nos options como no exemplo a seguir:

```php
    $this->add(array(
        'name' => 'id',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'status',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'valor',
        'required' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'status',
                    'tem_valor' => 'Finalizado',
                    'este_campo' => Dependencia::NAO_EH_EDITAVEL,
                    'entidade' => 'Application\Entity\Pagamento',
                    'campo' => 'valor',
                ),
            ),
        ),
    ));
```

#### Campos opcionais

O validador dependência aceita mais dois campos opcionais:

* **da_associacao**: O campo da associação da entidade a ser validada que o campo a ser checado se encontra.
* **caso_contrario**: A regra de validação caso o valor do campo checado seja diferente do esperado.

Exemplo de uso de *da_associacao*

```php
    $this->add(array(
        'name' => 'evento',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'documento',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'precisa_documento',
                    'da_associacao' => 'evento',
                    'tem_valor' => 'true',
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                    'entidade' => 'Application\Entity\Convidado',
                ),
            ),
        ),
    ));
```

Exemplo de uso de *caso_contrario*

```php
    $this->add(array(
        'name' => 'tipo',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'pessoa_fisica',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'tipo',
                    'tem_valor' => 'Pessoa Física',
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                    'caso_contrario' => Dependencia::DEVE_SER_NULL,
                    'entidade' => 'Application\Entity\Fornecedor',
                ),
            ),
        ),
    ));
```

No caso do campo *da_associação*, também é possível declarar campos aninhados, como no exemplo a seguir:

```php
    $this->add(array(
        'name' => 'formaPagamento',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'numeroCheque',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'exigeCheque',
                    'da_associacao' => 'formaPagamento.tipoPagamento',
                    'tem_valor' => 'true',
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                    'entidade' => 'Application\Entity\Convidado',
                ),
            ),
        ),
    ));
```