<?php

namespace App\Controller;

use App\Entity\Label;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LabelController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Label::class);
    }

    #[Route('/labels', name: 'app_labels_get_all', methods: 'GET')]
    public function getLabels()
    {
        $labels = $this->repository->findAll();

        if (!$labels) {
            return new JsonResponse([
                'error' => true,
                'message' => "Aucun label trouvé"
            ],
            404);
        }

        $serializedLabels = [];
        foreach ($labels as $label) {
            $serializedLabels[] = [
                'id' => $label->getId(),
                'nom' => $label->getNom()
            ];
        }

        return new JsonResponse($serializedLabels);
    }

    #[Route('/label/{id}', name: 'app_label_get', methods: 'GET')]
    public function getLabel(int $id): JsonResponse
    {
        $label = $this->repository->find($id);

        if (!$label) {
            return new JsonResponse([
                'error' => true,
                'message' => "Label introuvable",
                'label_id' => $id,
            ], 404);
        }

        return $this->json([
            'id' => $label->getId(),
            'nom' => $label->getNom()
        ]);
    }

    #[Route('/label', name: 'app_label_post', methods: 'POST')]
    public function postLabel(Request $request): JsonResponse
    {
        parse_str($request->getContent(), $data);

        if (!isset($data['nom']) || !isset($data['create_at'])) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes'
            ], 
            400);
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $label = new Label();
        $label->setNom($data['nom']);
        $label->setCreateAt($date);
        $label->setUpdateAt($date);

        $this->entityManager->persist($label);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Label ajouté avec succès',
            'id' => $label->getId()
        ]);
    }

    #[Route('/label/{id}', name: 'app_label_put', methods: 'PUT')]
    public function putLabel(Request $request, int $id): JsonResponse
    {
        $label = $this->repository->find($id);

        if (!$label) {
            return new JsonResponse([
                'error' => true,
                'message' => "Label introuvable",
                'label_id' => $id,
            ], 404);
        }
        parse_str($request->getContent(), $data);

        if (isset($data['nom'])) {
            $label->setNom($data['nom']);
        }

        $this->entityManager->persist($label);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Label mis à jour avec succès'
        ]);
    }

    #[Route('/label/{id}', name: 'app_label_delete', methods: 'DELETE')]
    public function deleteLabel(int $id): JsonResponse
    {
        $label = $this->repository->find($id);

        if (!$label) {
            return new JsonResponse([
                'error' => true,
                'message' => "Label introuvable",
                'label_id' => $id,
            ], 404);
        }

        $this->entityManager->remove($label);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'message' => 'Votre label a été supprimé avec succès'
        ]);
    }
}
