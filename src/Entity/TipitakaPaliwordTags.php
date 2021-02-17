<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaPaliwordTags
 *
 * @ORM\Table(name="tipitaka_paliword_tags", uniqueConstraints={@ORM\UniqueConstraint(name="paliwordtag_unique", columns={"paliword", "tagid"})}, indexes={@ORM\Index(name="tipitaka_paliword_tags_ibfk_1", columns={"tagid"}), @ORM\Index(name="tipitaka_paliword_tags_ibfk_2", columns={"authorid"})})
 * @ORM\Entity
 */
class TipitakaPaliwordTags
{
    /**
     * @var int
     *
     * @ORM\Column(name="paliwordtagid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $paliwordtagid;

    /**
     * @var string
     *
     * @ORM\Column(name="paliword", type="string", length=255, nullable=false)
     */
    private $paliword;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="applydate", type="datetime", nullable=false)
     */
    private $applydate;

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

    public function getPaliwordtagid(): ?int
    {
        return $this->paliwordtagid;
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

    public function getApplydate(): ?\DateTimeInterface
    {
        return $this->applydate;
    }

    public function setApplydate(\DateTimeInterface $applydate): self
    {
        $this->applydate = $applydate;

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
