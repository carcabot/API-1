<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use App\Entity\CustomerAccount;
use App\Enum\AuthorizationRole;
use App\Service\AuthenticationHelper;
use Symfony\Component\HttpFoundation\Request;

final class HomepageCustomerAccountContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $authenticationHelper;

    public function __construct(SerializerContextBuilderInterface $decorated, AuthenticationHelper $authenticationHelper)
    {
        $this->decorated = $decorated;
        $this->authenticationHelper = $authenticationHelper;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        if (CustomerAccount::class === $resourceClass &&
            isset($context['groups']) &&
            (
                $this->authenticationHelper->hasRole(AuthorizationRole::ROLE_HOMEPAGE) ||
                (
                    null !== $this->authenticationHelper->getImpersonatorUser() &&
                    \in_array(AuthorizationRole::ROLE_HOMEPAGE, $this->authenticationHelper->getImpersonatorUser()->getRoles(), true)
                )
            ) &&
            true === $normalization
        ) {
            $context['groups'] = 'role_homepage_customer_account_read';
        }

        return $context;
    }
}
