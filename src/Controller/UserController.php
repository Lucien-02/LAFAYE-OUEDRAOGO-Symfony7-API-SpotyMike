<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Util\ErrorTypes;
use App\Util\ErrorManager;
use Exception;

class UserController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/user', name: 'app_users_get_all', methods: 'GET')]
    public function getUsers(): JsonResponse
    {
        $users = $this->repository->findAll();

        if (!$users) {
            return new JsonResponse([
                'error' => true,
                'message' => "Aucun utilisateur trouvé",
            ], 404);
        }

        $serializedUsers = [];
        foreach ($users as $user) {
            $serializedUsers[] = [
                'id' => $user->getId(),
                'name' => $user->getFirstname(),
                'encrypt' => $user->getPassword(),
                'mail' => $user->getEmail(),
                'tel' => $user->getTel(),
                'birthday' => $user->getDateBirth()
            ];
        }

        return new JsonResponse($serializedUsers);
    }

    #[Route('/user/{id}', name: 'app_user_get', methods: 'GET')]
    public function getUserById(int $id)
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return new JsonResponse([
                'error' => true,
                'message' => "Utilisateur introuvable",
                'user_id' => $id,
            ], 404);
        }

        return $this->json($user->serializer());
    }

    #[Route('/register', name: 'app_register', methods: 'POST')]
    public function register(Request $request, ErrorManager $errorManager, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        try {

            parse_str($request->getContent(), $data);
            //vérification attribut nécessaire
            $errorManager->checkRequiredAttributes($data, ['firstname', 'lastname', 'email', 'password', 'dateBirth']);
            $firstname = $data['firstname'];
            $lastname = $data['lastname'];
            $email = $data['email'];
            $password = $data['password'];
            $birthday =  $data['dateBirth'];
            if (isset($data['sexe'])) {
                $sexe = $data['sexe'];
            }
            if (isset($data['tel'])) {
                $phoneNumber = $data['tel'];
            }
            $ageMin = 12;
            // vérif format mail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $errorManager->generateError(ErrorTypes::INVALID_EMAIL);
            }

            // vérif format mdp
            $errorManager->isValidPassword($password);
            // vérif format date
            $errorManager->isValidDateFormat($birthday, 'd/m/Y');
            // vérif age
            $errorManager->isAgeValid($birthday, 12);

            //vérif tel
            if (isset($data['tel'])) {
                $errorManager->isValidPhoneNumber($phoneNumber);
            }

            //vérif sexe
            if (isset($data['sexe'])) {
                $errorManager->isValidGender($sexe);
            }

            //vérif email unique
            if ($this->repository->findOneByEmail($email)) {
                return $errorManager->generateError(ErrorTypes::NOT_UNIQUE_EMAIL);
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

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "L'utilisateur a été créé avec succès",
                'user' => $user->serializer(),

            ]);
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/user/{id}', name: 'app_user_put', methods: 'PUT')]
    public function putUser(Request $request, int $id): JsonResponse
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return new JsonResponse([
                'error' => true,
                'message' => "Utilisateur introuvable",
                'user_id' => $id,
            ], 404);
        }
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
            'message' => "Utilisateur mis à jour avec succès"
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_delete', methods: 'DELETE')]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return new JsonResponse([
                'error' => true,
                'message' => "Utilisateur introuvable",
                'user_id' => $id,
            ], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => "Votre utilisateur a été supprimé avec succès"
        ]);
    }
}
