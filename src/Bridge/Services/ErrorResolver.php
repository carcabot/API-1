<?php

declare(strict_types=1);

namespace App\Bridge\Services;

final class ErrorResolver
{
    /**
     * Best effort to translate non-standard error returns from nodejs version.
     *
     * @param array $guzzleResponse
     *
     * @return string
     */
    public static function getErrorMessage(array $guzzleResponse)
    {
        $error = $guzzleResponse['message'];
        if (!empty($guzzleResponse['data']) && (empty($error) || 'fail' === $error)) {
            $error = $guzzleResponse['data'];
        }

        if (true === \is_array($error)) {
            $error = \trim(\json_encode($error), '"');
        }

        return $error;
    }
}
