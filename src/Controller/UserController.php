<?php

namespace App\Controller;

use App\Entity\User;
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
            $users = $this->repository->findAll();

            $this->errorManager->checkNotFoundUser($users);

            $serializedUsers = [];
            foreach ($users as $user) {
                $serializedUsers[] = [
                    'id' => $user->getId(),
                    'name' => $user->getFirstname(),
                    'encrypt' => $user->getPassword(),
                    'mail' => $user->getEmail(),
                    'tel' => $user->getTel(),
                    'active' => $user->getActive(),
                    'birthday' => $user->getDateBirth()
                ];
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
    public function register(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
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
            // vérif age
            $this->errorManager->isAgeValid($birthday, $ageMin);

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
            $dateOfBirth = new \DateTimeImmutable($birthday);
            $user->setDateBirth($dateOfBirth);
            $user->setSexe($sexe);
            $user->setEmail($email);
            $hash = $passwordHash->hashPassword($user, $password);
            $user->setPassword($hash);
            $user->setIdUser($uniqueId);

            $this->entityManager->persist($user);

            $this->entityManager->flush();

            $user->serializer();

            return new JsonResponse([
                'error' => false,
                'message' => "Utilisateur créé avec succès."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }


    #[Route('/user/{id}', name: 'app_user_put', methods: 'PUT')]
    public function putUser(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->repository->find($id);


            $this->errorManager->checkNotFoundUserId($user);

            parse_str($request->getContent(), $data);

            $email = $data['email'];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'error' => 'Adresse email invalide',
                    'email' => $email
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
            $existingUser = $this->repository->findOneByEmail($data['email']);
            if ($existingUser !== null) {
                return new JsonResponse([
                    'error' => 'Cet email existe déjà',
                    'email' => $data['email']
                ], JsonResponse::HTTP_CONFLICT);
            }

            if (isset($data['name'])) {
                $user->setName($data['name']);
            }
            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            }
            if (isset($data['tel'])) {
                $user->setTel($data['tel']);
            }
            if (isset($data['encrypte'])) {
                $user->setPassword($data['encrypte']);
            }
            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $user->setUpdateAt($date);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Utilisateur mis à jour avec succès."
            ]);

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
                'error' => false,
                'message' => "Votre compte  a été  désactiver avec succès.Nous sommes désolés de vus voir partir."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return (($this->errorManager->generateError($exception->getMessage(), $exception->getCode())));
        }
    }
}
