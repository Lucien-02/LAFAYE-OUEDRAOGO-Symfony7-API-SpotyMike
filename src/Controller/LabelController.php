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
use CustomException;

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
    public function getLabels(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager)
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $labelsPerPage = 5;
            $numPage = $_GET["currentPage"];

            // Récupération page demandée
            $page = $request->query->getInt('currentPage', $numPage);

            $offset = ($page - 1) * $labelsPerPage;

            $labels = $this->repository->findBy([], null, $labelsPerPage, $offset);

            $this->errorManager->checkNotFoundLabel($labels);

            $label_serialized = [];
            foreach ($labels as $label) {
                array_push($label_serialized, $label->serializer());
            }

            $totalLabels = count($this->repository->findAll());

            $totalPages = ceil($totalLabels / $labelsPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $labelsPerPage;

                // Récupération labels page suivante
                $nextPageLabels = $this->repository->findBy([], null, $labelsPerPage, $nextPageOffset);

                $nextPageLabelsSerialized = [];
                foreach ($nextPageLabels as $label) {
                    array_push($nextPageLabelsSerialized, $label->serializer());
                }
            }

            if (!empty($label_serialized)) {
                $currentSerializedContent = $label_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPageLabelsSerialized
                $currentSerializedContent = $nextPageLabelsSerialized;
                $currentPage = $nextPage;
            }

            $response = [
                "error" => false,
                "labels" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalLabels" => $totalLabels
                ]
            ];

            if ($page = $nextPage) {
                $label_serialized = null;
            }

            return $this->json($response, 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/label/{id}', name: 'app_label_get', methods: 'GET')]
    public function getLabel(int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundLabelId($label);

            return $this->json([
                'id' => $label->getId(),
                'nom' => $label->getNom()
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
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
            ], 201);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/label/{id}', name: 'app_label_put', methods: 'PUT')]
    public function putLabel(Request $request, int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundLabelId($label);

            parse_str($request->getContent(), $data);

            if (isset($data['nom'])) {
                $label->setNom($data['nom']);
            }

            $this->entityManager->persist($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Label mis à jour avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }

    #[Route('/label/{id}', name: 'app_label_delete', methods: 'DELETE')]
    public function deleteLabel(int $id): JsonResponse
    {
        try {
            $label = $this->repository->find($id);

            $this->errorManager->checkNotFoundLabelId($label);

            $this->entityManager->remove($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre label a été supprimé avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new CustomException(ErrorTypes::UNEXPECTED_ERROR);
        } catch (CustomException $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
        }
    }
}
