<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\{Label, Repository, User};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param Repository $repository
     * @param Label      $label
     *
     * @return User[]
     */
    public function getAllSubscribedTo(Repository $repository, Label $label): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select(['u', 's'])
            ->from(User::class, 'u')
            ->join('u.subscriptions', 's')
            ->where('s.repository.url = :repositoryUrl')
            ->andWhere('s.label.normalizedName = :label')
            ->setParameters([
                ':repositoryUrl' => $repository->getUrl(),
                ':label' => $label->getNormalizedName(),
            ])
            ->getQuery()
            ->getResult();
    }

    public function findByTelegramId($id): ?User
    {
        return $this->findOneBy(['telegramId' => $id]);
    }

    public function add(User $user): void
    {
        $this->getEntityManager()->persist($user);
    }
}
