<?php

$path = '/www/wwwroot/admin-homes/app/Exceptions/Handler.php';
$text = file_get_contents($path);
$needle = "        if (\$request->expectsJson()) {\n            \$details = '';";
$insert = <<<'PHP'
        if ($request->expectsJson()) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $details = $exception->getMessage();
                if (method_exists($exception, 'getMessages')) {
                    $details = json_encode($exception->getMessages());
                }

                return response()->json([
                    'error' => true,
                    'message' => $exception->getMessage(),
                    'details' => $details,
                    'code' => $status,
                ], $status, $exception->getHeaders());
            }

            $details = '';
PHP;

if (strpos($text, 'HttpExceptionInterface') !== false) {
    echo "Already patched\n";
    exit(0);
}

if (strpos($text, $needle) === false) {
    fwrite(STDERR, "Needle not found\n");
    exit(1);
}

file_put_contents($path, str_replace($needle, $insert, $text));
echo "Handler patched\n";
