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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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

    #[Route('/artist', name: 'app_artist_delete', methods: ['DELETE'])]
    public function delete_artist(TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $decodedtoken = $JWTManager->decode($token);
        $this->errorManager->Tokennotreset($decodedtoken);
        $decodedtoken = $JWTManager->decode($token);
        $email =  $decodedtoken['username'];
        $request_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $request_artist = $request_user->getArtist();
        if (!$request_artist) {
            return $this->json([
                'error' => true,
                'message' => 'Compte artiste nom trouvé .Vérifier les informations fournies et réessayer'
            ], 409);
        }


        $this->entityManager->remove($request_artist);

        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => "Le compte artiste a été désactivé avec succès."
        ]);

        // Gestion des erreurs inattendues
        throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
    }

    #[Route('/artist', name: 'post_artist', methods: 'POST')]
    public function post_artist(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->Tokennotreset($decodedtoken);
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

            $this->errorManager->checkNotFoundArtistId($user);

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
    public function putArtist(Request $request, int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $artist = $this->repository->find($id);

            $this->errorManager->checkNotFoundArtistId($artist);

            parse_str($request->getContent(), $data);
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->Tokennotreset($decodedtoken);
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



    #[Route('/artist', name: 'app_artists_get', methods: ['GET'])]
    public function get_all_artists(TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->Tokennotreset($decodedtoken);
            $artists = $this->repository->findAll();
            $artist_serialized = [];
            foreach ($artists as $artist) {
                array_push($artist_serialized, $artist->serializer());
            }
            $this->errorManager->checkNotFoundArtist($artists);

            return $this->json([
                "error" => false,
                "artists" => $artist_serialized,
                "message" => 'Informations des artistes récupérées avec succès.',
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/artist/{fullname}', name: 'app_artist', methods: ['GET'])]
    public function get_artist_by_id(TokenInterface $token, string $fullname, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            if ($fullname == " ") {
                return $this->json([
                    'error' => true,
                    'message' => "Le nom d'artiste est obligatoire pour cette requete."
                ], 400);
            }

            $artist = $this->repository->findOneBy(['fullname' => $fullname]);
            $owner = false;
            if (!preg_match('/^[^\s]+$/u', $fullname)) {
                return $this->json([
                    'error' => true,
                    'message' => "Le format du nom de l'artiste fourni est invalide."
                ], 400);
            }

            if (!$artist) {
                return $this->json([
                    'error' => true,
                    'message' => 'Aucun  artiste  trouvé correspondant au nom fourni.'
                ], 409);
            }

            $email = $artist->getUserIdUser()->getEmail();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->Tokennotreset($decodedtoken);
            $email =  $decodedtoken['username'];
            $request_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user->getEmail() == $request_user->getEmail()) {
                $owner = true;
            }
            return $this->json([
                "error" => false,
                "artist" => $artist->serializer($owner),
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
