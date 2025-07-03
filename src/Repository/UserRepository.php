<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByNameOrNick(string $value, ?string $firstName = null): ?User
    {
        $result = $this->createQueryBuilder('u')
            ->where('u.lastName = :val')
            ->orWhere("u.nicks LIKE :nick")
            ->setParameter('val', $value)
            ->setParameter('nick', '%' . $value . '%')
            ->getQuery()
            ->getResult()
        ;

        if (0 === count($result)) {
            return null;
        }

        if (1 === count($result)) {
            return $result[0];
        }

        if (null !== $firstName) {
            /** @var User $user */
            foreach ($result as $user) {
                if ($user->isSameFirstName($firstName)) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function getList(): array
    {
        $list = $this->findBy(['isActive' => true], ['lastName' => 'ASC', 'firstName' => 'ASC']);

        $result = [];
        foreach ($list as $user) {
            $result[$user->getId()] = $user->gelFullName();
        }

        return $result;
    }
}
