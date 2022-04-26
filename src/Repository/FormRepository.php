<?php

namespace App\Repository;

use App\Entity\Form;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Form|null find($id, $lockMode = null, $lockVersion = null)
 * @method Form|null findOneBy(array $criteria, array $orderBy = null)
 * @method Form[]    findAll()
 * @method Form[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Form::class);
    }

    public function addDataForm($fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            INSERT INTO form VALUES (DEFAULT, :type, :fields, :send_email, NOW(), 
            NULL)
            ';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fields);

        return $conn->lastInsertId();
    }

    public function updateDataForm($id)
    {
        $conn = $this->getEntityManager()->getConnection();
        $fieldsExecute = [
            'id' => $id
        ];
        $sql = '
            UPDATE form
            SET send_email = 1, date_send = NOW()
            WHERE id = :id';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fieldsExecute);
    }

    // /**
    //  * @return Form[] Returns an array of Form objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Form
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
