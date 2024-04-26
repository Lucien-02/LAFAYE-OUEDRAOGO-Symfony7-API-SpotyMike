<?php

namespace App\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\User;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;
use CustomException;

class PlaylistController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;

        $this->repository = $entityManager->getRepository(Playlist::class);
    }

    #[Route('/playlist/{id}', name: 'app_playlist_delete', methods: ['DELETE'])]
    public function delete_playlist_by_id(int $id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundPlaylistId($playlist);

            $this->entityManager->remove($playlist);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre playlist a été supprimée avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/playlist', name: 'post_playlist', methods: 'POST')]
    public function post_playlist(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);

            $this->errorManager->checkRequiredAttributes($data, ['title', 'public', 'idplaylist']);

            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $uniqueId = uniqid();

            //Recherche si le user est deja un artiste
            $user = $this->entityManager->getRepository(User::class)->find($data['user_id']);

            $playlist = new Playlist();
            $playlist->setTitle($data['title']);
            $playlist->setIdPlaylist($uniqueId);
            $playlist->setPublic($data['public']);
            $playlist->setUser($user);
            $playlist->setCreateAt($date);
            $playlist->setUpdateAt($date);

            $this->entityManager->persist($playlist);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Playlist créée avec succès."
            ], 201);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/playlist/all', name: 'app_playlists_get', methods: ['GET'])]
    public function get_all_playlists(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $playlistsPerPage = 5;
            $numPage = $_GET["currentPage"];
            if ($numPage <= 0) {
                throw new CustomException(ErrorTypes::NOT_FOUND_ARTIST);
            }
            // Récupération page demandée
            $page = $request->query->getInt('currentPage', $numPage);

            $offset = ($page - 1) * $playlistsPerPage;

            $playlists = $this->repository->findBy([], null, $playlistsPerPage, $offset);

            $this->errorManager->checkNotFoundPlaylist($playlists);

            $playlist_serialized = [];
            foreach ($playlists as $playlist) {
                array_push($playlist_serialized, $playlist->serializer());
            }

            $totalPlaylists = count($this->repository->findAll());

            $totalPages = ceil($totalPlaylists / $playlistsPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $playlistsPerPage;

                // Récupération playlists page suivante
                $nextPagePlaylists = $this->repository->findBy([], null, $playlistsPerPage, $nextPageOffset);

                $nextPagePlaylistsSerialized = [];
                foreach ($nextPagePlaylists as $playlist) {
                    array_push($nextPagePlaylistsSerialized, $playlist->serializer());
                }
            }

            if (!empty($playlist_serialized)) {
                $currentSerializedContent = $playlist_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPagePlaylistsSerialized
                $currentSerializedContent = $nextPagePlaylistsSerialized;
                $currentPage = $nextPage;
            }

            $response = [
                "error" => false,
                "playlists" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalPlaylists" => $totalPlaylists
                ]
            ];

            if ($page = $nextPage) {
                $playlist_serialized = null;
            }

            return $this->json($response, 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/playlist/{id}', name: 'app_playlist_put', methods: ['PUT'])]
    public function putPlaylist(Request $request, int $id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundPlaylistId($playlist);

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

            return new JsonResponse([
                'error' => false,
                'message' => "Playlist mise à jour avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/playlist/{id}', name: 'app_playlist', methods: ['GET'])]
    public function get_playlist_by_id($id): JsonResponse
    {
        try {
            $playlist = $this->repository->find($id);

            $this->errorManager->checkNotFoundPlaylistId($playlist);

            return $this->json([
                'id' => $playlist->getId(),
                'title' => $playlist->getTitle(),
                'public' => $playlist->isPublic(),
                'create_at' => $playlist->getCreateAt(),
                'update_at' => $playlist->getUpdateAt(),
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }
}
