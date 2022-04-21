<?php

namespace App\Repository;

use App\Entity\PersonLoginInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method PersonLoginInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method PersonLoginInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersonLoginInfo[]    findAll()
 * @method PersonLoginInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonLoginInfoRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonLoginInfo::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PersonLoginInfo $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(PersonLoginInfo $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof PersonLoginInfo) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Compte les utilisateurs par rôles
     *
     * @return array
     */
    public function countUserByRoles(): array
    {
        /* SELECT SUBSTR(roles, 3, LENGTH(roles) - 4) as role, count(id)
            FROM person_login_info
            GROUP BY roles;
        */
        return $this->createQueryBuilder('cubr')
            ->select('count(cubr.id), cubr.roles')
            ->groupBy('cubr.roles')
            ->getQuery()->getResult();
    }

    public function countUserByRoleStudent()
    {
        $stmt = $this->conn->prepare("SELECT person_login_info.id, pseudo, person_login_info_id
            FROM person_login_info
            LEFT JOIN person_details
            ON person_login_info.id = person_details.person_login_info_id
        where roles LIKE '%STUDENT%'");
        $result = $stmt->executeQuery();
        $specs = $result->fetchAllAssociative();

        return $specs;
    }
    
    // /**
    //  * @return PersonLoginInfo[] Returns an array of PersonLoginInfo objects
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
    public function findOneBySomeField($value): ?PersonLoginInfo
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
