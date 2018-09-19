<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\{IssueLabeledSubscription, User};
use App\Entity\{Label, Repository};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method IssueLabeledSubscription|null findOneBy(array $criteria, array $orderBy = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, IssueLabeledSubscription::class);
    }

    public function findSubscription(User $user, Repository $repository, Label $label): ?IssueLabeledSubscription
    {
        return $this->findOneBy([
            'user' => $user->getId(),
            'repository.url' => $repository->getUrl(),
            'label.normalizedName' => $label->getNormalizedName(),
        ]);
    }

    /**
     * @return Repository[]
     */
    public function findAllRepositories(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('NEW App:Repository(s.repository.url)')
            ->distinct()
            ->from(IssueLabeledSubscription::class, 's')
            ->getQuery()
            ->getResult();
    }
}
