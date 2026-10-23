<?php

namespace ValidaDependenciaTeste;

use ValidaDependenciaTeste\Entity\Banco;
use ValidaDependenciaTeste\Entity\Pessoa;

/** Registros de apoio: pessoa 1 (situação 0, banco 10) e pessoa 2 (situação 5, sem banco). */
class Dados
{
    public static function grava(Aplicacao $app)
    {
        $em = $app->entityManager();
        $banco = new Banco(10, '001', 4);
        $em->persist($banco);
        $em->persist(new Banco(11, '', 0));
        $em->persist(new Pessoa(1, 'Ana', 0, 'F', $banco));
        $em->persist(new Pessoa(2, 'Beto', 5, 'M'));
        $em->flush();
        $em->clear();
    }
}
