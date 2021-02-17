<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaDictionaryentryUse
 *
 * @ORM\Table(name="tipitaka_dictionaryentry_use", indexes={@ORM\Index(name="dictionaryentryid", columns={"dictionaryentryid"}), @ORM\Index(name="sentencetranslationid", columns={"sentencetranslationid"})})
 * @ORM\Entity
 */
class TipitakaDictionaryentryUse
{
    /**
     * @var int
     *
     * @ORM\Column(name="useid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $useid;

    /**
     * @var string
     *
     * @ORM\Column(name="paliword", type="string", length=255, nullable=false)
     */
    private $paliword;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="string", length=255, nullable=false)
     */
    private $translation;

    /**
     * @var TipitakaDictionaryentries
     *
     * @ORM\ManyToOne(targetEntity="TipitakaDictionaryentries")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dictionaryentryid", referencedColumnName="dictionaryentryid")
     * })
     */
    private $dictionaryentryid;

    /**
     * @var TipitakaSentenceTranslations
     *
     * @ORM\ManyToOne(targetEntity="TipitakaSentenceTranslations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sentencetranslationid", referencedColumnName="sentencetranslationid")
     * })
     */
    private $sentencetranslationid;

    public function getUseid(): ?int
    {
        return $this->useid;
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

    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function getDictionaryentryid(): ?TipitakaDictionaryentries
    {
        return $this->dictionaryentryid;
    }

    public function setDictionaryentryid(?TipitakaDictionaryentries $dictionaryentryid): self
    {
        $this->dictionaryentryid = $dictionaryentryid;

        return $this;
    }

    public function getSentencetranslationid(): ?TipitakaSentenceTranslations
    {
        return $this->sentencetranslationid;
    }

    public function setSentencetranslationid(?TipitakaSentenceTranslations $sentencetranslationid): self
    {
        $this->sentencetranslationid = $sentencetranslationid;

        return $this;
    }


}
