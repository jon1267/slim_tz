<?php

declare(strict_types=1);

namespace App\Application\Actions\PostIndex;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\PostIndex\PostIndexRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

final class ListPostIndexesAction extends Action
{
    private PostIndexRepository $repository;

    /**
     * ListPostIndexesAction constructor.
     *
     * @param LoggerInterface $logger Logger of the applicaton.
     * @param PostIndexRepository $repository Repository postal indexes.
     */
    public function __construct(LoggerInterface $logger, PostIndexRepository $repository)
    {
        parent::__construct($logger);

        $this->repository = $repository;
    }

    /**
     * Processes a GET request to retrieve postal codes.
     *
     * Supported query parameters:
     * - post_index — exact search by postal index;
     * - address — search by address or part of the address;
     * - page — page number for the regular list.
     *
     * @return Response JSON-response API.
     */
    protected function action(): Response
    {
        $params = $this->request->getQueryParams();

        if (!empty($params['post_index'])) {
            $item = $this->repository->findByPostIndex((string)$params['post_index']);

            if ($item === null) {
                return $this->respondWithData([
                    'message' => 'Почтовый индекс не найден',
                ], 404);
            }

            return $this->respondWithData($item);
        }

        if (!empty($params['address'])) {
            return $this->respondWithData([
                'items' => $this->repository->findByAddress((string)$params['address']),
            ]);
        }

        $page = isset($params['page']) ? (int)$params['page'] : 1;

        return $this->respondWithData([
            'page'  => max(1, $page),
            'limit' => 50,
            'items' => $this->repository->findPaginated($page),
        ]);
    }
}
