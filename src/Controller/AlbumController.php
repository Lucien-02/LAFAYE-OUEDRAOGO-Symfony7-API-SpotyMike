<?php

namespace App\Controller;

use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Artist;
use Symfony\Component\HttpFoundation\Request;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use App\Success\SuccessManager;
use Exception;

class AlbumController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;
    private $successManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager, SuccessManager $successManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;
        $this->successManager = $successManager;
        $this->repository = $entityManager->getRepository(Album::class);
    }

    #[Route('/album/{id}', name: 'app_album_delete', methods: ['DELETE'])]
    public function delete_album_by_id(int $id): JsonResponse
    {
        try {
            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($album, 'album');

            $this->entityManager->remove($album);
            $this->entityManager->flush();

            $this->successManager->validDeleteRequest("album");
            
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album', name: 'post_album', methods: 'POST')]
    public function post_album(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);
            
            $this->errorManager->checkRequiredAttributes($data, ['nom', 'categ', 'cover', 'year', 'idalbum']);

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

            $this->successManager->validPostRequest("Album");
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/{id}', name: 'app_album_put', methods: ['PUT'])]
    public function putAlbum(Request $request, int $id): JsonResponse
    {
        try {
            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($album, 'Album');

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

            $this->successManager->validPutRequest("Album");
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/{id}', name: 'app_album', methods: ['GET'])]
    public function get_album_by_id(int $id): JsonResponse
    {
        try {
            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($album, 'Album');

            return new JsonResponse([
                $album->serializer()
            ]);
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/all', name: 'app_albums_get', methods: ['GET'])]
    public function get_all_albums(): JsonResponse
    {
        try {
            $albums = $this->repository->findAll();

            $this->errorManager->checkNotFoundEntity($albums, 'album');

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
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
