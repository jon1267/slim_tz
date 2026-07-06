<?php

declare(strict_types=1);

namespace App\Application\Actions\PostIndex;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\PostIndex\PostIndexRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

final class DeletePostIndexAction extends Action
{
    private PostIndexRepository $repository;

    /**
     * DeletePostIndexAction constructor.
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
     * Processes a DELETE request to remove a postal index.
     *
     * @return Response JSON-response API.
     */
    protected function action(): Response
    {
        $postIndex = (string)$this->resolveArg('post_index');

        $deleted = $this->repository->deleteByPostIndex($postIndex);

        if (!$deleted) {
            return $this->respondWithData([
                'message' => 'Postal index not found',
            ], 404);
        }

        return $this->respondWithData([
            'message'    => 'Postal index successfully deleted',
            'post_index' => $postIndex,
        ]);
    }
}
