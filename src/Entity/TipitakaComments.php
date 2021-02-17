<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaComments
 *
 * @ORM\Table(name="tipitaka_comments", indexes={@ORM\Index(name="AuthorID", columns={"authorid"}), @ORM\Index(name="SentenceID", columns={"sentenceid"})})
 * @ORM\Entity
 */
class TipitakaComments
{
    /**
     * @var int
     *
     * @ORM\Column(name="commentid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $commentid;

    /**
     * @var string
     *
     * @ORM\Column(name="commenttext", type="text", length=0, nullable=false)
     */
    private $commenttext;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createddate", type="datetime", nullable=false)
     */
    private $createddate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="lasteditdate", type="datetime", nullable=true)
     */
    private $lasteditdate;

    /**
     * @var int
     *
     * @ORM\Column(name="forprint", type="integer", nullable=false)
     */
    private $forprint = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="authorname", type="string", length=255, nullable=true)
     */
    private $authorname;

    /**
     * @var TipitakaSentences
     *
     * @ORM\ManyToOne(targetEntity="TipitakaSentences")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sentenceid", referencedColumnName="sentenceid")
     * })
     */
    private $sentenceid;

    /**
     * @var TipitakaUsers
     *
     * @ORM\ManyToOne(targetEntity="TipitakaUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authorid", referencedColumnName="userid")
     * })
     */
    private $authorid;

    public function getCommentid(): ?int
    {
        return $this->commentid;
    }

    public function getCommenttext(): ?string
    {
        return $this->commenttext;
    }

    public function setCommenttext(string $commenttext): self
    {
        $this->commenttext = $commenttext;

        return $this;
    }

    public function getCreateddate(): ?\DateTimeInterface
    {
        return $this->createddate;
    }

    public function setCreateddate(\DateTimeInterface $createddate): self
    {
        $this->createddate = $createddate;

        return $this;
    }

    public function getLasteditdate(): ?\DateTimeInterface
    {
        return $this->lasteditdate;
    }

    public function setLasteditdate(?\DateTimeInterface $lasteditdate): self
    {
        $this->lasteditdate = $lasteditdate;

        return $this;
    }

    public function getForprint(): ?int
    {
        return $this->forprint;
    }

    public function setForprint(int $forprint): self
    {
        $this->forprint = $forprint;

        return $this;
    }

    public function getAuthorname(): ?string
    {
        return $this->authorname;
    }

    public function setAuthorname(?string $authorname): self
    {
        $this->authorname = $authorname;

        return $this;
    }

    public function getSentenceid(): ?TipitakaSentences
    {
        return $this->sentenceid;
    }

    public function setSentenceid(?TipitakaSentences $sentenceid): self
    {
        $this->sentenceid = $sentenceid;

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
