<?php

class Redirect
{
    public static function to(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::to($referer);
    }

    public static function route(string $name, array $params = []): never
    {
        global $router;
        self::to($router->route($name, $params));
    }

    public static function with(string $key, mixed $value): self
    {
        Session::flash($key, $value);
        return new self();
    }
}