<?php

function app_base_url(): string
{
    $base = getenv('APP_BASE_URL') ?: '/Muggle';
    $base = '/' . trim((string) $base, '/');

    return $base === '/' ? '' : $base;
}

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = app_base_url();

    if ($path === '') {
        return $base;
    }

    return $base . '/' . $path;
}
