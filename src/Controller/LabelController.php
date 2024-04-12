<?php

namespace App\Controller;

use App\Entity\Label;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;

class LabelController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;
        
        $this->repository = $entityManager->getRepository(Label::class);
    }

    #[Route('/label/all', name: 'app_labels_get_all', methods: 'GET')]
    public function getLabels(TokenInterface $token, JWTTokenManagerInterface $JWTManager)
    {

        $labels = $this->repository->findAll();

        $this->errorManager->checkNotFoundEntity($labels, "label");

        $serializedLabels = [];
        foreach ($labels as $label) {
            $serializedLabels[] = [
                'id' => $label->getId(),
                'nom' => $label->getNom()
            ];
        }

        $decodedtoken = $JWTManager->decode($token);
        if ($decodedtoken['type'] == 'reset-password') {
            return new JsonResponse(
                [
                    'error' => true,
                    'message' => 'ca passe pas brother'
                ],
                404
            );
        }
        return new JsonResponse($serializedLabels);
    }

    #[Route('/label/{id}', name: 'app_label_get', methods: 'GET')]
    public function getLabel(int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($label, 'Label');

            return $this->json([
                'id' => $label->getId(),
                'nom' => $label->getNom()
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/label', name: 'app_label_post', methods: 'POST')]
    public function postLabel(Request $request): JsonResponse
    {
        try {
            parse_str($request->getContent(), $data);

            $this->errorManager->checkRequiredAttributes($data, ['nom']);

            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $label = new Label();
            $label->setNom($data['nom']);
            $label->setCreateAt($date);
            $label->setUpdateAt($date);

            $this->entityManager->persist($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Label créé avec succès."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/label/{id}', name: 'app_label_put', methods: 'PUT')]
    public function putLabel(Request $request, int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($label, 'Label');

            parse_str($request->getContent(), $data);

            if (isset($data['nom'])) {
                $label->setNom($data['nom']);
            }

            $this->entityManager->persist($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Label mis à jour avec succès."
            ]);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/label/{id}', name: 'app_label_delete', methods: 'DELETE')]
    public function deleteLabel(int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundEntityId($label, 'Label');

            $this->entityManager->remove($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre label a été supprimé avec succès."
            ]);
            
            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
