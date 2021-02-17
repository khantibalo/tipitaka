<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaDictionarytypes
 *
 * @ORM\Table(name="tipitaka_dictionarytypes")
 * @ORM\Entity
 */
class TipitakaDictionarytypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="dictionarytypeid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $dictionarytypeid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=4, nullable=false)
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="sortorder", type="integer", nullable=true)
     */
    private $sortorder;
    
    public function getDictionarytypeid(): ?int
    {
        return $this->dictionarytypeid;
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

    public function getSortorder(): ?int
    {
        return $this->sortorder;
    }
    
    public function setSortorder(int $sortorder): self
    {
        $this->sortorder=$sortorder;
        
        return $this;
    }
}
