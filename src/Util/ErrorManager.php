<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorManager
{
    public function generateError(string $errorType, string $remainingTime = null): JsonResponse
    {
        $errorMessage = '';
        $codeErreur = '';

        switch ($errorType) {
            case 'TooManyAttempts':
                $errorMessage = "Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - $remainingTime minutes restantes";
                $codeErreur = 429;
                break;
            case 'MissingAttributes':
                $errorMessage = 'Email/Password manquants';
                $codeErreur = 400;
                break;
            case 'InvalidEmail':
                $errorMessage = "Le format de l'email est invalide.";
                $codeErreur = 400;
                break;
            case 'InvalidPasswordFormat':
                $errorMessage = "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.";
                $codeErreur = 400;
                break;
            case 'UserNotFound':
                $errorMessage = 'Aucun utilisateur trouvé. Mot de passe ou Identifiant incorrect';
                $codeErreur = 400;
                break;
            case 'AccountNotActive':
                $errorMessage = "Le compte n'est plus actif ou est suspendu.";
                $codeErreur = 403;
                break;
            case 'UnexpectedError':
                $errorMessage = 'Une erreur inattendue s\'est produite.';
                $codeErreur = 400;
                break;
            default:
                $errorMessage = 'Erreur inconnue';
                $codeErreur = 400;
                break;
        }

        return new JsonResponse([
            'error' => true,
            'message' => $errorMessage,
        ], $codeErreur);
    }
}
