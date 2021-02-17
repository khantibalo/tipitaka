<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaParagraphtypes
 *
 * @ORM\Table(name="tipitaka_paragraphtypes", uniqueConstraints={@ORM\UniqueConstraint(name="Name", columns={"name"})})
 * @ORM\Entity
 */
class TipitakaParagraphtypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="paragraphtypeid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $paragraphtypeid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    public function getParagraphtypeid(): ?int
    {
        return $this->paragraphtypeid;
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
