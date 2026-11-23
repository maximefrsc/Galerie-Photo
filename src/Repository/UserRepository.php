<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Utilisateurs qui ont au moins une photo publiée (numéro d'ordre renseigné).
     *
     * @return User[]
     */
    public function findWithPublishedPhotos(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.photos', 'p')
            ->where('p.position IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
