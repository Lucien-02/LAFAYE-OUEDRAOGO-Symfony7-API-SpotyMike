<?php

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\OneToMany(targetEntity: LabelHasArtist::class, mappedBy: 'label_id')]
    private Collection $labelHasArtists;

    public function __construct()
    {
        $this->labelHasArtists = new ArrayCollection();
    }




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function serializer()
    {
        return [
            "id" => $this->getId(),
            "nom" => $this->getNom(),
            "createAt" => $this->getCreateAt()->format('Y-m-d H:i:s'),
            "updateAt" => $this->getUpdateAt()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @return Collection<int, LabelHasArtist>
     */
    public function getLabelHasArtists(): Collection
    {
        return $this->labelHasArtists;
    }

    public function addLabelHasArtist(LabelHasArtist $labelHasArtist): static
    {
        if (!$this->labelHasArtists->contains($labelHasArtist)) {
            $this->labelHasArtists->add($labelHasArtist);
            $labelHasArtist->setLabelId($this);
        }

        return $this;
    }

    public function removeLabelHasArtist(LabelHasArtist $labelHasArtist): static
    {
        if ($this->labelHasArtists->removeElement($labelHasArtist)) {
            // set the owning side to null (unless already changed)
            if ($labelHasArtist->getLabelId() === $this) {
                $labelHasArtist->setLabelId(null);
            }
        }

        return $this;
    }
}
