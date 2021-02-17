<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaParagraphs
 *
 * @ORM\Table(name="tipitaka_paragraphs", indexes={@ORM\Index(name="NodeID", columns={"nodeid"}), @ORM\Index(name="text", columns={"text"}), @ORM\Index(name="paragraphtypeid", columns={"paragraphtypeid"})})
 * @ORM\Entity
 */
class TipitakaParagraphs
{
    /**
     * @var int
     *
     * @ORM\Column(name="paragraphid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $paragraphid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="paranum", type="integer", nullable=true)
     */
    private $paranum;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", length=65535, nullable=false)
     */
    private $text;

    /**
     * @var bool
     *
     * @ORM\Column(name="hastranslation", type="boolean", nullable=false)
     */
    private $hastranslation = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="caps", type="string", length=2000, nullable=true)
     */
    private $caps;

    /**
     * @var TipitakaParagraphtypes
     *
     * @ORM\ManyToOne(targetEntity="TipitakaParagraphtypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="paragraphtypeid", referencedColumnName="paragraphtypeid")
     * })
     */
    private $paragraphtypeid;

    /**
     * @var TipitakaToc
     *
     * @ORM\ManyToOne(targetEntity="TipitakaToc")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="nodeid", referencedColumnName="nodeid")
     * })
     */
    private $nodeid;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="bold", type="string", length=2000, nullable=true)
     */
    private $bold;

    public function getParagraphid(): ?int
    {
        return $this->paragraphid;
    }

    public function getParanum(): ?int
    {
        return $this->paranum;
    }

    public function setParanum(?int $paranum): self
    {
        $this->paranum = $paranum;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getHastranslation(): ?bool
    {
        return $this->hastranslation;
    }

    public function setHastranslation(bool $hastranslation): self
    {
        $this->hastranslation = $hastranslation;

        return $this;
    }

    public function getCaps(): ?string
    {
        return $this->caps;
    }

    public function setCaps(?string $caps): self
    {
        $this->caps = $caps;

        return $this;
    }

    public function getParagraphtypeid(): ?TipitakaParagraphtypes
    {
        return $this->paragraphtypeid;
    }

    public function setParagraphtypeid(?TipitakaParagraphtypes $paragraphtypeid): self
    {
        $this->paragraphtypeid = $paragraphtypeid;

        return $this;
    }

    public function getNodeid(): ?TipitakaToc
    {
        return $this->nodeid;
    }

    public function setNodeid(?TipitakaToc $nodeid): self
    {
        $this->nodeid = $nodeid;

        return $this;
    }

    public function getBold(): ?string
    {
        return $this->bold;
    }
    
    public function setBold(?string $bold): self
    {
        $this->bold = $bold;
        
        return $this;
    }
}
