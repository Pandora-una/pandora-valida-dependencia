# Testes do pandora-valida-dependencia

Suíte de caracterização dos validadores `Dependencia` e `IfNotNull`: uma aplicação MVC mínima
(`tests/src/Aplicacao.php`) com o módulo carregado como nos sistemas, sobre SQLite em memória. Os
input filters são montados pelo `InputFilterManager` a partir de specs, e os validadores vêm do
`ValidatorManager`, o caminho dos sistemas. As respostas (`[válido, mensagens]`) são comparadas com
instantâneos em `tests/_instantaneos/`.

Os instantâneos foram **gravados na pilha de produção da 1.x**: o `vendor` do area-restrita
(zend-validator 2.11, zend-inputfilter 2.10, Doctrine ORM 2.5), PHP 7.2, com o código deste
repositório no lugar do instalado lá. A 3.x (Laminas, PHP 8.4) tem de reproduzir os mesmos arquivos.

`comparacao-numero-texto.json`, `nao-eh-editavel*.json` e `obrigatorio-true.json` fixam a
comparação solta (`==`) do PHP 7, que o PHP 8 mudou: número contra texto não numérico (`0 == ""`)
e texto numérico com espaço no fim (`"1" == "1 "`).

O campo validado vai como `null` quando o caso não o preenche: input opcional **ausente** dos dados
não chega aos validadores (caso "true e filho ausente").

## Rodar

```bash
# 3.x: vendor deste repositório
make test

# 1.x: vendor e imagem do area-restrita (PHPUnit 5.7 de lá), num checkout com o código 1.x
# (o commit da caracterização)
git worktree add /tmp/valida-1x <commit da caracterização>
/tmp/valida-1x/ci/teste-1x.sh [<caminho do srv do area-restrita>]

# regravar os instantâneos (só na 1.x, e só de propósito)
VALIDA_GRAVAR=1 /tmp/valida-1x/ci/teste-1x.sh
```
