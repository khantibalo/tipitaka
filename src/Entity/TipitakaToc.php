<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaToc
 *
 * @ORM\Table(name="tipitaka_toc", indexes={@ORM\Index(name="ParentID", columns={"parentid"}), @ORM\Index(name="Path", columns={"path"}), @ORM\Index(name="TitleTypeID", columns={"titletypeid"})})
 * @ORM\Entity(repositoryClass="App\Repository\TipitakaTocRepository")
 */
class TipitakaToc
{
    /**
     * @var int
     *
     * @ORM\Column(name="nodeid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $nodeid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parentid", type="integer", nullable=true)
     */
    private $parentid;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=300, nullable=false)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="titlenodiac", type="string", length=300, nullable=false)
     */
    private $titlenodiac;

    /**
     * @var bool
     *
     * @ORM\Column(name="haschildnodes", type="boolean", nullable=false, options={"default"="1"})
     */
    private $haschildnodes = '1';

    /**
     * @var string|null
     *
     * @ORM\Column(name="textpath", type="string", length=500, nullable=true)
     */
    private $textpath;

    /**
     * @var int
     *
     * @ORM\Column(name="linkscount", type="integer", nullable=false)
     */
    private $linkscount = '0';

    /**
     * @var TipitakaTitletypes
     *
     * @ORM\ManyToOne(targetEntity="TipitakaTitletypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="titletypeid", referencedColumnName="titletypeid")
     * })
     */
    private $titletypeid;
    
    /**
     * @var int
     *
     * @ORM\Column(name="minvolumenumber", type="integer", nullable=false)
     */
    private $MinVolumeNumber;
    
    /**
     * @var int
     *
     * @ORM\Column(name="maxvolumenumber", type="integer", nullable=false)
     */
    private $MaxVolumeNumber;
    
    /**
     * @var int
     *
     * @ORM\Column(name="minpagenumber", type="integer", nullable=false)
     */
    private $MinPageNumber;
    
    /**
     * @var int
     *
     * @ORM\Column(name="maxpagenumber", type="integer", nullable=false)
     */
    private $MaxPageNumber;
    
    /**
     * @var int
     *
     * @ORM\Column(name="hastranslation", type="boolean", nullable=false, options={"default"="0"})
     */
    private $HasTranslation;
    
    /**
     * @var int
     *
     * @ORM\Column(name="hastableview", type="boolean", nullable=false, options={"default"="0"})
     */    
    private $HasTableView;
    
    
    /**
     * @var int
     *
     * @ORM\Column(name="ishidden", type="boolean", nullable=false, options={"default"="0"})
     */
    private $IsHidden;
    
    
    /**
     * @var TipitakaSources
     *
     * @ORM\ManyToOne(targetEntity="TipitakaSources")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="translationsourceid", referencedColumnName="sourceid")
     * })
     */
    private $TranslationSourceID;
    
    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", nullable=true)
     */
    private $notes;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="disableview", type="boolean", nullable=false, options={"default"="0"})
     */
    private $disableview;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="hasprologue", type="boolean", nullable=false, options={"default"="0"})
     */
    private $hasprologue;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="disabletranslalign", type="boolean", nullable=false, options={"default"="0"})
     */
    private $disabletranslalign;
        
    /**
     * @var bool
     *
     * @ORM\Column(name="allowptspage", type="boolean", nullable=false, options={"default"="0"})
     */
    private $allowptspage;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="urlpart", type="string", length=20, nullable=true)
     */
    private $urlpart;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="urlfull", type="string", length=255, nullable=true)
     */
    private $urlfull;
    
    /**
     * @return \App\Entity\TipitakaSources
     */
    public function getTranslationSourceID(): ?TipitakaSources
    {
        return $this->TranslationSourceID;
    }

    /**
     * @param \App\Entity\TipitakaSources $TranslationSourceID
     */
    public function setTranslationSourceID(?TipitakaSources $TranslationSourceID): self
    {
        $this->TranslationSourceID = $TranslationSourceID;
        return $this;
    }

    /**
     * @return number
     */
    public function getIsHidden(): ?bool
    {
        return $this->IsHidden;
    }

    /**
     * @param number $IsHidden
     */
    public function setIsHidden($IsHidden): self
    {
        $this->IsHidden = $IsHidden;
        
        return $this;
    }

    /**
     * @return number
     */
    public function getHasTableView(): ?bool
    {
        return $this->HasTableView;
    }

    /**
     * @param number $HasTableView
     */
    public function setHasTableView(bool $HasTableView): self
    {
        $this->HasTableView = $HasTableView;
        
        return $this;
    }

    /**
     * @return number
     */
    public function getHasTranslation(): ?bool
    {
        return $this->HasTranslation;
    }

    /**
     * @param number $HasTranslation
     */
    public function setHasTranslation(bool $HasTranslation): self
    {
        $this->HasTranslation = $HasTranslation;
        
        return $this;
    }

    public function getNodeid(): ?int
    {
        return $this->nodeid;
    }

    public function getParentid(): ?int
    {
        return $this->parentid;
    }

    public function setParentid(?int $parentid): self
    {
        $this->parentid = $parentid;

        return $this;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getTitlenodiac(): ?string
    {
        return $this->titlenodiac;
    }

    public function setTitlenodiac(string $titlenodiac): self
    {
        $this->titlenodiac = $titlenodiac;

        return $this;
    }

    public function getAllowptspage(): ?bool
    {
        return $this->allowptspage;
    }

    public function setAllowptspage(bool $allowptspage): self
    {
        $this->allowptspage = $allowptspage;

        return $this;
    }

    public function getHaschildnodes(): ?bool
    {
        return $this->haschildnodes;
    }

    public function setHaschildnodes(bool $haschildnodes): self
    {
        $this->haschildnodes = $haschildnodes;

        return $this;
    }

    public function getTextpath(): ?string
    {
        return $this->textpath;
    }

    public function setTextpath(?string $textpath): self
    {
        $this->textpath = $textpath;

        return $this;
    }

    public function getLinkscount(): ?int
    {
        return $this->linkscount;
    }

    public function setLinkscount(int $linkscount): self
    {
        $this->linkscount = $linkscount;

        return $this;
    }

    public function getTitletypeid(): ?TipitakaTitletypes
    {
        return $this->titletypeid;
    }

    public function setTitletypeid(?TipitakaTitletypes $titletypeid): self
    {
        $this->titletypeid = $titletypeid;

        return $this;
    }

    
    /**
     * @return number
     */
    public function getMinVolumeNumber()
    {
        return $this->MinVolumeNumber;
    }
    
    /**
     * @return number
     */
    public function getMaxVolumeNumber()
    {
        return $this->MaxVolumeNumber;
    }
    
    /**
     * @return number
     */
    public function getMinPageNumber()
    {
        return $this->MinPageNumber;
    }
    
    /**
     * @return number
     */
    public function getMaxPageNumber()
    {
        return $this->MaxPageNumber;
    }
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getDisableview(): bool
    {
        return $this->disableview;
    }

    public function setDisableview(bool $disableview): self
    {
        $this->disableview = $disableview;
        return $this;
    }

    public function getHasprologue(): bool
    {
        return $this->hasprologue;
    }
    
    public function setHasprologue(bool $hasprologue): self
    {
        $this->hasprologue = $hasprologue;
        return $this;
    }
    
    public function getDisableTranslAlign(): bool
    {
        return $this->disabletranslalign;
    }
    
    public function setDisableTranslAlign(bool $disabletranslalign): self
    {
        $this->disabletranslalign = $disabletranslalign;
        return $this;
    }
    /**
     * @param number $MinVolumeNumber
     */
    public function setMinVolumeNumber($MinVolumeNumber)
    {
        $this->MinVolumeNumber = $MinVolumeNumber;
    }

    /**
     * @param number $MaxVolumeNumber
     */
    public function setMaxVolumeNumber($MaxVolumeNumber)
    {
        $this->MaxVolumeNumber = $MaxVolumeNumber;
    }

    /**
     * @param number $MinPageNumber
     */
    public function setMinPageNumber($MinPageNumber)
    {
        $this->MinPageNumber = $MinPageNumber;
    }

    /**
     * @param number $MaxPageNumber
     */
    public function setMaxPageNumber($MaxPageNumber)
    {
        $this->MaxPageNumber = $MaxPageNumber;
    }
    /**
     * @return boolean
     */
    public function isAllowptspage()
    {
        return $this->allowptspage;
    }

    public function getUrlpart(): ?string
    {
        return $this->urlpart;
    }
    
    public function setUrlpart(?string $urlpart): static
    {
        $this->urlpart = $urlpart;
        
        return $this;
    }
    
    public function getUrlfull(): ?string
    {
        return $this->urlfull;
    }
    
    public function setUrlfull(?string $urlfull): static
    {
        $this->urlfull = $urlfull;
        
        return $this;
    }

}
