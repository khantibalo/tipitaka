<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaCollectionItems
 *
 * @ORM\Table(name="tipitaka_collection_items", indexes={@ORM\Index(name="AuthorID", columns={"authorid"}), @ORM\Index(name="NodeID", columns={"Nodeid"})})
 * @ORM\Entity
 */
class TipitakaCollectionItems
{
    /**
     * @var int
     *
     * @ORM\Column(name="collectionitemid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $collectionitemid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parentid", type="integer", nullable=true)
     */
    private $parentid;

    /**
     * @var int
     *
     * @ORM\Column(name="vieworder", type="integer", nullable=false)
     */
    private $vieworder;

    /**
     * @var string|null
     *
     * @ORM\Column(name="limitrows", type="string", length=500, nullable=true)
     */
    private $limitrows;

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
     * @var TipitakaUsers
     *
     * @ORM\ManyToOne(targetEntity="TipitakaUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authorid", referencedColumnName="userid")
     * })
     */
    private $authorid;
    
    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    private $level;

    public function getCollectionitemid(): ?int
    {
        return $this->collectionitemid;
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

    public function getVieworder(): ?int
    {
        return $this->vieworder;
    }

    public function setVieworder(int $vieworder): self
    {
        $this->vieworder = $vieworder;

        return $this;
    }

    public function getLimitrows(): ?string
    {
        return $this->limitrows;
    }

    public function setLimitrows(?string $limitrows): self
    {
        $this->limitrows = $limitrows;

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

    public function getAuthorid(): ?TipitakaUsers
    {
        return $this->authorid;
    }

    public function setAuthorid(?TipitakaUsers $authorid): self
    {
        $this->authorid = $authorid;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }
    
    public function setLevel(int $level): self
    {
        $this->level = $level;
        
        return $this;
    }
}
