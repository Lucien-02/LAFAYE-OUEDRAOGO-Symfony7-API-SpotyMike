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

class ArtistController extends AbstractController
{

    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
    }


    #[Route('/artist/{id}', name: 'app_artist_delete', methods: ['DELETE'])]
    public function delete_artist_by_id(int $id): JsonResponse
    {

        $artist = $this->repository->find($id);

        if (!$artist) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Artiste introuvable',
                'artist_id' => $id,
            ],
            404);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Votre profil artiste a été supprimé avec succès'
        ]);
    }

    #[Route('/artist', name: 'post_artist', methods: 'POST')]
    public function post_artist(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);

            //Donné manquante
            if (!isset($data['fullname']) || !isset($data['user_id_user_id'])) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Une ou plusieurs données obligatoires sont manquantes'
                ], 400);
            }

            //Vérification token
            if (False) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Votre token n'est pas correct"
                ], 401);
            }

            // Vérification du type des données
            if (!is_string($data['fullname']) || !is_numeric($data['user_id_user_id'])) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Une ou plusieurs données sont erronées',
                    'data' => $data
                ], 409);
            }
            // Recherche d'un artiste avec le même nom dans la base de données
            $existingArtist = $this->entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $data['fullname']]);
            if ($existingArtist) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Un compte utilisant ce nom d'artiste est déjà enregistré"
                ], 409);
            }

            //Recherche si le user est deja un artiste
            $user = $this->entityManager->getRepository(User::class)->find($data['user_id_user_id']);

            if (!$user) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }
            $artist = new Artist();
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $birthday = $user->getDateBirth();
            $age = $birthday->diff($date)->y;
            //Vérification Age
            if ($age < 16) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Age minimum requis (16 ans)",
                ], 401);
            }

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
                'sucess' => true,
                'message' => "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artistes !",
                'artist_id' => $artist->getId()
            ]);

        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // Renvoyer un message d'erreur indiquant que la donnée est déjà présente
            return new JsonResponse([
                'error' => true,
                'message' => 'La donnée que vous essayez d\'insérer existe déjà dans la base de données'
            ], 409);
        }
    }

    #[Route('/artist/{id}', name: 'app_artist_put', methods: ['PUT'])]
    public function putArtist(Request $request, int $id): JsonResponse
    {
        $artist = $this->repository->find($id);

        if (!$artist) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Artiste introuvable',
                'artist_id' => $id,
            ],
            404);
        }

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
            'message' => 'Artiste mis à jour avec succès'
        ]);
    }

    #[Route('/artist/', name: 'empty_artist', methods: ['GET'])]
    public function emptyArtist(): JsonResponse
    {
        return $this->json([
            "error" => true,
            "message" => "Nom de l'artiste manquant",
        ], 400);
    }
    #[Route('/artist/all', name: 'app_artists_get', methods: ['GET'])]
    public function get_all_artists(): JsonResponse
    {

        $artists = $this->repository->findAll();
        $artist_serialized = [];
        foreach ($artists as $artist) {

            array_push($artist_serialized, $artist->serializer());
        }
        if (!$artists) {
            return $this->json([
                'message' => 'Aucun utilisateur trouvé',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($artist_serialized);
    }
    #[Route('/artist/{fullname}', name: 'app_artist', methods: ['GET'])]
    public function get_artist_by_id(string $fullname): JsonResponse
    {

        $artist = $this->repository->findOneBy(['fullname' => $fullname]);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données sont erronées'

            ], 409);
        }

        return $this->json([
            $artist->serializer()
        ]);
    }
}
