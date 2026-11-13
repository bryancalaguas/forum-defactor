<?php
declare(strict_types=1);


namespace App;


/** Thin wrapper around the Mustache templating engine. */
final class View
{
    private static ?\Mustache_Engine $engine = null;


    private static function engine(): \Mustache_Engine
    {
        return self::$engine ??= new \Mustache_Engine([
            'loader'          => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/Views'),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/Views/partials'),
            // {{ }} output is always escaped with this function.
            'escape'          => static fn ($v): string =>
                htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
    }


    public static function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');


        $data += [
            'logged_in'  => Session::userId() !== null,
            'username'   => Session::username(),
            'csrf_token' => Session::csrfToken(),
            'flash'      => Session::takeFlash(),
            'has_errors' => !empty($data['errors']),
        ];


        echo self::engine()->render($template, $data);
    }


    public static function error(int $status, string $message): void
    {
        self::render('error', ['code' => $status, 'message' => $message, 'title' => "Error $status"], $status);
    }


    public static function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303); // 303 = "see other" after a POST
        exit;
    }
}