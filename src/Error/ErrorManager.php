<?php

namespace App\Error;

use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use DateTime;
use Throwable;


class ErrorManager
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function TokenNotReset(array $decodedtoken)
    {
        if (isset($decodedtoken['type'])) {
            if ($decodedtoken['type'] == 'reset-password') {
                throw new Exception(ErrorTypes::TOKEN_INVALID_MISSING);
            }
        }
    }

    public function isValidDateFormat(string $dateString, string $expectedFormat)
    {
        $date = \DateTime::createFromFormat($expectedFormat, $dateString);
        if ($date === false || $date->format($expectedFormat) !== $dateString) {
            throw new Exception(ErrorTypes::INVALID_DATE_FORMAT);
        }
    }

    public function isValidPassword(string $password)
    {
        if (!(strlen($password) >= 8 &&
            preg_match('/[A-Z]/', $password) &&
            preg_match('/[a-z]/', $password) &&
            preg_match('/[0-9]/', $password) &&
            preg_match('/[!@#$%^&*()-_+=]/', $password))) {
            throw new Exception(ErrorTypes::INVALID_PASSWORD_FORMAT);
        }
    }
    public function checkNotFoundUser(array $entity)
    {

        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_USER);
        }
    }
    public function checkNotFoundAlbum(array $entity)
    {

        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_ALBUM);
        }
    }
    public function checkNotFoundSong(array $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_SONG);
        }
    }
    public function checkNotFoundPlaylist(array $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_PLAYLIST);
        }
    }
    public function checkNotFoundLabel(array $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_LABEL);
        }
    }
    public function checkNotFoundArtist(array $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_ARTIST);
        }
    }
    public function checkNotFoundArtistId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_ARTIST_ID);
        }
    }
    public function checkNotFoundUserId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_USER_ID);
        }
    }
    public function checkNotFoundLabelId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_LABEL_ID);
        }
    }
    public function checkNotFoundPlaylistId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_PLAYLIST_ID);
        }
    }
    public function checkNotFoundSongId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_SONG_ID);
        }
    }
    public function checkNotFoundAlbumId(object $entity)
    {
        if (!$entity) {
            throw new Exception(ErrorTypes::NOT_FOUND_ALBUM_ID);
        }
    }
    public function checkRequiredAttributes(array $data, array $requiredAttributes)
    {
        foreach ($requiredAttributes as $attribute) {
            if (!isset($data[$attribute])) {
                throw new Exception(ErrorTypes::MISSING_ATTRIBUTES);
            }
        }
    }
    public function checkRequiredLoginAttributes(array $data, array $requiredAttributes)
    {
        foreach ($requiredAttributes as $attribute) {
            if (!isset($data[$attribute])) {
                throw new Exception(ErrorTypes::MISSING_ATTRIBUTES_LOGIN);
            }
        }
    }
    public  function isAgeValid(string $dateOfBirth, int $minimumAge)
    {
        $today = new \DateTime();
        $birthdate = new \DateTime($dateOfBirth);
        $age = $today->diff($birthdate)->y;

        if ($age < $minimumAge) {
            throw new Exception(ErrorTypes::INVALID_AGE, $minimumAge);
        }
    }

    public  function isValidPhoneNumber(string $phoneNumber)
    {
        if (!preg_match("/^[0-9]{10}$/", $phoneNumber)) {
            throw new Exception(ErrorTypes::INVALID_PHONE_NUMBER);
        }
    }

    public  function isValidEmail(string $email)
    {
        if (!preg_match('/^[a-zA-Z0-9.%+-]+@[^\s@]+[a-zA-Z0-9.—-]+[\w.-]+.[a-zA-Z]{2,}$/', $email)) {
            throw new Exception(ErrorTypes::INVALID_EMAIL);
        }
    }

    public  function isValidGender(string $gender)
    {
        if (!in_array($gender, [0, 1])) {
            throw new Exception(ErrorTypes::INVALID_GENDER);
        }
    }

    public function checkNotUniqueArtistName(object $existingArtist)
    {
        if ($existingArtist) {
            throw new Exception(ErrorTypes::NOT_UNIQUE_ARTIST_NAME);
        }
    }

    public  function tooManyAttempts(int $maxAttempts, int $interval, string $email, string $type)
    {


        // Récupérer le nombre de tentatives de connexion pour cette adresse email dans le cache
        $attempts = $this->cache->getItem('login_attempts_' . $email)->get() ?: 0;
        $timezone = new \DateTimeZone('Europe/Paris');
        $time = new DateTime('now', $timezone);
        // Vérifier si le nombre de tentatives a dépassé la limite
        if ($attempts >= $maxAttempts) {
            $expiration = $this->cache->getItem('expiration_' . $email)->get();
            $temprestant = $expiration->modify('+5 minutes')->diff($time)->format('%i');
            switch ($type) {
                case 'password-lost':
                    throw new Exception(ErrorTypes::TOO_MANY_PASSWORD_ATTEMPTS, $temprestant);
                    break;
                case 'connection':
                    throw new Exception(ErrorTypes::TOO_MANY_CONNECTION_ATTEMPTS, $temprestant);
                    break;
            }
        }
        $attempts++;
        $item = $this->cache->getItem('login_attempts_' . $email);
        $expiration = $this->cache->getItem('expiration_' . $email);

        $expiration->set($time);
        $this->cache->save($expiration);
        $item->set($attempts);
        $item->expiresAfter($interval);

        $this->cache->save($item);
    }

    public function IsLengthValid(string $field, int $maxlength)
    {
        if (strlen($field) > $maxlength) {
            throw new Exception(ErrorTypes::INVALID_DATA_LENGTH);
        }
    }


    public function generateError(string $errorType, string $variable = null): JsonResponse
    {
        $errorMessage = '';
        $codeErreur = '';

        switch ($errorType) {
            case 'TooManyConnectionAttempts':
                $errorMessage = "Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement : $variable minutes restantes.";
                $codeErreur = 429;
                break;
            case 'TooManyPasswordAttempts':
                $errorMessage = "Trop de demandes de réinitialisation de mot de passe ( 3 max ). Veuillez attendre avant de réessayer ( Dans $variable min).";
                $codeErreur = 429;
                break;
            case 'MissingAttributes':
                $errorMessage = 'Des champs obligatoires sont manquants.';
                $codeErreur = 400;
                break;
            case 'MissingEmail':
                $errorMessage = "Email manquant. Veuillez fournir votre email pour la récupération du mot de passe.";
                $codeErreur = 400;
                break;
            case 'MissingPassword':
                $errorMessage = 'Veuiller fournir un nouveaux mot de passe.';
                $codeErreur = 400;
                break;
            case 'MissingAttributesLogin':
                $errorMessage = 'Email/password manquants.';
                $codeErreur = 400;
                break;
            case 'MissingAlbumId':
                $errorMessage = "L'id de l'album est obligatoire pour cette requête.";
                $codeErreur = 400;
                break;
            case 'InvalidEmail':
                $errorMessage = "Le format de l'email est invalide. Veuillez entrer un email valide.";
                $codeErreur = 400;
                break;
            case 'InvalidDateFormat':
                $errorMessage = "Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA.";
                $codeErreur = 400;
                break;
            case 'InvalidAge':
                $errorMessage = "L'utilisateur doit avoir au moins $variable ans.";
                $codeErreur = 400;
                break;
            case 'InvalidPasswordFormat':
                $errorMessage = "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.";
                $codeErreur = 400;
                break;
            case 'UserNotFound':
                $errorMessage = 'Aucun utilisateur trouvé. Mot de passe ou identifiant incorrect.';
                $codeErreur = 400;
            case 'EmailNotFound':
                $errorMessage = "Aucun compte n'est associé à cet email. Veuillez vérifier et réessayer.";
                $codeErreur = 404;
                break;
            case 'AccountNotActive':
                $errorMessage = "Le compte n'est plus actif ou est suspendu.";
                $codeErreur = 403;
                break;
            case 'UnexpectedError':
                $errorMessage = 'Une erreur inattendue s\'est produite.';
                $codeErreur = 400;
                break;
            case 'InvalidPhoneNumber':
                $errorMessage = 'Le format du numéro de téléphone est invalide.';
                $codeErreur = 400;
                break;
            case 'InvalidGender':
                $errorMessage = 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.';
                $codeErreur = 400;
                break;
            case 'NotUniqueEmail':
                $errorMessage = 'Cet email est déjà utilisé par un autre compte.';
                $codeErreur = 409;
                break;
            case 'NotUniqueTel':
                $errorMessage = 'Conflit de données. Le numéro de téléphone est déjà utilisé par un autre utilisateur.';
                $codeErreur = 409;
                break;
            case 'NotFoundArtist':
                $errorMessage = "Aucun artiste trouvé pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundAlbum':
                $errorMessage = "Aucun album trouvé pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundUser':
                $errorMessage = "Aucun utilisateur trouvé pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundLabel':
                $errorMessage = "Aucun label trouvé pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundPlaylist':
                $errorMessage = "Aucune playlist trouvée pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundSong':
                $errorMessage = "Aucun son trouvé pour la page demandée.";
                $codeErreur = 404;
                break;
            case 'NotFoundAlbumId':
                $errorMessage = "L'album non trouvé. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotFoundArtistId':
                $errorMessage = "L'artiste non trouvé. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotFoundSongId':
                $errorMessage = "Le son non trouvé. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotFoundUserId':
                $errorMessage = "L'utilisateur non trouvé. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotFoundLabelId':
                $errorMessage = "Le label non trouvé. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotFoundPlaylistId':
                $errorMessage = "La playlist non trouvée. Vérifiez les informations fournies et réessayez.";
                $codeErreur = 404;
                break;
            case 'NotUniqueArtistName':
                $errorMessage = "Ce nom d'artiste est déjà pris. Veuillez en choisir un autre.";
                $codeErreur = 409;
                break;
            case 'NotActiveUser':
                $errorMessage = "Le compte n'est plus actif ou est suspendu.";
                $codeErreur = 403;
                break;
            case 'TokenInvalidMissing':
                $errorMessage = "Token de réinitialisation  manquant  ou invalide .Veuiller utiliser le lien fourni dans l'email de reinitialisation de mot de passe";
                $codeErreur = 400;
                break;
            case 'TokenPasswordExpire':
                $errorMessage = "Votre token de réinitialisation de mot de passe a expiré.Veuiller refaire une demande de reinitialisation de mot de passe.";
                $codeErreur = 400;
                break;
            case 'AccountAlreadyDesactivate':
                $errorMessage = "Le compte est déja désactivé.";
                $codeErreur = 409;
                break;
            case 'InvalidDataLength':
                $errorMessage = 'Erreur de validation des données.';
                $codeErreur = 422;
                break;

            default:
                $errorMessage = 'Erreur inconnue.';
                $codeErreur = 400;
                break;
        }

        return new JsonResponse([
            'error' => true,
            'message' => $errorMessage,
        ], $codeErreur);
    }
}
