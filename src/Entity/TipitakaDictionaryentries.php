<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaDictionaryentries
 *
 * @ORM\Table(name="tipitaka_dictionaryentries", indexes={@ORM\Index(name="DictionaryTypeID", columns={"dictionarytypeid"}), @ORM\Index(name="paliword", columns={"paliword"})})
 * @ORM\Entity
 */
class TipitakaDictionaryentries
{
    /**
     * @var int
     *
     * @ORM\Column(name="dictionaryentryid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $dictionaryentryid;

    /**
     * @var string
     *
     * @ORM\Column(name="paliword", type="string", length=255, nullable=false)
     */
    private $paliword;

    /**
     * @var string
     *
     * @ORM\Column(name="paliwordnodiac", type="string", length=255, nullable=false)
     */
    private $paliwordnodiac;

    /**
     * @var string|null
     *
     * @ORM\Column(name="explanation", type="text", length=65535, nullable=true)
     */
    private $explanation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="explanation_plain", type="text", length=65535, nullable=true)
     */
    private $explanationPlain;

    /**
     * @var string|null
     *
     * @ORM\Column(name="explanationids", type="string", length=1000, nullable=true)
     */
    private $explanationIds;

    /**
     * @var string|null
     *
     * @ORM\Column(name="translation", type="string", length=255, nullable=true)
     */
    private $translation;

    /**
     * @var TipitakaDictionarytypes
     *
     * @ORM\ManyToOne(targetEntity="TipitakaDictionarytypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dictionarytypeid", referencedColumnName="dictionarytypeid")
     * })
     */
    private $dictionarytypeid;

    public function getDictionaryentryid(): ?int
    {
        return $this->dictionaryentryid;
    }

    public function getPaliword(): ?string
    {
        return $this->paliword;
    }

    public function setPaliword(string $paliword): self
    {
        $this->paliword = $paliword;

        return $this;
    }

    public function getPaliwordnodiac(): ?string
    {
        return $this->paliwordnodiac;
    }

    public function setPaliwordnodiac(string $paliwordnodiac): self
    {
        $this->paliwordnodiac = $paliwordnodiac;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): self
    {
        $this->explanation = $explanation;

        return $this;
    }

    public function getExplanationPlain(): ?string
    {
        return $this->explanationPlain;
    }

    public function setExplanationPlain(?string $explanationPlain): self
    {
        $this->explanationPlain = $explanationPlain;

        return $this;
    }

    public function getExplanationIds(): ?string
    {
        return $this->explanationIds;
    }

    public function setExplanationIds(?string $explanationIds): self
    {
        $this->explanationIds = $explanationIds;

        return $this;
    }

    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    public function setTranslation(?string $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function getDictionarytypeid(): ?TipitakaDictionarytypes
    {
        return $this->dictionarytypeid;
    }

    public function setDictionarytypeid(?TipitakaDictionarytypes $dictionarytypeid): self
    {
        $this->dictionarytypeid = $dictionarytypeid;

        return $this;
    }


}
