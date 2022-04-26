<?php

namespace App\Repository;

use App\Entity\ProductProp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductProp|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductProp|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductProp[]    findAll()
 * @method ProductProp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductPropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductProp::class);
    }

    // /**
    //  * @return ProductProp[] Returns an array of ProductProp objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductProp
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
