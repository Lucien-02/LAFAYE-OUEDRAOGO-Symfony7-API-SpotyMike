<?php

namespace App\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
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
            'code' => 401,
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
            'code' => 401,
            'message' => 'Token invalide. Veuillez vous reconnecter avec un token valide.'
        ], 401);

        $event->setResponse($response);
    }
    public function onJwtNotFound(JWTNotFoundEvent $event)
    {
        // Définir votre propre réponse pour le token manquant
        $response = new JsonResponse([
            'code' => 401,
            'message' => 'Token manquant. Veuillez fournir un token valide pour accéder à cette ressource.'
        ], 401);

        $event->setResponse($response);
    }
}
