<?php

namespace ValidaDependenciaTeste\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity @ORM\Table(name="pessoa") */
class Pessoa
{
    /** @ORM\Id @ORM\Column(type="integer") */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $nome;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $situacao;

    /** @ORM\Column(type="string", nullable=true) */
    protected $sexo;

    /**
     * @ORM\ManyToOne(targetEntity="Banco")
     * @ORM\JoinColumn(name="banco", referencedColumnName="id", nullable=true)
     */
    protected $banco;

    public function __construct($id, $nome, $situacao, $sexo, ?Banco $banco = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->situacao = $situacao;
        $this->sexo = $sexo;
        $this->banco = $banco;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function getSituacao()
    {
        return $this->situacao;
    }

    public function getSexo()
    {
        return $this->sexo;
    }

    public function getBanco()
    {
        return $this->banco;
    }
}
