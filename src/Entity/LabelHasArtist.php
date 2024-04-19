<?php

namespace App\Entity;

use App\Repository\LabelHasArtistRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelHasArtistRepository::class)]
class LabelHasArtist
{


    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joining_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $living_date = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'labelHasArtists')]
    #[ORM\JoinColumn(name: 'label_id', referencedColumnName: 'id')]
    private ?Label $label_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ArtisthasLabels')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    private ?Artist $artist_id = null;


    public function getJoiningDate(): ?\DateTimeInterface
    {
        return $this->joining_date;
    }

    public function setJoiningDate(\DateTimeInterface $joining_date): static
    {
        $this->joining_date = $joining_date;

        return $this;
    }

    public function getLivingDate(): ?\DateTimeInterface
    {
        return $this->living_date;
    }

    public function setLivingDate(\DateTimeInterface $living_date): static
    {
        $this->living_date = $living_date;

        return $this;
    }

    public function getLabelId(): ?Label
    {
        return $this->label_id;
    }

    public function setLabelId(?Label $label_id): static
    {
        $this->label_id = $label_id;

        return $this;
    }

    public function getArtistId(): ?Artist
    {
        return $this->artist_id;
    }

    public function setArtistId(?Artist $artist_id): static
    {
        $this->artist_id = $artist_id;

        return $this;
    }
}
