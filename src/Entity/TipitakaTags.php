<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaTags
 *
 * @ORM\Table(name="tipitaka_tags", indexes={@ORM\Index(name="TagTypeID", columns={"tagtypeid"})})
 * @ORM\Entity
 */
class TipitakaTags
{
    /**
     * @var int
     *
     * @ORM\Column(name="tagid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $tagid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paliname", type="string", length=255, nullable=true)
     */
    private $paliname;

    /**
     * @var TipitakaTagtypes
     *
     * @ORM\ManyToOne(targetEntity="TipitakaTagtypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tagtypeid", referencedColumnName="tagtypeid")
     * })
     */
    private $tagtypeid;

    public function getTagid(): ?int
    {
        return $this->tagid;
    }

    public function getPaliname(): ?string
    {
        return $this->paliname;
    }

    public function setPaliname(?string $paliname): self
    {
        $this->paliname = $paliname;

        return $this;
    }

    public function getTagtypeid(): ?TipitakaTagtypes
    {
        return $this->tagtypeid;
    }

    public function setTagtypeid(?TipitakaTagtypes $tagtypeid): self
    {
        $this->tagtypeid = $tagtypeid;

        return $this;
    }


}
