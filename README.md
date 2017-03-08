# pandora-valida-dependencia

Um validador para criar regras em campos que dependem de outros campos

### Instalação

A forma recomendada de instalação é por [composer](https://getcomposer.org/):
```
    {
        "require": {
            "pandora-una/pandora-valida-dependencia": "1.3.*"
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
* **tem_valor**: O valor que o campo checado deve ter para que alguma regra se aplique, caso hajam dois valores diferentes para a aplicação da regra, este campo aceita um array
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

Neste caso o campo "filho" é obrigatório apenas se o campo "tem_filho" tiver *true* como valor.

Um exemplo para uma regra que se aplica a dois valores do mesmo campo seria:

```php
    $this->add(array(
        'name' => 'status',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'justificativa',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campo' => 'status',
                    'tem_valor' => array('aprovada', 'nao_aprovada'),
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                    'entidade' => 'Application\Entity\Despesa',
                ),
            ),
        ),
    ));
```

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
* **se_campos_tem_valores**: Recebe uma lista de campos e valores dos quais todos deverão ser satisfeitos no contexto para a regra se aplicar. Caso este campo seja preenchido os campos *se\_campo* e *tem\_valor* deixam de ser obrigatórios

Exemplo de uso de *da_associacao*:

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

Exemplo de uso de *se_campo_tem_valor*

```php
    $this->add(array(
        'name' => 'status',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'precisa_cotacao',
        'required' => true,
    ));
    $this->add(array(
        'name' => 'valor',
        'required' => false,
        'continue_if_empty' => true,
        'filters' => array(),
        'validators' => array(
            array(
                'name' => 'Dependencia',
                'options' => array(
                    'se_campos_tem_valores' => array(
                        'precisa_cotacao' => true,
                        'status' => 'incluido',
                    ),
                    'este_campo' => Dependencia::DEVE_SER_NULL,
                    'caso_contrario' => Dependencia::EH_OBRIGATORIO,
                    'entidade' => 'Application\Entity\ContPaga',
                ),
            ),
        ),
    ));
```
### Classes Auxiliares


#### IfNotNull

Para auxiliar a validar campos com obrigatoriedade condicional essa biblioteca disponibiliza um validador genérico que aplica uma validação específica apenas se o campo não for vazio

**opções:**

* *validator*: O validador a ser aplicado se o campo não for null
* *override_message* (opcional): A mensagem de erro quando o validador falhar
* *override_messages* (opcional): Lista que sobrescreve as mensagens de erro do validador

**exemplo de uso:**

```php
    $this->add(array(
        'name'              => 'novoEmail',
        'required'          => false,
        'continue_if_empty' => true,
        'filters' => array(
            array(
                'name' => 'StripTags',
            ),
            array(
                'name' => 'StringTrim',
            ),
        ),
        'validators' => array(
            array(
                'name'    => 'IfNotNull',
                'options' => array(
                    'validator'        => 'EmailAddress',
                    'override_messages' => array(
                        EmailAddress::LENGTH_EXCEEDED => "O email é longo demais",
                    ),
                ),
            ),
            array(
                'name'    => 'Dependencia',
                'options' => array(
                    'se_campo'   => 'email',
                    'tem_valor'  => PessoaFisicaRepository::EMAIL_NOVO,
                    'este_campo' => Dependencia::EH_OBRIGATORIO,
                ),
            ),
        ),
    ));
```