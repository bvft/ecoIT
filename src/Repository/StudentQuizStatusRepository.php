<?php

namespace App\Repository;

use App\Entity\StudentQuizStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentQuizStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentQuizStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentQuizStatus[]    findAll()
 * @method StudentQuizStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentQuizStatusRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentQuizStatus::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(StudentQuizStatus $entity, bool $flush = true): void
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
    public function remove(StudentQuizStatus $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Permet de mettre à jour les réponses du quiz donnés par l'étudiant
     *
     * @param array $answers
     * @param integer $id
     * @param integer $sections_id
     * @return void
     */
    public function updateAnswers(array $answers, int $id, int $sections_id): void
    {
        $stmt = $this->conn->prepare("UPDATE student_quiz_status
            SET answers = :answers
            WHERE person_details_id = :id
            AND sections_id = :section_id");
        $stmt->executeQuery(['id' => $id, 'section_id' => $sections_id, 'answers' => json_encode($answers)]);
    }

    /**
     * Retourne tous les quizs terminés par utilisateur et par formation
     *
     * @param integer $id_formation
     * @param integer $id_user
     * @return array|void
     */
    public function countCompletedQuizsByUser(int $id_formation, int $id_user)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT(student_quiz_status.sections_id))
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN student_quiz_status
            ON sections.id = student_quiz_status.sections_id
            WHERE formations.id = :id_formation
            AND student_quiz_status.person_details_id = :id_user
            AND student_quiz_status.sections_id IS NOT NULL");
        $result = $stmt->executeQuery(['id_formation' => $id_formation, 'id_user' => $id_user]);
        $specs = $result->fetchOne();

        return $specs;
    }

    // /**
    //  * @return StudentQuizStatus[] Returns an array of StudentQuizStatus objects
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
    public function findOneBySomeField($value): ?StudentQuizStatus
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
