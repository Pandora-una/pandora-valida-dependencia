#!/bin/sh
# Roda a suíte na pilha 1.x (ZF2, PHP 7.2): vendor e imagem do area-restrita.
# Uso: ci/teste-1x.sh [<caminho do srv do area-restrita>] [argumentos do phpunit]
# Com VALIDA_GRAVAR=1 regrava os instantâneos (só aqui, e só de propósito).
set -e
SRV=${1:-$HOME/sistemas/area-restrita/srv}
[ $# -gt 0 ] && shift
exec docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(cd "$(dirname "$0")/.." && pwd)":/pacote -v "$SRV/vendor":/vendor-ar:ro -w /pacote \
  -e VALIDA_VENDOR=/vendor-ar -e VALIDA_GRAVAR \
  --entrypoint php "${IMAGEM:-area-restrita-backend}" /vendor-ar/bin/phpunit "$@"
