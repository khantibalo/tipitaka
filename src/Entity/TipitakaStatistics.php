<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipitakaStatistics
 *
 * @ORM\Table(name="tipitaka_statistics")
 * @ORM\Entity
 */
class TipitakaStatistics
{
    /**
     * @var int
     *
     * @ORM\Column(name="statid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $statid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accessdate", type="date", nullable=false)
     */
    private $accessdate;

    /**
     * @var int
     *
     * @ORM\Column(name="accesscount", type="integer", nullable=false)
     */
    private $accesscount;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nodeid", type="integer", nullable=true)
     */
    private $nodeid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    public function getStatid(): ?int
    {
        return $this->statid;
    }

    public function getAccessdate(): ?\DateTimeInterface
    {
        return $this->accessdate;
    }

    public function setAccessdate(\DateTimeInterface $accessdate): self
    {
        $this->accessdate = $accessdate;

        return $this;
    }

    public function getAccesscount(): ?int
    {
        return $this->accesscount;
    }

    public function setAccesscount(int $accesscount): self
    {
        $this->accesscount = $accesscount;

        return $this;
    }

    public function getNodeid(): ?int
    {
        return $this->nodeid;
    }

    public function setNodeid(?int $nodeid): self
    {
        $this->nodeid = $nodeid;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }


}
