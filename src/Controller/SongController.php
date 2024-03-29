<?php

namespace App\Controller;

use App\Entity\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Album;

class SongController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
    }

    #[Route('/song', name: 'app_songs_get_all', methods: 'GET')]
    public function getSongs()
    {
        $songs = $this->repository->findAll();

        if (!$songs) {
            return $this->json([
                'error' => true,
                'message' => "Aucun son n'a été trouvé",
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $serializedSongs = [];
        foreach ($songs as $song) {
            $serializedSongs[] = [
                'id' => $song->getId(),
                'album' => [
                    'id' => $song->getAlbum()->getId(),
                    'nom' => $song->getAlbum()->getNom(),
                    'categ' => $song->getAlbum()->getCateg(),
                    'cover' => $song->getAlbum()->getCover(),
                    'year' => $song->getAlbum()->getYear(),
                ],
                'title' => $song->getTitle(),
                'url' => $song->getUrl(),
                'cover' => $song->getCover(),
                'visibility' => $song->isVisibility()
            ];
        }

        return new JsonResponse($serializedSongs);
    }

    #[Route('/song/{id}', name: 'app_song_get', methods: 'GET')]
    public function getSong(int $id): JsonResponse
    {
        $song = $this->repository->find($id);

        if (!$song) {
            return $this->json([
                'error' => 'Song not found',
                'songid' => $id,
            ]);
        }

        return $this->json([
            'id' => $song->getId(),
            'album' => [
                'id' => $song->getAlbum()->getId(),
                'nom' => $song->getAlbum()->getNom(),
                'categ' => $song->getAlbum()->getCateg(),
                'cover' => $song->getAlbum()->getCover(),
                'year' => $song->getAlbum()->getYear(),
            ],
            'title' => $song->getTitle(),
            'url' => $song->getUrl(),
            'cover' => $song->getCover(),
            'visibility' => $song->isVisibility()
        ]);
    }

    #[Route('/song', name: 'app_song_post', methods: 'POST')]
    public function postSong(Request $request): JsonResponse
    {
        parse_str($request->getContent(), $data);

        if (!isset($data['title']) || !isset($data['url']) || !isset($data['cover']) || !isset($data['visibility']) || !isset($data['album_id']) || !isset($data['create_at']) || !isset($data['song'])) {
            return new JsonResponse([
                'error' => 'Missing data',
                'data' => $data
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $album = $this->entityManager->getRepository(Album::class)->find($data['album_id']);

        if (!$album) {
            return new JsonResponse(['error' => 'Album not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $song = new Song();
        $song->setAlbum($album);
        $song->setTitle($data['title']);
        $song->setUrl($data['url']);
        $song->setCover($data['cover']);
        $song->setIdSong($data['id_song']);
        $song->setIdSong($data['song']);
        $song->setVisibility($data['visibility']);
        $song->setCreateAt($date);

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return new JsonResponse([
            'validate' => 'Song added successfully',
            'id' => $song->getId()
        ]);
    }

    #[Route('/song/{id}', name: 'app_song_put', methods: 'PUT')]
    public function putSong(Request $request, int $id): JsonResponse
    {
        $song = $this->repository->find($id);

        if (!$song) {
            return $this->json([
                'error' => 'Song not found',
                'songid' => $id,
            ]);
        }
        parse_str($request->getContent(), $data);

        if (isset($data['title'])) {
            $song->setTitle($data['title']);
        }
        if (isset($data['url'])) {
            $song->setUrl($data['url']);
        }
        if (isset($data['cover'])) {
            $song->setCover($data['cover']);
        }
        if (isset($data['visibility'])) {
            $song->setVisibility($data['visibility']);
        }

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Song updated successfully']);
    }

    #[Route('/song/{id}', name: 'app_song_delete', methods: 'DELETE')]
    public function deleteSong(int $id): JsonResponse
    {
        $song = $this->repository->find($id);

        if (!$song) {
            return $this->json([
                'error' => 'Song not found',
                'songid' => $id,
            ]);
        }

        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return $this->json(['message' => 'Song deleted successfully']);
    }
}
