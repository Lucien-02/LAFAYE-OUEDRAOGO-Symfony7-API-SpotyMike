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
use App\Util\ErrorTypes;
use App\Util\ErrorManager;
use Exception;



class LoginController extends  AbstractController
{
    private $repository;
    private $cache;
    private $errorManager;
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
            // Définir les paramètres de limite de fréquence
            $maxAttempts = 20;
            $interval = 300;

            //recup l'ip
            $ip = $request->getClientIp();
            // Récupérer le nombre de tentatives de connexion pour cette adresse IP dans le cache
            $attempts = $this->cache->getItem('login_attempts_' . $ip)->get() ?: 0;
            $timezone = new \DateTimeZone('Europe/Paris');
            $time = new DateTime('now', $timezone);
            // Vérifier si le nombre de tentatives a dépassé la limite
            if ($attempts >= $maxAttempts) {
                $expiration = $this->cache->getItem('expiration_' . $ip)->get();
                $temprestant = $expiration->modify('+5 minutes')->diff($time)->format('%i');

                return $errorManager->generateError(ErrorTypes::TOO_MANY_ATTEMPTS, $temprestant);
            }
            $attempts++;
            $item = $this->cache->getItem('login_attempts_' . $ip);
            $expiration = $this->cache->getItem('expiration_' . $ip);

            $expiration->set($time);
            $this->cache->save($expiration);
            $item->set($attempts);
            $item->expiresAfter($interval);

            $this->cache->save($item);


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
            return $errorManager->generateError($exception->getMessage());
        }
    }
}
