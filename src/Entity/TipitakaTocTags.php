<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaTocTags
 *
 * @ORM\Table(name="tipitaka_toc_tags", indexes={@ORM\Index(name="TagID", columns={"tagid"}), @ORM\Index(name="NodeID", columns={"nodeid"}), @ORM\Index(name="AuthorID", columns={"authorid"})})
 * @ORM\Entity
 */
class TipitakaTocTags
{
    /**
     * @var int
     *
     * @ORM\Column(name="nodetagid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $nodetagid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="applydate", type="datetime", nullable=false)
     */
    private $applydate;

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
     * @var TipitakaTags
     *
     * @ORM\ManyToOne(targetEntity="TipitakaTags")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tagid", referencedColumnName="tagid")
     * })
     */
    private $tagid;

    /**
     * @var TipitakaUsers
     *
     * @ORM\ManyToOne(targetEntity="TipitakaUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authorid", referencedColumnName="userid")
     * })
     */
    private $authorid;

    public function getNodetagid(): ?int
    {
        return $this->nodetagid;
    }

    public function getApplydate(): ?\DateTimeInterface
    {
        return $this->applydate;
    }

    public function setApplydate(\DateTimeInterface $applydate): self
    {
        $this->applydate = $applydate;

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

    public function getTagid(): ?TipitakaTags
    {
        return $this->tagid;
    }

    public function setTagid(?TipitakaTags $tagid): self
    {
        $this->tagid = $tagid;

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


}
