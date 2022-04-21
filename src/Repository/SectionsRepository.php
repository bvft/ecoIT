<?php

namespace App\Repository;

use App\Entity\Sections;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sections|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sections|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sections[]    findAll()
 * @method Sections[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SectionsRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sections::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Sections $entity, bool $flush = true): void
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
    public function remove(Sections $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne le dernier rang d'une section de formation
     *
     * @param integer $id
     * @return array
     */
    public function findMaxRankBySection(int $id)
    {
        $stmt = $this->conn->prepare("SELECT MAX(rank_order) as last_rank
            FROM sections
            WHERE formations_id = :id");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchAllAssociative();

        return $specs;
    }

    /**
     * Retourne toutes les sections par formation
     *
     * @param array $data
     * @return array|void
     */
    public function findSectionsByFormation(array $data)
    {
        $formation_id = $data[0];

        $stmt = $this->conn->prepare("SELECT title, id
            FROM sections
            WHERE formations_id = :id");
        $result = $stmt->executeQuery(['id' => $formation_id]);
        $specs = $result->fetchAllKeyValue();

        return $specs;
    }

    /**
     * Retourne toutes les sections par formation pour les étudiants
     *
     * @param int $id
     * @return array|void
     */
    public function findSectionsByFormationForStudent(int $id)
    {
        $stmt = $this->conn->prepare("SELECT number, title
            FROM sections
            WHERE formations_id = :id
            ORDER BY rank_order ASC");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchAllAssociativeIndexed();

        return $specs;
    }

    /**
     * Retourne toutes les sections par instructeur
     *
     * @param integer $id
     * @return array|void
     */
    public function findAllSectionsByUser(int $id)
    {
        $stmt = $this->conn->prepare("SELECT sections.id
            FROM formations
            LEFT JOIN sections
            ON formations.id = formations_id
            WHERE person_details_id = :id");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchAllAssociativeIndexed();

        return $specs;
    }

    /**
     * Retourne toutes les sections par formation
     *
     * @param int $formation_id
     * @return Object
     */
    /*public function findSectionsByFormation(int $formation_id)
    {
        return $this->createQueryBuilder('s')
            ->where('s.formations = :id')
            ->setParameter('id', $formation_id);
    }*/

    // /**
    //  * @return Sections[] Returns an array of Sections objects
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
    public function findOneBySomeField($value): ?Sections
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
