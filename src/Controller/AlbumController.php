<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Artist;
use Symfony\Component\HttpFoundation\Request;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;
use UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use CustomException;

class AlbumController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;
        $this->repository = $entityManager->getRepository(Album::class);
    }

    #[Route('/album/{id}', name: 'app_album_delete', methods: ['DELETE'])]
    public function delete_album_by_id(int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            $this->entityManager->remove($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre album a été supprimé avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/album', name: 'post_album', methods: 'POST')]
    public function post_album(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $this->errorManager->checkRequiredAttributes($data, ['nom', 'categ', 'cover', 'year']);


            //vérif taille de caractères des string titre et catégorie album 
            $badData = [];
            if (strlen($data['nom']) > 90 || strlen($data['nom']) < 1) {
                $badData[] = 'nom';
            }
            if (strlen($data['categ']) > 20 || strlen($data['categ']) < 1) {
                $badData[] = 'categ';
            }
            if (!empty($badData)) {
                throw new CustomException(ErrorTypes::VALIDATION_ERROR);
            }


            $this->errorManager->isValidCategory($data['categ']);

            if ($this->repository->findOneBy(['nom' => $data['nom']])) {
                throw new CustomException(ErrorTypes::NOT_UNIQUE_ALBUM_TITLE);
            }

            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $uniqueId = uniqid();

            $album = new Album();
            $artist = $this->entityManager->getRepository(Artist::class)->find(1);
            $album->setArtistUserIdUser($artist);
            $album->setNom($data['nom']);
            $album->setCateg($data['categ']);
            $album->setYear($data['year']);
            $album->setIdAlbum($uniqueId);
            $album->setCreateAt($date);
            $album->setUpdateAt($date);

            if (isset($data['cover'])) {
                $explodeData = explode(",", $data['cover']);

                if (count($explodeData) == 2) {
                    $fileFormat = explode(';', $explodeData[0]);
                    $fileFormat = explode('/', $fileFormat[0]);

                    //verif format fichier
                    if ($fileFormat[1] !== 'png' && $fileFormat[1] !== 'jpeg') {
                        return $this->json([
                            'error' => true,
                            'message' => 'Erreur sur le format du fichier qui n\'est pas pris en compte.',
                        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $file = base64_decode($explodeData[1]);
                    if ($file === false) {
                        return $this->json([
                            'error' => true,
                            'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
                        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    //vérif si taille fichier entre 1MB et 7MB
                    // if (strlen($file) < 1000000 || strlen($file) > 7000000) {
                    //     return $this->json([
                    //         'error' => true,
                    //         'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.',
                    //     ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    // }

                    $email = $artist->getUserIdUser()->getEmail();
                    $fullname = $artist->getFullname();
                    $nom_album = $album->getNom();

                    $chemin = $this->getParameter('upload_directory') . '/' . $email . '/' . $fullname . '/' . $nom_album;
                    mkdir($chemin, 0777, true);
                    $getCover = $chemin . '/cover_' . $album->getIdAlbum() . '.' . $fileFormat[1];
                    file_put_contents($getCover, $file);
                }
            }

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album créé avec succès.",
                'id' => $album->getId()
            ], 201);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/album/{id}/song', name: 'post_album_id_song', methods: 'POST')]
    public function post_album_id_song(int $id, Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            parse_str($request->getContent(), $data);

            if (isset($data['nom'])) {
                $album->setNom($data['nom']);
            }
            if (isset($data['categ'])) {
                $album->setCateg($data['categ']);
            }
            if (isset($data['cover'])) {
                $album->setCover($data['cover']);
            }
            if (isset($data['year'])) {
                $album->setYear($data['year']);
            }
            if (isset($data['idalbum'])) {
                $album->setIdAlbum($data['idalbum']);
            }

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album mis à jour avec succès.",
                'idSong' => $album->getSongIdSong()
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/album/{id}', name: 'app_album_put', methods: ['PUT'])]
    public function putAlbum(Request $request, int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager, AlbumRepository $albumRepository): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            parse_str($request->getContent(), $data);

            //vérif taille de caractères des string titre et catégorie album 
            $badData = [];
            if (strlen($data['nom']) > 90 || strlen($data['nom']) < 1) {
                $badData[] = 'nom';
            }
            if (strlen($data['categ']) > 20 || strlen($data['categ']) < 1) {
                $badData[] = 'categ';
            }
            if (!empty($badData)) {
                throw new CustomException(ErrorTypes::VALIDATION_ERROR);
            }

            $this->errorManager->isValidCategory($data['categ']);

            if ($this->repository->findOneBy(['nom' => $data['nom']])) {
                throw new CustomException(ErrorTypes::NOT_UNIQUE_ALBUM_TITLE);
            }

            if (isset($data['nom'])) {
                $album->setNom($data['nom']);
            }
            if (isset($data['categ'])) {
                $album->setCateg($data['categ']);
            }
            if (isset($data['cover'])) {
                $album->setCover($data['cover']);
            }
            if (isset($data['year'])) {
                $album->setYear($data['year']);
            }
            if (isset($data['idalbum'])) {
                $album->setIdAlbum($data['idalbum']);
            }

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album mis à jour avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/album/search', name: 'app_album_get_search', methods: ['GET'])]
    public function get_album_search(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $this->errorManager->isValidCategory($_GET['categ']);

            if ((isset($_GET['label']) || isset($_GET['year']) || isset($_GET['featuring']) || isset($_GET['category']) || isset($_GET['limit']))) {
                $albums = $this->repository->findBy($_GET);
                $this->errorManager->checkNotFoundAlbum($albums);

                $album_serialized = [];
                foreach ($albums as $album) {
                    array_push($album_serialized, $album->serializer());
                }

                return new JsonResponse([
                    "error" => false,
                    "album" => $album->serializer()
                ], 200);
            } else {
                return $this->json([
                    'error' => true,
                    'message' => "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
                ], 400);
            }

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }


    #[Route('/album/{id}', name: 'app_album_get_by_id', methods: ['GET'])]
    public function get_album_by_id(int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager, AlbumRepository $albumRepository): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);
            $email =  $decodedtoken['username'];
            $request_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (empty($id)) {
                return $this->errorManager->generateError(ErrorTypes::MISSING_ALBUM_ID);
            }

            $album = $this->repository->find($id);
            if (!empty($album)) {

                $songsData = [];
                $songs = $album->getSongIdSong();
                $this->errorManager->checkNotFoundAlbumId($album);
                $albumfound = $album->serializer();
                $owner = false;
                if ($album->getArtistUserIdUser() == $request_user->getArtist()) {
                    $owner = true;
                }
                foreach ($songs as $song) {
                    if ($owner == true || $song->isVisibility() == true) {
                        //Featuring dans song
                        $featurings_serialized = [];
                        $featurings = $song->getArtistIdUser();

                        foreach ($featurings as $featuring) {

                            array_push($featurings_serialized, $featuring->serializer());
                        }
                        $songsData = $song->serializer();
                        $songsData['featuring'] = $featurings_serialized;
                    }
                }
                //Gestion Label
                $labels = $this->repository->findLabelsByAlbum($album);
                $labelnom = null;
                if (is_array($labels)) {
                    foreach ($labels as $label) {
                        $labelId = $label->getLabelId();
                        $labelnom = $labelId->getNom();
                    }
                }
                //Get artist
                $artist = $album->getArtistUserIdUser() ? $album->getArtistUserIdUser()->serializer() : [];

                $albumfound['artist'] = $artist;
                $albumfound['label'] = $labelnom;

                $albumfound['songs'] = $songsData;
            } else {
                return new JsonResponse([
                    "error" => true,
                    "message" => "Aucun album trouvé."
                ], 200);
            }
            return new JsonResponse([
                "error" => false,
                "album" => $albumfound
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/albums', name: 'app_albums_get', methods: ['GET'])]
    public function get_all_albums(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);
            $email =  $decodedtoken['username'];
            $request_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            parse_str($request->getContent(), $data);

            $albumsPerPage = 5;
            $numPage = $_GET["currentPage"];
            if ($numPage <= 0) {
                throw new CustomException(ErrorTypes::INVALID_PAGE);
            }
            // Récupération page demandée
            $page = $request->query->getInt('currentPage', $numPage);

            $offset = ($page - 1) * $albumsPerPage;

            $albums = $this->repository->findBy([], null, $albumsPerPage, $offset);

            $this->errorManager->checkNotFoundAlbum($albums);

            $album_serialized = [];
            foreach ($albums as $album) {
                $songsData = [];
                $songs = $album->getSongIdSong();
                $this->errorManager->checkNotFoundAlbumId($album);
                $albumfound = $album->serializer();
                $owner = false;
                if ($album->getArtistUserIdUser() == $request_user->getArtist()) {
                    $owner = true;
                }
                foreach ($songs as $song) {
                    if ($owner == true || $song->isVisibility() == true) {
                        //Featuring dans song
                        $featurings_serialized = [];
                        $featurings = $song->getArtistIdUser();

                        foreach ($featurings as $featuring) {

                            array_push($featurings_serialized, $featuring->serializer());
                        }
                        $songsData = $song->serializer();
                        $songsData['featuring'] = $featurings_serialized;
                    }
                }

                $albumfound['songs'] = $songsData;
                //Get Label
                $labels = $this->repository->findLabelsByAlbum($album);
                $labelnom = null;
                if (is_array($labels)) {
                    foreach ($labels as $label) {
                        $labelId = $label->getLabelId();
                        $labelnom = $labelId->getNom();
                    }
                }
                //Get artist
                $artist = $album->getArtistUserIdUser() ? $album->getArtistUserIdUser()->serializer() : [];

                $albumfound['artist'] = $artist;
                $albumfound['label'] = $labelnom;
                array_push($album_serialized, $albumfound);
            }

            $totalAlbums = count($this->repository->findAll());

            $totalPages = ceil($totalAlbums / $albumsPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $albumsPerPage;

                // Récupération albums page suivante
                $nextPageAlbums = $this->repository->findBy([], null, $albumsPerPage, $nextPageOffset);

                $nextPageAlbumsSerialized = [];
                foreach ($nextPageAlbums as $album) {
                    array_push($nextPageAlbumsSerialized, $album->serializer());
                }
            }

            if (!empty($album_serialized)) {
                $currentSerializedContent = $album_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPageAlbumsSerialized
                $currentSerializedContent = $nextPageAlbumsSerialized;
                $currentPage = $nextPage;
                $id = $album->getId();

                $serializedAlbums[] = $album->serializer();
            }

            $response = [
                "error" => false,
                "albums" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalAlbums" => $totalAlbums
                ]
            ];

            if ($page = $nextPage) {
                $album_serialized = null;
            }

            return $this->json($response, 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
            return new JsonResponse([
                'error' => true,
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }
}
