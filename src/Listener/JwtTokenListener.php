<?php

namespace App\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JwtTokenListener
{
    public function onJwtExpired(JWTExpiredEvent $event)
    {
        // Vous pouvez accéder à la requête pour obtenir le token expiré
        $request = $event->getRequest();
        $expiredToken = $request->headers->get('Authorization');

        // Définir votre propre réponse
        $response = new JsonResponse([
            'error' => 'true',
            'message' => 'Votre session a expiré. Veuillez vous reconnecter.'
        ], 401);

        $event->setResponse($response);
    }
    public function onJwtInvalid(JWTInvalidEvent $event)
    {
        // Vous pouvez accéder à la requête pour obtenir le token invalide
        $request = $event->getRequest();
        $invalidToken = $request->headers->get('Authorization');

        // Définir votre propre réponse pour le token invalide
        $response = new JsonResponse([
            'error' => 'true',
            'message' => 'Token invalide. Veuillez vous reconnecter avec un token valide.'
        ], 401);

        $event->setResponse($response);
    }
    public function onKernelException(ExceptionEvent $event)
    {
        // Récupérer l'exception de l'événement
        $exception = $event->getThrowable();

        // Vérifier si c'est une exception HTTP
        if ($exception instanceof HttpExceptionInterface) {
            // Renvoyer une réponse JSON avec le message d'erreur personnalisé
            $response = new JsonResponse([
                'error' => 'true',
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], 401);

            // Remplacer la réponse de l'événement avec la réponse personnalisée
            $event->setResponse($response);
        }
    }
}
