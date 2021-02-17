<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaIssues
 *
 * @ORM\Table(name="tipitaka_issues")
 * @ORM\Entity
 */
class TipitakaIssues
{
    /**
     * @var int
     *
     * @ORM\Column(name="tipitakaissueid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $tipitakaissueid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=1, nullable=false)
     */
    private $code;

    public function getTipitakaissueid(): ?int
    {
        return $this->tipitakaissueid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }


}
