<?php

namespace App\Repository;

use App\Entity\StudentLessonStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentLessonStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentLessonStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentLessonStatus[]    findAll()
 * @method StudentLessonStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentLessonStatusRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentLessonStatus::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(StudentLessonStatus $entity, bool $flush = true): void
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
    public function remove(StudentLessonStatus $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Permet de mettre à jour les cours lus par l'étudiant
     *
     * @param array $answers
     * @param integer $id
     * @param integer $sections_id
     * @return void
     */
    public function updateAnswers(int $status, int $id, int $lessons_id): void
    {
        $stmt = $this->conn->prepare("UPDATE student_lesson_status
            SET status = :status
            WHERE person_details_id = :id
            AND lessons_id = :lesson_id");
        $stmt->executeQuery(['id' => $id, 'lesson_id' => $lessons_id, 'status' => $status]);
    }

    /**
     * Compte toute les leçons terminées par utilisateur et par formation
     *
     * @param integer $id_formation
     * @param integer $id_user
     * @return array|void
     */
    public function countCompletedLessonsByUser(int $id_formation, int $id_user)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(formations.id)
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            LEFT JOIN student_lesson_status
            ON lessons.id = student_lesson_status.lessons_id
            WHERE formations.id = :id_formation
            AND student_lesson_status.status = 2
            AND student_lesson_status.person_details_id = :id_user");
        $result = $stmt->executeQuery(['id_formation' => $id_formation, 'id_user' => $id_user]);
        $specs = $result->fetchOne();

        return $specs;
    }


    // /**
    //  * @return StudentLessonStatus[] Returns an array of StudentLessonStatus objects
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
    public function findOneBySomeField($value): ?StudentLessonStatus
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
