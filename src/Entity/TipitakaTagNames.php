<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaTagNames
 *
 * @ORM\Table(name="tipitaka_tag_names", indexes={@ORM\Index(name="LanguageID", columns={"languageid"}), @ORM\Index(name="TagID", columns={"tagid"})})
 * @ORM\Entity
 */
class TipitakaTagNames
{
    /**
     * @var int
     *
     * @ORM\Column(name="tagnameid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $tagnameid;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string",length=255, nullable=false)
     */
    private $title;

    /**
     * @var TipitakaTags
     *
     * @ORM\ManyToOne(targetEntity="TipitakaTags")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tagid", referencedColumnName="tagid")
     * })
     */
    private $tagid;

    /**
     * @var TipitakaLanguages
     *
     * @ORM\ManyToOne(targetEntity="TipitakaLanguages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="languageid", referencedColumnName="languageid")
     * })
     */
    private $languageid;

    public function getTagnameid(): ?int
    {
        return $this->tagnameid;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTagid(): ?TipitakaTags
    {
        return $this->tagid;
    }

    public function setTagid(?TipitakaTags $tagid): self
    {
        $this->tagid = $tagid;

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


}
