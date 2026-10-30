<?php
// backend_php/server.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve API requests
if (preg_match('#^/api/(.*)$#', $uri, $matches)) {
    $script = __DIR__ . '/api/' . $matches[1];
    if (file_exists($script) && is_file($script)) {
        require $script;
        exit;
    }
}

// Serve public static files (css, js, images)
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && is_file($publicFile)) {
    return false; // Let PHP built-in server handle standard static file MIME
}

// Default to main index.php
require __DIR__ . '/public/index.php';

