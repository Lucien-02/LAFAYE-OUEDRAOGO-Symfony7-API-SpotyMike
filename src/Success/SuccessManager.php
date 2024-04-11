<?php

namespace App\Success;

use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

class SuccessManager
{
    public function validPostRequest(string $entityName)
    {
        throw new Exception(SuccessTypes::VALID_POST_REQUEST, $entityName);
    }

    public function validPutRequest(string $entityName)
    {
        throw new Exception(SuccessTypes::VALID_PUT_REQUEST, $entityName);
    }

    public function validDeleteRequest(string $entityName)
    {
        throw new Exception(SuccessTypes::VALID_DELETE_REQUEST, $entityName);
    }

    public function generateSuccess(string $successType, string $variable = null): JsonResponse
    {
        $successMessage = '';

        switch ($successType) {
            case 'Create':
                if ($variable == "Playlist"){
                    $successMessage = "$variable créée avec succès.";
                }
                else{
                    $successMessage = "$variable créé avec succès.";
                }
                break;
            case 'Update':
                if ($variable == "Playlist"){
                    $successMessage = "$variable mise à jour avec succès.";
                }
                else{
                    $successMessage = "$variable mis à jour avec succès.";
                }
                break;
            case 'Delete':
                if ($variable == "Playlist"){
                    $successMessage = "Votre $variable a été supprimée avec succès.";
                }
                else{
                    $successMessage = "Votre $variable a été supprimé avec succès.";
                }
                break;
            default:
                $successMessage = 'Succès inconnu.';
                break;
        }

        return new JsonResponse([
            'error' => false,
            'message' => $successMessage
        ]);
    }
}
