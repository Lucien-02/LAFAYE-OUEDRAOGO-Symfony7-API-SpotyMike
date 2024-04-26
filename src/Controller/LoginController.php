<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use App\Repository\AlbumRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;



class LoginController extends  AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash, ErrorManager $errorManager, AlbumRepository $albumRepository): JsonResponse
    {
        try {

            parse_str($request->getContent(), $data);
            //vérification attribut nécessaire
            $errorManager->checkRequiredLoginAttributes($data, ['email', 'password']);
            $email = $data['email'];
            $password = $data['password'];
            // vérif format mail
            $errorManager->isValidEmail($email);
            $user = $this->repository->findOneByEmail($email);
            $iduser = $user->getIdUser();
            //Gerer le nome de tentative de connection max
            //recup l'ip

            $errorManager->tooManyAttempts(5, 300, $iduser, 'connection');



            // vérif format mdp
            $errorManager->isValidPassword($password);


            // vérif Compte existant
            if (!$user) {
                return $errorManager->generateError(ErrorTypes::USER_NOT_FOUND);
            }

            if (!$user->getActive()) {
                return $errorManager->generateError(ErrorTypes::NOT_ACTIVE_USER);
            }

            if ($passwordHash->isPasswordValid($user, $password)) {
                $token = $JWTManager->create($user);
                return new JsonResponse([
                    'error' => false,
                    'message' => "L'utilisateur a été authentifié avec succès",
                    'user' => $user->serializer($albumRepository),
                    'token' => $token,
                ], 200);
            }
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/password-lost', name: 'app_password-lost', methods: ['POST'])]
    public function password_lost(Request $request, ErrorManager $errorManager, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);
            //Email manquant
            if (!isset($data["email"])) {
                return $errorManager->generateError(ErrorTypes::MISSING_EMAIL);
            }
            $email = $data["email"];
            $errorManager->isValidEmail($email);
            $user = $this->repository->findOneByEmail($email);
            $iduser = $user->getIdUser();
            //Gerer le nombre de tentative de connection max
            //recup l'email
            $errorManager->tooManyAttempts(3, 300, $iduser, 'password-lost');


            $email_found = $this->repository->findOneByEmail($email);

            //Email non trouvé
            if (!$email_found) {
                return $errorManager->generateError(ErrorTypes::EMAIL_NOT_FOUND);
            } else {
                $token = $JWTManager->createFromPayload(
                    $email_found,
                    ['type' => 'reset-password']
                );
                //$token = $JWTManager->create($email_found, [], ['type' => 'reset_password']);
                return new JsonResponse([
                    'success' => true,
                    'token' => $token,
                    'message' => "Un email de réinitialisation de mot de passe a été envoyé à votre adresse email. Veuillez suivre les instructions contenues dans l'email pour réinitialiser votre mot de passe.",
                ], 200);
            }
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
    #[Route('/reset-password/{token}', name: 'app_reset-password', methods: ['GET'])]
    public function reset_password(Request $request, string $token, ErrorManager $errorManager, JWTEncoderInterface $jwtEncoder, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        try {

            //Token manquant
            // Vérifier si le token est fourni
            if (empty($token)) {
                return $errorManager->generateError(ErrorTypes::TOKEN_INVALID_MISSING);
            }
            // Vérifier si le token est valide
            try {

                $decodedToken = $jwtEncoder->decode($token);
                // Récupérez la date d'expiration du token
                $expirationDate = new \DateTime('@' . $decodedToken['exp'], new DateTimeZone('Europe/Paris'));
                // Vérifiez si le token a expiré
                $currentTime = new \DateTime('now');
                echo !$expirationDate > $currentTime;
                if (!$expirationDate > $currentTime) {
                    return $errorManager->generateError(ErrorTypes::TOKEN_PASSWORD_EXPIRE);
                };
                $email = $decodedToken['username'];
            } catch (JWTDecodeFailureException $e) {
                // Vérifier si le message d'erreur indique que le token est expiré
                if (strpos($e->getMessage(), 'Expired JWT Token') !== false) {
                    return $errorManager->generateError(ErrorTypes::TOKEN_PASSWORD_EXPIRE);
                } else {
                    return $errorManager->generateError(ErrorTypes::TOKEN_INVALID_MISSING);
                }
            }

            if (!isset($_GET["password"])) {
                return $errorManager->generateError(ErrorTypes::MISSING_PASSWORD);
            }

            $password = $_GET["password"];

            $errorManager->isValidPassword($password);
            $user = $this->repository->findOneByEmail($email);
            $hash = $passwordHash->hashPassword($user, $password);
            $user->setPassword($hash);

            $this->entityManager->persist($user);
            $this->entityManager->flush();


            return new JsonResponse([
                'success' => true,
                'message' => "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.",
            ], 200);


            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
