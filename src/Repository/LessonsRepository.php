<?php

namespace App\Repository;

use App\Entity\Lessons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Lessons|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lessons|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lessons[]    findAll()
 * @method Lessons[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonsRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lessons::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Lessons $entity, bool $flush = true): void
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
    public function remove(Lessons $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne le dernier rang d'un cours de section
     *
     * @param integer $id
     * @return array
     */
    public function findMaxRankCourseBySection(int $id)
    {
        $stmt = $this->conn->prepare("SELECT MAX(rank_order) as last_rank
            FROM lessons
            WHERE sections_id = :id");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchAllAssociative();

        return $specs;
    }

    /**
     * Retourne toutes les leçons par sections de formation pour les étudiants
     *
     * @param integer $id
     * @return array|void
     */
    public function findLessonsBySectionsForStudent(int $id)
    {
        $l = [];

        foreach($this->conn->iterateAssociativeIndexed(
            "SELECT number, lessons.title, lessons.id
            FROM sections
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            WHERE formations_id = :id
            ORDER BY sections.rank_order ASC, lessons.rank_order ASC", ['id' => $id]) as $k => $v)
        {
            $l[$k][] = $v;
        }

        return $l;
    }

    /**
     * Retourne le statut des leçons pour les étudiants
     *
     * @param integer $id
     * @param integer $student_id
     * @return array|void
     */
    public function findLessonsStatusForStudent(int $id, int $student_id)
    {
        $ls = [];

        foreach($this->conn->iterateAssociativeIndexed(
            "SELECT number, student_lesson_status.lessons_id AS id, status
            FROM sections
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            LEFT JOIN student_lesson_status
            ON lessons.id = student_lesson_status.lessons_id
            WHERE formations_id = :id
            AND person_details_id = :student_id", ['id' => $id, 'student_id' => $student_id]) as $k => $v)
        {
            $ls[$k][] = $v;
        }

        return $ls;
    }

    /**
     * Obtient toute les leçons par formation
     *
     * @param integer $id
     * @return array|void
     */
    public function findAllLessons(int $id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(lessons.id)
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            WHERE formations.id = :id
            AND lessons.sections_id IS NOT NULL");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchOne();

        return $specs;
    }
    // /**
    //  * @return Lessons[] Returns an array of Lessons objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Lessons
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
