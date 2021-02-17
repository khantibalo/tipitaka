<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaNotes
 *
 * @ORM\Table(name="tipitaka_notes", indexes={@ORM\Index(name="paragraphid", columns={"paragraphid"})})
 * @ORM\Entity
 */
class TipitakaNotes
{
    /**
     * @var int
     *
     * @ORM\Column(name="noteid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $noteid;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="notetext", type="string", length=1000, nullable=false)
     */
    private $notetext;

    /**
     * @var TipitakaParagraphs
     *
     * @ORM\ManyToOne(targetEntity="TipitakaParagraphs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="paragraphid", referencedColumnName="paragraphid")
     * })
     */
    private $paragraphid;

    public function getNoteid(): ?int
    {
        return $this->noteid;
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

    public function getNotetext(): ?string
    {
        return $this->notetext;
    }

    public function setNotetext(string $notetext): self
    {
        $this->notetext = $notetext;

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


}
