<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function uniqEmailUser(string $email)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT count(*) as count
            FROM "user" WHERE email = :email
            ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
        $res = $stmt->executeQuery();

        $resB = $res->fetchAssociative();
        if (isset($resB) && !empty($resB['count']) && $resB['count'] > 0) {
            return true;
        }

        return false;
    }

    public function uniqPhoneUser(string $mobilePhone)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT count(*) as count
            FROM "user" WHERE mobile_phone = :mobile_phone
            ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':mobile_phone', $mobilePhone, \PDO::PARAM_STR);
        $res = $stmt->executeQuery();

        $resB = $res->fetchAssociative();
        if (isset($resB) && !empty($resB['count']) && $resB['count'] > 0) {
            return true;
        }

        return false;
    }

    public function addUser($fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb->insert('"user"')
            ->values(
                [
                    'email' => '?',
                    'roles' => '?',
                    'password' => '?',
                    'first_name' => '?',
                    'last_name' => '?',
                    'patronymic_name' => '?',
                    'mobile_phone' => '?',
                    'type_user' => '?',
                    'guid' => '?',
                    'certified' => '?',
                    'blocked' => '?',
                    'checked' => '?'
                ]
            )
            ->setParameter(0, $fields['email'])
            ->setParameter(1, $fields['roles'])
            ->setParameter(2, $fields['password'])
            ->setParameter(3, $fields['first_name'])
            ->setParameter(4, $fields['last_name'])
            ->setParameter(5, $fields['patronymic_name'])
            ->setParameter(6, $fields['mobile_phone'])
            ->setParameter(7, $fields['type_user'])
            ->setParameter(8, $fields['guid'])
            ->setParameter(9, $fields['certified'], 'integer')
            ->setParameter(10, $fields['blocked'], 'integer')
            ->setParameter(11, $fields['checked'], 'integer');

            if ($fields['type_user'] == 'ur1' || $fields['type_user'] == 'ur2') {
                $qb->setValue('inn', '?')
                    ->setValue('opfname', '?')
                    ->setParameter(12, $fields['inn'])
                    ->setParameter(13, $fields['opfname']);
                if ($fields['type_user'] == 'ur1') {
                    $qb->setValue('kpp', '?')
                        ->setParameter(14, $fields['kpp']);
                }
            }
        
            $qb->execute();
            
        return $conn->lastInsertId();
    }

    public function updateUser($userId, $fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb->update('"user"');

            if (isset($fields['roles'])) {
                $qb->set('roles', ':roles')
                    ->setParameter('roles', $fields['roles']);
            }
            if (isset($fields['inn'])) {
                $qb->set('inn', ':inn')
                    ->setParameter('inn', $fields['inn']);
            }
            if (isset($fields['kpp'])) {
                $qb->set('kpp', ':kpp')
                    ->setParameter('kpp', $fields['kpp']);
            }
            if (isset($fields['certified'])) {
                $qb->set('certified', ':certified')
                    ->setParameter('certified', $fields['certified'], 'integer');
            }
            if (isset($fields['blocked'])) {
                $qb->set('blocked', ':blocked')
                    ->setParameter('blocked', $fields['blocked'], 'integer');
            }
            if (isset($fields['checked'])) {
                $qb->set('checked', ':checked')
                    ->setParameter('checked', $fields['checked'], 'integer');
            }
            if (isset($fields['price_level'])) {
                $qb->set('price_level', ':price_level')
                    ->setParameter('price_level', $fields['price_level']);
            }
            if (isset($fields['discount'])) {
                $qb->set('discount', ':discount')
                    ->setParameter('discount', $fields['discount']);
            }
            if (isset($fields['opfname'])) {
                $qb->set('opfname', ':opfname')
                    ->setParameter('opfname', $fields['opfname']);
            }
            if (isset($fields['password'])) {
                $qb->set('password', ':password')
                    ->setParameter('password', $fields['password']);
            }
            if (isset($fields['first_name'])) {
                $qb->set('first_name', ':first_name')
                    ->setParameter('first_name', $fields['first_name']);
            }
            if (isset($fields['last_name'])) {
                $qb->set('last_name', ':last_name')
                    ->setParameter('last_name', $fields['last_name']);
            }
            if (isset($fields['patronymic_name'])) {
                $qb->set('patronymic_name', ':patronymic_name')
                    ->setParameter('patronymic_name', $fields['patronymic_name']);
            }
            if (isset($fields['message'])) {
                $qb->set('message', ':message')
                    ->setParameter('message', $fields['message']);
            }
            $qb->where('id =:user_id')
                ->setParameter('user_id', $userId);

            $qb->execute();
            
        return true;
    }

    public function getUserById($id)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT * FROM "user" 
            WHERE id = :user_id
            ';
        $stmt = $conn->prepare($sql);    
        $stmt->bindValue(':user_id', $id, \PDO::PARAM_INT);
        $res = $stmt->executeQuery();

        return $res->fetchAssociative();
    }

    public function getHashPassword($passRaw)
    {
        $user = new User();
        return $this->passwordHasher->hashPassword($user, $passRaw);
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
