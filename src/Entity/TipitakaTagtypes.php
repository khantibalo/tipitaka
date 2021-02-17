<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaTagtypes
 *
 * @ORM\Table(name="tipitaka_tagtypes")
 * @ORM\Entity
 */
class TipitakaTagtypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="tagtypeid", type="integer", nullable=false)
     * @ORM\Id
     */
    private $tagtypeid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    public function getTagtypeid(): ?int
    {
        return $this->tagtypeid;
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


}
