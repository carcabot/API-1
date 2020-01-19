<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends EntityRepository implements UserLoaderInterface
{
    use KeywordSearchRepositoryTrait;

    /**
     * {@inheritdoc}
     *
     * @see http://symfony.com/doc/current/security/entity_provider.html#using-a-custom-query-to-load-the-user
     */
    public function loadUserByUsername($username): ?UserInterface
    {
        $qb = $this->createQueryBuilder('user');

        $qb->select('user')
            ->where($qb->expr()->eq('user.email', $qb->expr()->literal(\strtolower($username))))
            ->orWhere($qb->expr()->eq('lower(user.username)', 'lower(:username)'))
            ->setParameter('username', $username);

        $user = $qb->getQuery()->getOneOrNullResult();

        if (null === $user) {
            $qb = $this->createQueryBuilder('user');

            $qb->select('user')
                ->innerJoin('user.bridgeUser', 'bridgeUser')
                ->where($qb->expr()->eq('bridgeUser.bridgeUserId', $qb->expr()->literal($username)));

            $user = $qb->getQuery()->getOneOrNullResult();
        }

        return $user;
    }
}
