<?php

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * Photos publiées d'un utilisateur, triées par numéro d'ordre.
     * Une photo est publiée quand son numéro d'ordre n'est pas vide.
     *
     * @return Photo[]
     */
    public function findPublishedByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.position IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
