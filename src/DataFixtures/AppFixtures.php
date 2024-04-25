<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\User;
use App\Entity\Song;
use App\Entity\Playlist;
use App\Entity\Label;
use App\Entity\LabelHasArtist;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i < 8; $i++) {

            // Add New User
            $user = new User;
            $user->setIdUser(uniqid());
            $sexe = rand(0, 1) === 0 ? 0 : 1;
            $user->setSexe($sexe);
            $user->setFirstname("User_" . $i);
            $user->setLastname("User_" . $i);
            $user->setEmail("user_" . $i . "@gmail.com");
            $user->setActive(rand(0, 1));
            $user->setDateBirth(new DateTimeImmutable());
            $user->setCreateAt(new DateTimeImmutable());
            $user->setUpdateAt(new DateTimeImmutable());
            $hash = $this->hasher->hashPassword($user, "TestUser_" . $i);
            $user->setPassword($hash);
            $manager->persist($user);
            $manager->flush();

            // Add New Artist
            $artist = new Artist;
            $artist->setFullname("Artist_" . $i);
            $artist->setUserIdUser($user);
            $artist->setDescription("Artist_" . $i);
            $artist->setCreateAt(new DateTimeImmutable());
            $artist->setUpdateAt(new DateTimeImmutable());
            $manager->persist($artist);
            $manager->flush();

            // Add New Label
            $label = new Label;
            $label->setNom("Label_" . $i);
            $label->setCreateAt(new DateTimeImmutable());
            $label->setUpdateAt(new DateTimeImmutable());
            $manager->persist($label);
            $manager->flush();

            if (rand(0, 1)) {
                // Créer une nouvelle instance de LabelHasArtist
                $newLabelHasArtist = new LabelHasArtist();

                // Configurer la nouvelle instance avec les détails appropriés
                $newLabelHasArtist->setJoiningDate(new \DateTime());
                $newLabelHasArtist->setArtistId($artist);
                $newLabelHasArtist->setLabelId($label);

                // Ajouter la nouvelle instance à l'artiste
                //$artist->addArtisthasLabel($newLabelHasArtist);


                $manager->persist($newLabelHasArtist); // Persistez la nouvelle relation
                //$manager->persist($artist); // Persistez l'artiste (si nécessaire)
                $manager->flush();
            }
            // Add New Album
            $album = new Album;
            $album->setIdAlbum($i);
            $album->setArtistUserIdUser($artist);
            $album->setNom("Album_" . $i);
            $album->setCateg("Album_" . $i);
            // $album->setCover("Album_" . $i);
            $album->setYear(rand(1900, 2024));
            $album->setCreateAt(new DateTimeImmutable());
            $album->setUpdateAt(new DateTimeImmutable());
            $manager->persist($album);
            $manager->flush();

            // Add New Song
            $song = new Song;
            $song->setIdSong($i);
            $song->setTitle("Song_" . $i);
            $song->setUrl("Song_" . $i);
            //$song->setCover("Song_" . $i);
            $song->setVisibility(rand(0, 1));
            $song->setCreateAt(new DateTimeImmutable());
            $song->setAlbum($album);
            $manager->persist($song);
            $manager->flush();

            // Add New Playlist
            $playlist = new Playlist;
            $playlist->setIdPlaylist($i);
            $playlist->setUser($user);
            $playlist->setTitle("Playlist_" . $i);
            $playlist->setPublic(rand(0, 1));
            $playlist->setCreateAt(new DateTimeImmutable());
            $playlist->setUpdateAt(new DateTimeImmutable());
            $manager->persist($playlist);
            $manager->flush();
        }
    }
}
