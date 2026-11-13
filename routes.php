<?php
// All URL → controller mappings live here.
use App\View;
use Bramus\Router\Router;


return function (Router $router): void {
    $router->setNamespace('\App\Controllers');


    // Public reading
    $router->get('/', 'DiscussionController@index');
    $router->get('/discussions/([^/]+)', 'DiscussionController@show');


    // Accounts
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->post('/logout', 'AuthController@logout');


    // Authenticated writes (top-level comment AND reply use the same route;
    // a reply simply includes a parent_id field)
    $router->post('/discussions/([^/]+)/comments', 'CommentController@store');


    $router->set404(function (): void {
        View::error(404, 'Page not found.');
    });
};