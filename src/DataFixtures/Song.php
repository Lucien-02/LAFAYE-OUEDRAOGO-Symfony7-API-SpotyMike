<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Song as EntitySong;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class Song extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 7; $i++) {
            # code...
            $song = new EntitySong;
            $manager->persist($song);
            $song->setIdSong($i);
            $song->setTitle("Song_" . $i);
            $song->setUrl("Song_" . $i);
            $song->setStream("Song_" . $i);
            $song->setCover("Song_" . $i);
            $song->isVisibility(1);
            $song->setCreateAt(new DateTimeImmutable());
            if (rand(0, 1)) {
                $album = new Album();
            }
        }
        $manager->flush();
    }
}
