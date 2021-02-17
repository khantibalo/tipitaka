<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaSentences
 *
 * @ORM\Table(name="tipitaka_sentences", indexes={@ORM\Index(name="paragraphid", columns={"paragraphid"})})
 * @ORM\Entity
 */
class TipitakaSentences
{
    /**
     * @var int
     *
     * @ORM\Column(name="sentenceid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sentenceid;

   
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
     * @var string
     *
     * @ORM\Column(name="sentencetext", type="text", length=65535, nullable=false)
     */
    private $sentencetext;
    
    /**
     * @var int
     *
     * @ORM\Column(name="commentcount", type="integer", nullable=false)
     */
    private $commentcount;
    
    /**
     * @var string
     *
     * @ORM\Column(name="lastcomment", type="text", length=255, nullable=true)
     */
    private $lastcomment;

    /**
     * @var int
     *
     * @ORM\Column(name="legacyid", type="integer", nullable=true)
     */
    private $legacyid;
    
    /**
     * @return string
     */
    public function getLastcomment(): ?string
    {
        return $this->lastcomment;
    }

    /**
     * @param string $lastcomment
     */
    public function setLastcomment(string $lastcomment)
    {
        $this->lastcomment = $lastcomment;
        return $this;
    }

    /**
     * @return number
     */
    public function getCommentcount(): ?int
    {
        return $this->commentcount;
    }

    /**
     * @param number $commentcount
     */
    public function setCommentcount($commentcount): self
    {
        $this->commentcount = $commentcount;
        
        return $this;
    }

    public function getSentenceid(): ?int
    {
        return $this->sentenceid;
    }

    public function getParagraphid(): TipitakaParagraphs
    {
        return $this->paragraphid;
    }

    public function setParagraphid(TipitakaParagraphs $paragraphid): self
    {
        $this->paragraphid = $paragraphid;

        return $this;
    }

    public function getSentencetext(): ?string
    {
        return $this->sentencetext;
    }

    public function setSentencetext(string $sentencetext): self
    {
        $this->sentencetext = $sentencetext;

        return $this;
    }

    /**
     * @return number
     */
    public function getLegacyID(): ?int
    {
        return $this->legacyid;
    }
    
    /**
     * @param number $legacyid
     */
    public function setLegacyID($legacyid): self
    {
        $this->legacyid = $legacyid;
        
        return $this;
    }
}
