<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaTitletypes
 *
 * @ORM\Table(name="tipitaka_titletypes")
 * @ORM\Entity
 */
class TipitakaTitletypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="titletypeid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $titletypeid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="canview", type="boolean", nullable=false)
     */
    private $canview;

    public function getTitletypeid(): ?int
    {
        return $this->titletypeid;
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

    public function getCanview(): ?bool
    {
        return $this->canview;
    }

    public function setCanview(bool $canview): self
    {
        $this->canview = $canview;

        return $this;
    }


}
