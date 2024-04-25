<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\LabelHasArtist;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function findLabelsByAlbum($albumId)
    {

        $qb = $this->createQueryBuilder('a');

        $qb->select('lha')
            ->from(LabelHasArtist::class, 'lha')
            ->join('a.artist_User_idUser', 'album_artist')
            ->where('a.id = :albumId')
            ->andWhere('lha.artist_id = album_artist.id')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->between('a.createAt', 'lha.joining_date', 'lha.leaving_date'),
                    $qb->expr()->andX(
                        $qb->expr()->gt('a.createAt', 'lha.joining_date'),
                        $qb->expr()->isNull('lha.leaving_date')
                    )
                )
            )
            ->setParameter('albumId', $albumId);

        return $qb->getQuery()->getResult();
    }
}
