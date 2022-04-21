<?php

namespace App\Repository;

use App\Entity\StudentFormationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentFormationStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentFormationStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentFormationStatus[]    findAll()
 * @method StudentFormationStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentFormationStatusRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentFormationStatus::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(StudentFormationStatus $entity, bool $flush = true): void
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
    public function remove(StudentFormationStatus $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne toutes les formations par étudiant
     *
     * @param integer $id
     * @return array|void
     */
    public function findFormationsByUser(int $id)
    {
        $stmt = $this->conn->prepare("SELECT formations_id, status
            FROM student_formation_status
            WHERE person_details_id = :id");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchAllAssociativeIndexed();

        return $specs;
    }

    // /**
    //  * @return StudentFormationStatus[] Returns an array of StudentFormationStatus objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StudentFormationStatus
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
