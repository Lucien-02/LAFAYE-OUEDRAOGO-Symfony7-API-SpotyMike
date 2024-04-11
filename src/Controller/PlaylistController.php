<?php

namespace App\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use App\Success\SuccessManager;
use Exception;

class PlaylistController extends AbstractController
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
        $this->repository = $entityManager->getRepository(Playlist::class);
    }

    #[Route('/playlist/{id}', name: 'app_playlist_delete', methods: ['DELETE'])]
    public function delete_playlist_by_id(int $id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($playlist, "Playlist");

            $this->entityManager->remove($playlist);
            $this->entityManager->flush();

            $this->successManager->validDeleteRequest("playlist");
        
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/playlist', name: 'post_playlist', methods: 'POST')]
    public function post_playlist(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);
            
            $this->errorManager->checkRequiredAttributes($data, ['title', 'public', 'idplaylist']);
            
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        
            //Recherche si le user est deja un artiste
            $user = $this->entityManager->getRepository(User::class)->find($data['user_id']);

            $playlist = new Playlist();
            $playlist->setTitle($data['title']);
            $playlist->setIdPlaylist($data['idplaylist']);
            $playlist->setPublic($data['public']);
            $playlist->setUser($user);
            $playlist->setCreateAt($date);
            $playlist->setUpdateAt($date);

            $this->entityManager->persist($playlist);
            $this->entityManager->flush();

            $this->successManager->validPostRequest("Playlist");
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/playlist/{id}', name: 'app_playlist_put', methods: ['PUT'])]
    public function putPlaylist(Request $request, int $id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($playlist, "Playlist");

            parse_str($request->getContent(), $data);

            if (isset($data['title'])) {
                $playlist->setTitle($data['title']);
            }
            if (isset($data['public'])) {
                $playlist->setPublic($data['public']);
            }
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $playlist->setUpdateAt($date);

            $this->entityManager->persist($playlist);
            $this->entityManager->flush();

            $this->successManager->validPutRequest("Playlist");
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/playlist/{id}', name: 'app_playlist', methods: ['GET'])]
    public function get_playlist_by_id(int $id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($playlist, "Playlist");

            return $this->json([
                'id' => $playlist->getId(),
                'title' => $playlist->getTitle(),
                'public' => $playlist->isPublic(),
                'create_at' => $playlist->getCreateAt(),
                'update_at' => $playlist->getUpdateAt(),
            ]);
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/playlist/all', name: 'app_playlists_get', methods: ['GET'])]
    public function get_all_playlists(): JsonResponse
    {
        try {
            $playlists = $this->repository->findAll();

            $this->errorManager->checkNotFoundEntity($playlists, "playlist");

            $serializedPlaylists = [];
            foreach ($playlists as $playlist) {
                $serializedPlaylists[] = [
                    'id' => $playlist->getId(),
                    'title' => $playlist->getTitle(),
                    'public' => $playlist->isPublic(),
                    'create_at' => $playlist->getCreateAt()->format('Y-m-d H:i:s'),
                    'update_at' => $playlist->getUpdateAt()->format('Y-m-d H:i:s'),
                ];
            }

            return new JsonResponse($serializedPlaylists);
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
