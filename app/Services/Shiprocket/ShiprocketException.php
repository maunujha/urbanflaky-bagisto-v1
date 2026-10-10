<?php

namespace App\Services\Shiprocket;

use Illuminate\Http\Client\Response;
use RuntimeException;

class ShiprocketException extends RuntimeException
{
    public static function fromResponse(Response $response, string $path): self
    {
        $message = $response->json('message') ?: $response->body();

        if (is_array($message)) {
            $message = json_encode($message);
        }

        if ($errors = $response->json('errors')) {
            $message .= ' '.json_encode($errors);
        }

        return new self(sprintf('Shiprocket %s failed (HTTP %d): %s', $path, $response->status(), mb_strimwidth((string) $message, 0, 500, '…')), $response->status());
    }
}
