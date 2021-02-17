<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaPagenumbers
 *
 * @ORM\Table(name="tipitaka_pagenumbers", indexes={@ORM\Index(name="paragraphid", columns={"paragraphid"}), @ORM\Index(name="TipitakaIssueID", columns={"tipitakaissueid"})})
 * @ORM\Entity
 */
class TipitakaPagenumbers
{
    /**
     * @var int
     *
     * @ORM\Column(name="pagenumberid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $pagenumberid;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @var int
     *
     * @ORM\Column(name="volumenumber", type="integer", nullable=false)
     */
    private $volumenumber;

    /**
     * @var int
     *
     * @ORM\Column(name="pagenumber", type="integer", nullable=false)
     */
    private $pagenumber;

    /**
     * @var TipitakaParagraphs
     *
     * @ORM\ManyToOne(targetEntity="TipitakaParagraphs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="paragraphid", referencedColumnName="paragraphid")
     * })
     */
    private $paragraphid;

    /**
     * @var TipitakaIssues
     *
     * @ORM\ManyToOne(targetEntity="TipitakaIssues")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tipitakaissueid", referencedColumnName="tipitakaissueid")
     * })
     */
    private $tipitakaissueid;

    public function getPagenumberid(): ?int
    {
        return $this->pagenumberid;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getVolumenumber(): ?int
    {
        return $this->volumenumber;
    }

    public function setVolumenumber(int $volumenumber): self
    {
        $this->volumenumber = $volumenumber;

        return $this;
    }

    public function getPagenumber(): ?int
    {
        return $this->pagenumber;
    }

    public function setPagenumber(int $pagenumber): self
    {
        $this->pagenumber = $pagenumber;

        return $this;
    }

    public function getParagraphid(): ?TipitakaParagraphs
    {
        return $this->paragraphid;
    }

    public function setParagraphid(?TipitakaParagraphs $paragraphid): self
    {
        $this->paragraphid = $paragraphid;

        return $this;
    }

    public function getTipitakaissueid(): ?TipitakaIssues
    {
        return $this->tipitakaissueid;
    }

    public function setTipitakaissueid(?TipitakaIssues $tipitakaissueid): self
    {
        $this->tipitakaissueid = $tipitakaissueid;

        return $this;
    }


}
