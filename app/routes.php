<?php

declare(strict_types=1);

use App\Application\Actions\PostIndex\CreatePostIndexesAction;
use App\Application\Actions\PostIndex\DeletePostIndexAction;
use App\Application\Actions\PostIndex\ListPostIndexesAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/api/post-indexes', function (Group $group) {
        $group->get('', ListPostIndexesAction::class);
        $group->post('', CreatePostIndexesAction::class);
        $group->delete('/{post_index}', DeletePostIndexAction::class);
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
