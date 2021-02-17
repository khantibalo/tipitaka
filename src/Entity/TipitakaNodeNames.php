<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaNodeNames
 *
 * @ORM\Table(name="tipitaka_node_names", indexes={@ORM\Index(name="AuthorID", columns={"authorid"}), @ORM\Index(name="LanguageID", columns={"languageid"}), @ORM\Index(name="NodeID", columns={"nodeid"})})
 * @ORM\Entity
 */
class TipitakaNodeNames
{
    /**
     * @var int
     *
     * @ORM\Column(name="nodenameid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $nodenameid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var TipitakaLanguages
     *
     * @ORM\ManyToOne(targetEntity="TipitakaLanguages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="languageid", referencedColumnName="languageid")
     * })
     */
    private $languageid;

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
     * @var TipitakaToc
     *
     * @ORM\ManyToOne(targetEntity="TipitakaToc")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="nodeid", referencedColumnName="nodeid")
     * })
     */
    private $nodeid;

    public function getNodenameid(): ?int
    {
        return $this->nodenameid;
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

    public function getLanguageid(): ?TipitakaLanguages
    {
        return $this->languageid;
    }

    public function setLanguageid(?TipitakaLanguages $languageid): self
    {
        $this->languageid = $languageid;

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

    public function getNodeid(): ?TipitakaToc
    {
        return $this->nodeid;
    }

    public function setNodeid(?TipitakaToc $nodeid): self
    {
        $this->nodeid = $nodeid;

        return $this;
    }


}
