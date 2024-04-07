<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idUser = null;

    #[ORM\Column(length: 55)]
    private ?string $lastname = null;

    #[ORM\Column(length: 55)]
    private ?string $firstname = null;

    #[ORM\Column(length: 55)]
    private ?string $sexe = null;

    #[ORM\Column(length: 80)]
    private ?string $email = null;

    #[ORM\Column(length: 90)]
    private ?string $encrypte = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updateAt = null;

    #[ORM\OneToOne(mappedBy: 'User_idUser', cascade: ['persist', 'remove'])]
    private ?Artist $artist = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateBirth = null;

    #[ORM\ManyToMany(targetEntity: Playlist::class, inversedBy: 'users')]
    private Collection $playlist_idPlaylist;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'Followers')]
    private Collection $Follow;

    #[ORM\OneToMany(targetEntity: playlist::class, mappedBy: 'user')]
    private Collection $playlists;

    public function __construct()
    {
        $this->playlist_idPlaylist = new ArrayCollection();
        $this->Follow = new ArrayCollection();
        $this->playlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->encrypte;
    }

    public function setPassword(string $encrypte): static
    {
        $this->encrypte = $encrypte;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): static
    {
        $this->tel = $tel;

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

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(Artist $artist): static
    {
        // set the owning side of the relation if necessary
        if ($artist->getUserIdUser() !== $this) {
            $artist->setUserIdUser($this);
        }

        $this->artist = $artist;

        return $this;
    }

    public function getRoles(): array
    {
        return ["PUBLIC_ACCESS"];
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Returns the identifier for this user (e.g. username or email address).
     */
    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function serializer()
    {
        return [
            "id" => $this->getId(),
            "idUser" => $this->getIdUser(),
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "sexe" => $this->getSexe(),
            "email" => $this->getEmail(),
            "tel" => $this->getTel(),
            "birthday" => $this->getDateBirth()->format('d-m-Y'),
            "createAt" => $this->getCreateAt()->format('Y-m-d H:i:s'),
            "updateAt" => $this->getUpdateAt()->format('Y-m-d H:i:s'),
            "artist" => $this->getArtist() ?  $this->getArtist()->serializer() : [],
        ];
    }

    public function getDateBirth(): ?\DateTimeInterface
    {
        return $this->dateBirth;
    }

    public function setDateBirth(\DateTimeInterface $dateBirth): static
    {
        $this->dateBirth = $dateBirth;

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylistIdPlaylist(): Collection
    {
        return $this->playlist_idPlaylist;
    }

    public function addPlaylistIdPlaylist(Playlist $playlistIdPlaylist): static
    {
        if (!$this->playlist_idPlaylist->contains($playlistIdPlaylist)) {
            $this->playlist_idPlaylist->add($playlistIdPlaylist);
        }

        return $this;
    }

    public function removePlaylistIdPlaylist(Playlist $playlistIdPlaylist): static
    {
        $this->playlist_idPlaylist->removeElement($playlistIdPlaylist);

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getFollow(): Collection
    {
        return $this->Follow;
    }

    public function addFollow(Artist $follow): static
    {
        if (!$this->Follow->contains($follow)) {
            $this->Follow->add($follow);
        }

        return $this;
    }

    public function removeFollow(Artist $follow): static
    {
        $this->Follow->removeElement($follow);

        return $this;
    }

    /**
     * @return Collection<int, playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->setUser($this);
        }

        return $this;
    }

    public function removePlaylist(playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            // set the owning side to null (unless already changed)
            if ($playlist->getUser() === $this) {
                $playlist->setUser(null);
            }
        }

        return $this;
    }
}
