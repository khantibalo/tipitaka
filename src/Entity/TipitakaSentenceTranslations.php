<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaSentenceTranslations
 *
 * @ORM\Table(name="tipitaka_sentence_translations", indexes={@ORM\Index(name="SentenceID", columns={"sentenceid"}), @ORM\Index(name="user_id", columns={"userid"})})
 * @ORM\Entity
 */
class TipitakaSentenceTranslations
{
    /**
     * @var int
     *
     * @ORM\Column(name="sentencetranslationid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sentencetranslationid;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="text", length=65535, nullable=false)
     */
    private $translation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateupdated", type="datetime", nullable=false)
     */
    private $dateupdated;


    /**
     * @var int|null
     *
     * @ORM\Column(name="oldtranslationid", type="integer", nullable=true)
     */
    private $oldtranslationid;

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
     *   @ORM\JoinColumn(name="userid", referencedColumnName="userid")
     * })
     */
    private $userid;
    
    /**
     * @var TipitakaSources
     *
     * @ORM\ManyToOne(targetEntity="TipitakaSources")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sourceid", referencedColumnName="sourceid")
     * })
     */
    private $sourceid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parenttranslationid", type="integer", nullable=true)
     */
    private $parenttranslationid;
    
    public function getParenttranslationid(): ?int
    {
        return $this->parenttranslationid;
    }

    public function setParenttranslationid($parenttranslationid): self
    {
        $this->parenttranslationid = $parenttranslationid;
        
        return $this;
    }

    /**
     * @return TipitakaSources
     */
    public function getSourceid(): ?TipitakaSources
    {
        return $this->sourceid;
    }

    /**
     * @param TipitakaSources $sourceid
     */
    public function setSourceid($sourceid): self
    {
        $this->sourceid = $sourceid;
        
        return $this;
    }

    public function getSentencetranslationid(): ?int
    {
        return $this->sentencetranslationid;
    }

    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function getDateupdated(): ?\DateTimeInterface
    {
        return $this->dateupdated;
    }

    public function setDateupdated(\DateTimeInterface $dateupdated): self
    {
        $this->dateupdated = $dateupdated;

        return $this;
    }

    public function getOldtranslationid(): ?int
    {
        return $this->oldtranslationid;
    }

    public function setOldtranslationid(?int $oldtranslationid): self
    {
        $this->oldtranslationid = $oldtranslationid;

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

    public function getUserid(): ?TipitakaUsers
    {
        return $this->userid;
    }

    public function setUserid(?TipitakaUsers $user): self
    {
        $this->userid = $user;

        return $this;
    }


}
