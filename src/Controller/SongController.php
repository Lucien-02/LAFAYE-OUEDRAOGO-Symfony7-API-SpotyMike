<?php

namespace App\Controller;

use App\Entity\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\Album;
use App\Error\ErrorManager;
use App\Error\ErrorTypes;
use Exception;

class SongController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
        $this->errorManager = $errorManager;
    }

    #[Route('/song/all', name: 'app_songs_get_all', methods: 'GET')]
    public function getSongs(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);
            
            parse_str($request->getContent(), $data);

            $songsPerPage = 5;
            $numPage = $data["page"];

            // Récupération page demandée
            $page = $request->query->getInt('page', $numPage);

            $offset = ($page - 1) * $songsPerPage;

            $songs = $this->repository->findBy([], null, $songsPerPage, $offset);

            $this->errorManager->checkNotFoundSong($songs);

            $song_serialized = [];
            foreach ($songs as $song) {
                array_push($song_serialized, $song->serializer());
            }

            $totalSongs = count($this->repository->findAll());

            $totalPages = ceil($totalSongs / $songsPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $songsPerPage;

                // Récupération songs page suivante
                $nextPageSongs = $this->repository->findBy([], null, $songsPerPage, $nextPageOffset);

                $nextPageSongsSerialized = [];
                foreach ($nextPageSongs as $song) {
                    array_push($nextPageSongsSerialized, $song->serializer());
                }
            }

            if (!empty($song_serialized)) {
                $currentSerializedContent = $song_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPageSongsSerialized
                $currentSerializedContent = $nextPageSongsSerialized;
                $currentPage = $nextPage;
            }
 
            $response = [
                "error" => false,
                "songs" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalSongs" => $totalSongs
                ]
            ];

            if ($page = $nextPage) {
                $song_serialized = null;
            }

            return $this->json($response, 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/song/{id}', name: 'app_song_get', methods: 'GET')]
    public function getSong(int $id): JsonResponse
    {
        try {
            $song = $this->repository->find($id);

            $this->errorManager->checkNotFoundSongId($song);

            return $this->json([
                'id' => $song->getId(),
                'album' => [
                    'id' => $song->getAlbum()->getId(),
                    'nom' => $song->getAlbum()->getNom(),
                    'categ' => $song->getAlbum()->getCateg(),
                    'cover' => $song->getAlbum()->getCover(),
                    'year' => $song->getAlbum()->getYear(),
                ],
                'title' => $song->getTitle(),
                'url' => $song->getUrl(),
                'cover' => $song->getCover(),
                'visibility' => $song->isVisibility()
            ], 200);
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/song', name: 'app_song_post', methods: 'POST')]
    public function postSong(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);

            $this->errorManager->checkRequiredAttributes($data, ['title', 'url', 'cover, visibility', 'album_id', 'song']);

            $album = $this->entityManager->getRepository(Album::class)->find($data['album_id']);

            $this->errorManager->checkNotFoundSongId($album);

            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $uniqueId = uniqid();

            $song = new Song();
            $song->setAlbum($album);
            $song->setTitle($data['title']);
            $song->setUrl($data['url']);
            // $song->setCover($data['cover']);
            $song->setIdSong($uniqueId);
            $song->setVisibility($data['visibility']);
            $song->setCreateAt($date);

            $this->entityManager->persist($song);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Son créé avec succès."
            ], 201);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/song/{id}', name: 'app_song_put', methods: 'PUT')]
    public function putSong(Request $request, int $id): JsonResponse
    {
        try {
            $song = $this->repository->find($id);

            $this->errorManager->checkNotFoundSongId($song);

            parse_str($request->getContent(), $data);

            if (isset($data['title'])) {
                $song->setTitle($data['title']);
            }
            if (isset($data['url'])) {
                $song->setUrl($data['url']);
            }
            if (isset($data['cover'])) {
                $song->setCover($data['cover']);
            }
            if (isset($data['visibility'])) {
                $song->setVisibility($data['visibility']);
            }

            $this->entityManager->persist($song);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Son mis à jour avec succès."
            ], 200);
        
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/song/{id}', name: 'app_song_delete', methods: 'DELETE')]
    public function deleteSong(int $id): JsonResponse
    {
        try {
            $song = $this->repository->find($id);

            $this->errorManager->checkNotFoundSongId($song);

            $this->entityManager->remove($song);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre son a été supprimé avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
