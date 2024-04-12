<?php

namespace App\Controller;

use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Album;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;

class ArtistController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;
        
        $this->repository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artist/{id}', name: 'app_artist_delete', methods: ['DELETE'])]
    public function delete_artist_by_id(int $id): JsonResponse
    {
        try {
            $artist = $this->repository->find($id);
            
            $this->errorManager->checkNotFoundEntityId($artist);

            $this->entityManager->remove($artist);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre artiste a été supprimé avec succès."
            ]);
    
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist', name: 'post_artist', methods: 'POST')]
    public function post_artist(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);

            //Données manquantes
            $this->errorManager->checkRequiredAttributes($data, ['fullname', 'user_id_user_id']);

            //Vérification token
            if (False) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Votre token n'est pas correct."
                ], 401);
            }

            // Vérification du type des données
            if (!is_string($data['fullname']) || !is_numeric($data['user_id_user_id'])) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Une ou plusieurs données sont erronées.',
                    'data' => $data
                ], 409);
            }
            
            // Recherche d'un artiste avec le même nom dans la base de données
            $existingArtist = $this->entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $data['fullname']]);
            $this->errorManager->checkNotUniqueArtistName($existingArtist);

            //Recherche si le user est deja un artiste
            $user = $this->entityManager->getRepository(User::class)->find($data['user_id_user_id']);

            $this->errorManager->checkNotFoundEntityId($user, "Utilisateur");

            $artist = new Artist();
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $birthday = $user->getDateBirth();
            //Vérification Age
            $this->errorManager->isAgeValid($birthday, 16);

            $artist->setFullname($data['fullname']);
            $artist->setUserIdUser($user);
            if (isset($data['description'])) {
                $artist->setdescription($data['description']);
            }
            $artist->setCreateAt($date);
            $artist->setUpdateAt($date);

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Artiste créé avec succès."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist/{id}', name: 'app_artist_put', methods: ['PUT'])]
    public function putArtist(Request $request, int $id): JsonResponse
    {
        try {
            $artist = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($artist, "Artiste");

            parse_str($request->getContent(), $data);

            if (isset($data['fullname'])) {
                $artist->setFullname($data['fullname']);
            }
            if (isset($data['description'])) {
                $artist->setDescription($data['description']);
            }

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Artiste mis à jour avec succès."
            ]);
        
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist/', name: 'empty_artist', methods: ['GET'])]
    public function emptyArtist(): JsonResponse
    {
        try {
            return $this->json([
                "error" => true,
                "message" => "Nom de l'artiste manquant",
            ], 400);
        
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist/all', name: 'app_artists_get', methods: ['GET'])]
    public function get_all_artists(): JsonResponse
    {
        try {
            $artists = $this->repository->findAll();
            $artist_serialized = [];
            foreach ($artists as $artist) {
                array_push($artist_serialized, $artist->serializer());
            }
            $this->errorManager->checkNotFoundEntity($artists, "artiste");

            return new JsonResponse($artist_serialized);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist/{fullname}', name: 'app_artist', methods: ['GET'])]
    public function get_artist_by_id(string $fullname): JsonResponse
    {
        try {
            $artist = $this->repository->findOneBy(['fullname' => $fullname]);

            if (!$artist) {
                return $this->json([
                    'error' => true,
                    'message' => 'Une ou plusieurs données sont erronées.'
                ], 409);
            }

            return $this->json([
                $artist->serializer()
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
