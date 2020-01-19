<?php

declare(strict_types=1);

namespace App\Security\Guard;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator as BaseAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor;

class JWTTokenAuthenticator extends BaseAuthenticator
{
    /**
     * @return TokenExtractor\TokenExtractorInterface
     */
    protected function getTokenExtractor()
    {
        $tokenExtractors = [];
        $tokenExtractor = parent::getTokenExtractor();

        $tokenExtractors[] = $tokenExtractor;
        $tokenExtractors[] = new TokenExtractor\AuthorizationHeaderTokenExtractor(null, 'x-access-token');

        return new TokenExtractor\ChainTokenExtractor($tokenExtractors);
    }
}
