<?php

namespace ValidaDependenciaTeste\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity @ORM\Table(name="banco") */
class Banco
{
    /** @ORM\Id @ORM\Column(type="integer") */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $codigo;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $tipo;

    public function __construct($id, $codigo, $tipo)
    {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->tipo = $tipo;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function getTipo()
    {
        return $this->tipo;
    }
}
