<?php

namespace App\Controller;

use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Artist;

use Symfony\Component\HttpFoundation\Request;

class AlbumController extends AbstractController
{

    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Album::class);
    }


    #[Route('/album/{id}', name: 'app_album_delete', methods: ['DELETE'])]
    public function delete_album_by_id(int $id): JsonResponse
    {

        $album = $this->repository->find($id);

        if (!$album) {
            return new JsonResponse([
                'error' => true,
                'message' => "Album introuvable",
                'album_id' => $id
            ],
            404);
        }

        $this->entityManager->remove($album);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Votre album a été supprimé avec succès'
        ]);
    }

    #[Route('/album', name: 'post_album', methods: 'POST')]
    public function post_album(Request $request): JsonResponse
    {
        parse_str($request->getContent(), $data);

        if (!isset($data['nom']) || !isset($data['categ']) || !isset($data['cover']) || !isset($data['year']) || !isset($data['idalbum'])) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes'
            ], 
            400);
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $album = new Album();
        $artist = $this->entityManager->getRepository(Artist::class)->find(1);
        $album->setArtistUserIdUser($artist);
        $album->setNom($data['nom']);
        $album->setCateg($data['categ']);
        $album->setCover($data['cover']);
        $album->setYear($data['year']);
        $album->setIdAlbum($data['idalbum']);
        $album->setCreateAt($date);
        $album->setUpdateAt($date);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Album ajouté avec succès',
            'id' => $album->getId()
        ]);
    }

    #[Route('/album/{id}', name: 'app_album_put', methods: ['PUT'])]
    public function putAlbum(Request $request, int $id): JsonResponse
    {
        $album = $this->repository->find($id);

        if (!$album) {
            return new JsonResponse([
                'error' => true,
                'message' => "Album introuvable",
                'album_id' => $id
            ],
            404);
        }

        parse_str($request->getContent(), $data);

        if (isset($data['nom'])) {
            $album->setNom($data['nom']);
        }
        if (isset($data['categ'])) {
            $album->setCateg($data['categ']);
        }
        if (isset($data['cover'])) {
            $album->setCover($data['cover']);
        }
        if (isset($data['year'])) {
            $album->setYear($data['year']);
        }
        if (isset($data['idalbum'])) {
            $album->setIdAlbum($data['idalbum']);
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Album mis à jour avec succès'
        ]);
    }

    #[Route('/album/{id}', name: 'app_album', methods: ['GET'])]
    public function get_album_by_id(int $id): JsonResponse
    {
        $album = $this->repository->find($id);

        if (!$album) {
            return new JsonResponse([
                'error' => true,
                'message' => "Album introuvable",
                'album_id' => $id
            ],
            404);
        }

        return new JsonResponse([
            $album->serializer()
        ]);
    }

    #[Route('/album', name: 'app_albums_get', methods: ['GET'])]
    public function get_all_albums(): JsonResponse
    {
        $albums = $this->repository->findAll();

        if (!$albums) {
            return new JsonResponse([
                'error' => true,
                'message' => "Aucun album existant"
            ],
            404);
        }

        $serializedAlbums = [];
        foreach ($albums as $album) {
            $serializedAlbums[] = [
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'album_id' => $album->getIdAlbum(),
            ];
        }

        return new JsonResponse($serializedAlbums);
    }
}
