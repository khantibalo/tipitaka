<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaSources
 *
 * @ORM\Table(name="tipitaka_sources", indexes={@ORM\Index(name="LanguageID", columns={"languageid"}), @ORM\Index(name="UserID", columns={"userid"})})
 * @ORM\Entity
 */
class TipitakaSources
{
    /**
     * @var int
     *
     * @ORM\Column(name="sourceid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sourceid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="ishidden", type="boolean", nullable=false)
     */
    private $ishidden = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="excludefromsearch", type="boolean", nullable=false)
     */
    private $excludefromsearch;

    /**
     * @var bool
     *
     * @ORM\Column(name="hasformatting", type="boolean", nullable=false)
     */
    private $hasformatting;

    /**
     * @var TipitakaLanguages
     *
     * @ORM\ManyToOne(targetEntity="TipitakaLanguages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="languageid", referencedColumnName="languageid")
     * })
     */
    private $languageid;

    /**
     * @var TipitakaUsers
     *
     * @ORM\ManyToOne(targetEntity="TipitakaUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="userid", referencedColumnName="userid")
     * })
     */
    private $userid;

    public function getSourceid(): ?int
    {
        return $this->sourceid;
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

    public function getIshidden(): ?bool
    {
        return $this->ishidden;
    }

    public function setIshidden(bool $ishidden): self
    {
        $this->ishidden = $ishidden;

        return $this;
    }

    public function getExcludefromsearch(): ?bool
    {
        return $this->excludefromsearch;
    }

    public function setExcludefromsearch(bool $excludefromsearch): self
    {
        $this->excludefromsearch = $excludefromsearch;

        return $this;
    }

    public function getHasformatting(): ?bool
    {
        return $this->hasformatting;
    }

    public function setHasformatting(bool $hasformatting): self
    {
        $this->hasformatting = $hasformatting;

        return $this;
    }

    public function getLanguageid(): ?TipitakaLanguages
    {
        return $this->languageid;
    }

    public function setLanguageid(?TipitakaLanguages $languageid): self
    {
        $this->languageid = $languageid;

        return $this;
    }

    public function getUserid(): ?TipitakaUsers
    {
        return $this->userid;
    }

    public function setUserid(?TipitakaUsers $userid): self
    {
        $this->userid = $userid;

        return $this;
    }


}
