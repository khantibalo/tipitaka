<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaCollectionItemNames
 *
 * @ORM\Table(name="tipitaka_collection_item_names", indexes={@ORM\Index(name="CollectionItemID", columns={"collectionitemid"}), @ORM\Index(name="LanguageID", columns={"languageid"})})
 * @ORM\Entity
 */
class TipitakaCollectionItemNames
{
    /**
     * @var int
     *
     * @ORM\Column(name="itemnameid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemnameid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var TipitakaCollectionItems
     *
     * @ORM\ManyToOne(targetEntity="TipitakaCollectionItems")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collectionitemid", referencedColumnName="collectionitemid")
     * })
     */
    private $collectionitemid;

    /**
     * @var TipitakaLanguages
     *
     * @ORM\ManyToOne(targetEntity="TipitakaLanguages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="languageid", referencedColumnName="languageid")
     * })
     */
    private $languageid;

    public function getItemnameid(): ?int
    {
        return $this->itemnameid;
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

    public function getCollectionitemid(): ?TipitakaCollectionItems
    {
        return $this->collectionitemid;
    }

    public function setCollectionitemid(?TipitakaCollectionItems $collectionitemid): self
    {
        $this->collectionitemid = $collectionitemid;

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
