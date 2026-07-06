<?php

declare(strict_types=1);

namespace App\Application\Actions\PostIndex;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\PostIndex\PostIndexRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

final class CreatePostIndexesAction extends Action
{
    private PostIndexRepository $repository;

    /**
     * CreatePostIndexesAction constructor.
     *
     * @param LoggerInterface $logger Logger of the application.
     * @param PostIndexRepository $repository Repository postal indexes.
     */
    public function __construct(LoggerInterface $logger, PostIndexRepository $repository)
    {
        parent::__construct($logger);

        $this->repository = $repository;
    }

    /**
     * Processes a POST request to add one or more postal indexes.
     *
     * API accepts JSON:
     * - one object;
     * - or an array of objects.
     *
     * @return Response JSON-response API.
     *
     * @throws HttpBadRequestException If the request body is invalid.
     */
    protected function action(): Response
    {
        $data = $this->getFormData();

        if ($data === null) {
            throw new HttpBadRequestException($this->request, 'Empty request body');
        }

        $items = $this->normalizeItems((array)$data);
        $this->validateItems($items);

        $createdCount = $this->repository->upsertMany($items);

        return $this->respondWithData([
            'message' => 'Postal indexes successfully saved',
            'count'   => $createdCount,
        ], 201);
    }

    /**
     * Normalizes the input data into an array of records.
     *
     * @param array<string|int, mixed> $data Data from the JSON request.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $data): array
    {
        // If one object is received, wrap it in an array.
        if (isset($data['post_index'])) {
            return [$data];
        }

        return $data;
    }

    /**
     * Validates the input records before saving.
     *
     * @param array<int, array<string, mixed>> $items List of records.
     *
     * @throws HttpBadRequestException
     */
    private function validateItems(array $items): void
    {
        if ($items === []) {
            throw new HttpBadRequestException($this->request, 'The list of postal indexes is empty');
        }

        foreach ($items as $item) {
            if (empty($item['post_index'])) {
                throw new HttpBadRequestException($this->request, 'The post_index field is required');
            }

            if (!is_string($item['post_index']) && !is_numeric($item['post_index'])) {
                throw new HttpBadRequestException($this->request, 'The post_index field must be a string or a number');
            }
        }
    }
}
