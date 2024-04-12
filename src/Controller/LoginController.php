<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Cache\CacheItemPoolInterface;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;



class LoginController extends  AbstractController
{
    private $repository;
    private $cache;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cache)
    {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->repository = $entityManager->getRepository(User::class);
    }

    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash, ErrorManager $errorManager): JsonResponse
    {
        try {
            //Gerer le nome de tentative de connection max
            //recup l'ip
            $ip = $request->getClientIp();
            $errorManager->tooManyAttempts(5, 300, $ip, 'connection');

            parse_str($request->getContent(), $data);
            //vérification attribut nécessaire
            $errorManager->checkRequiredAttributes($data, ['Email', 'Password']);
            $email = $data['Email'];
            $password = $data['Password'];
            // vérif format mail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $errorManager->generateError(ErrorTypes::INVALID_EMAIL);
            }

            // vérif format mdp
            $errorManager->isValidPassword($password);

            $user = $this->repository->findOneByEmail($email);
            // vérif Compte existant
            if (!$user) {
                return $errorManager->generateError(ErrorTypes::USER_NOT_FOUND);
            }
            /*
            // vérif Compte actif
            if (!$user->isActive()) {
                return $errorManager->generateError("AccountNotActive");
            }
            */
            if ($passwordHash->isPasswordValid($user, $password)) {
                $token = $JWTManager->create($user);
                return new JsonResponse([
                    'error' => false,
                    'message' => "L'utilisateur a été authentifié avec succès",
                    'user' => $user->serializer(),
                    'token' => $token,
                ]);
            }
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/password-lost', name: 'app_password-lost', methods: ['POST'])]
    public function password_lost(Request $request, ErrorManager $errorManager): JsonResponse
    {
        try {
            //Gerer le nombre de tentative de connection max
            //recup l'ip
            $ip = $request->getClientIp();
            $errorManager->tooManyAttempts(3, 300, $ip, 'password-lost');

            parse_str($request->getContent(), $data);

            $email = $data["email"] ?? null;

            $email_found = $this->repository->findOneByEmail($email);
            //Email manquant

            if (!isset($data["email"])) {
                return $errorManager->generateError(ErrorTypes::MISSING_EMAIL);
            }
            // vérif format mail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $errorManager->generateError(ErrorTypes::INVALID_EMAIL);
            }
            //Email non trouvé
            if (!$email_found) {
                return $errorManager->generateError(ErrorTypes::EMAIL_NOT_FOUND);
            } else {

                return new JsonResponse([
                    'error' => false,
                    'message' => "Un email de réinitialisation de mot de passe a été envoyé à votre adresse email.Veuiller suivre les instructions contenues dans l'email pour réinitialiser votre mot de passe.",

                ]);
            }
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
