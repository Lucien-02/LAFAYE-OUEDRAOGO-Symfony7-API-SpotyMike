<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class UserController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;

        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/user/all', name: 'app_users_get_all', methods: 'GET')]
    public function getUsers(): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $usersPerPage = 5;
            $numPage = $data["page"];

            // Récupération page demandée
            $page = $request->query->getInt('page', $numPage);

            $offset = ($page - 1) * $usersPerPage;

            $users = $this->repository->findBy([], null, $usersPerPage, $offset);

            $this->errorManager->checkNotFoundUser($users);

            $serializedUsers = [];
            foreach ($users as $user) {
                array_push($user_serialized, $user->serializer());
            }

            $totalUsers = count($this->repository->findAll());

            $totalPages = ceil($totalUsers / $usersPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $usersPerPage;

                // Récupération users page suivante
                $nextPageUsers = $this->repository->findBy([], null, $usersPerPage, $nextPageOffset);

                $nextPageUsersSerialized = [];
                foreach ($nextPageUsers as $user) {
                    array_push($nextPageUsersSerialized, $user->serializer());
                }
            }

            if (!empty($user_serialized)) {
                $currentSerializedContent = $user_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPageUsersSerialized
                $currentSerializedContent = $nextPageUsersSerialized;
                $currentPage = $nextPage;
            }

            $response = [
                "error" => false,
                "users" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalUsers" => $totalUsers
                ]
            ];

            if ($page = $nextPage) {
                $user_serialized = null;
            }
            return new JsonResponse($serializedUsers);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }

    #[Route('/user/{id}', name: 'app_user_get', methods: 'GET')]
    public function getUserById(int $id)
    {
        try {
            $user = $this->repository->find($id);

            $this->errorManager->checkNotFoundUserId($user);

            return $this->json($user->serializer());

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }

    #[Route('/register', name: 'app_register', methods: 'POST')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHash, AlbumRepository $albumRepository): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);
            //vérification attribut nécessaire

            $this->errorManager->checkRequiredAttributes($data, ['firstname', 'lastname', 'email', 'password', 'dateBirth']);

        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $password = $data['password'];
        $birthday =  $data['dateBirth'];
        $uniqueId = uniqid();

        if (isset($data['sexe'])) {
            $sexe = $data['sexe'];
        }
        if (isset($data['tel'])) {
            $phoneNumber = $data['tel'];
        }
        $ageMin = 12;
        // vérif format mail
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorManager->generateError(ErrorTypes::INVALID_EMAIL);
        }

        // vérif format mdp
        $this->errorManager->isValidPassword($password);
        // vérif format date
        $this->errorManager->isValidDateFormat($birthday, 'd/m/Y');

        $dateOfBirth = \DateTime::createFromFormat('d/m/Y', $birthday)->format('Y-m-d');

        // vérif age
        $this->errorManager->isAgeValid($dateOfBirth, $ageMin);

        //vérif tel
        if (isset($data['tel'])) {
            $this->errorManager->isValidPhoneNumber($phoneNumber);
        }

        //vérif sexe
        if (isset($data['sexe'])) {
            $this->errorManager->isValidGender($sexe);
        }

        //vérif email unique
        if ($this->repository->findOneByEmail($email)) {
            return $this->errorManager->generateError(ErrorTypes::NOT_UNIQUE_EMAIL);
        }

        $user = new User();

        $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $user->setCreateAt($date);
        $user->setUpdateAt($date);

        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setDateBirth(new DateTime($dateOfBirth));
        $user->setSexe($sexe);
        $user->setEmail($email);
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setPassword($hash);
        $user->setIdUser($uniqueId);

        $this->entityManager->persist($user);

        $this->entityManager->flush();

        $explodeData = explode(",", $data['avatar']);

        if (count($explodeData) == 2) {
            $file = base64_decode($explodeData[1]);
            $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();
            mkdir($chemin);
            file_put_contents($chemin . '/file.png', $file);
        }

        return new JsonResponse([
            'error' => false,
            'message' => "L'utilisateur a bien été créé avec succès.",
            'user' => $user->serializer($albumRepository)
        ], 201);

        // Gestion des erreurs inattendues
        throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        // } catch (Exception $exception) {
        //     return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        // }
    }


    #[Route('/user', name: 'app_user_post', methods: 'POST')]
    public function postUser(TokenInterface $token, Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $email = $decodedtoken['username'];
            $request_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            parse_str($request->getContent(), $data);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'error' => 'Adresse email invalide',
                    'email' => $email
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
            if (isset($data['email'])) {
                $existingUser = $this->repository->findOneByEmail($email);
                if ($existingUser !== null) {
                    return new JsonResponse([
                        'error' => 'Cet email existe déjà',
                        'email' => $email
                    ], JsonResponse::HTTP_CONFLICT);
                }
            }

            if (isset($data['id_user'])) {
                $request_user->setIdUser($data['id_user']);
            }
            if (isset($data['firstname'])) {
                $request_user->setFirstname($data['firstname']);
            }
            if (isset($data['lastname'])) {
                $request_user->setLastname($data['lastname']);
            }
            if (isset($data['email'])) {
                $request_user->setEmail($data['email']);
            }
            if (isset($data['sexe'])) {
                $request_user->setSexe($data['sexe']);
            }
            if (isset($data['tel'])) {
                $request_user->setTel($data['tel']);
            }
            if (isset($data['encrypte'])) {
                // vérif format mdp
                $this->errorManager->isValidPassword($data['encrypte']);

                $hash = $passwordHash->hashPassword($request_user, $data['encrypte']);
                $request_user->setPassword($hash);
            }
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $request_user->setUpdateAt($date);

            $this->entityManager->persist($request_user);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre inscription a bien été prise en compte."
            ], 201);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }

    #[Route('/user/{id}', name: 'app_user_delete', methods: 'DELETE')]
    public function deleteUser(int $id): JsonResponse
    {
        try {
            $user = $this->repository->find($id);

            $this->errorManager->checkNotFoundUserId($user);

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre utilisateur a été supprimé avec succès."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }

    #[Route('/account-desactivation', name: 'app_user_delete', methods: 'DELETE')]
    public function account_desactivation(TokenInterface $token, JWTTokenManagerInterface $JWTManager, ErrorManager $errorManager): JsonResponse
    {
        try {

            $decodedtoken = $JWTManager->decode($token);
            $email =  $decodedtoken['username'];
            $user = $this->repository->findOneBy(['email' => $email]);

            if (!$user->getActive()) {
                throw new Exception(ErrorTypes::ACCOUNT_ALREADY_DESACTIVATE);
            }

            $user->setActive(false);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => "Votre compte a été désactivé avec succès. Nous sommes désolés de vous voir partir."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }
}
