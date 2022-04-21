<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Quiz|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quiz|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quiz[]    findAll()
 * @method Quiz[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuizRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Quiz $entity, bool $flush = true): void
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
    public function remove(Quiz $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne les quiz par sections de formation pour les étudiants
     *
     * @param integer $id
     * @return array|void
     */
    public function findQuizsBySectionsForStudent(int $id)
    {
        $q = [];

        foreach($this->conn->iterateAssociativeIndexed(
            "SELECT number, quiz.title, quiz.id
            FROM sections
            LEFT JOIN quiz
            ON sections.id = quiz.sections_id
            WHERE formations_id = :id", ['id' => $id]) as $k => $v)
        {
            // Le quizs est un ensemble de question.
            // ici un seul titre nous intéresse, inutile de tous les récupèrer
            if(!array_key_exists($k, $q))
            {
                $q[$k][] = $v;
            }
        }

        return $q;
    }

     /**
     * Retourne le statut des quizs pour les étudiants
     *
     * @param integer $id
     * @param integer $student_id
     * @return array|void
     */
    public function findQuizsStatusForStudent(int $id, int $student_id)
    {
        $qs = [];

        foreach($this->conn->iterateAssociativeIndexed(
            "SELECT number, quiz.id
            FROM sections
            LEFT JOIN quiz
            ON sections.id = quiz.sections_id
            LEFT JOIN student_quiz_status
            ON sections.id = student_quiz_status.sections_id
            WHERE formations_id = :id
            AND person_details_id = :student_id", ['id' => $id, 'student_id' => $student_id]) as $k => $v)
        {
            $qs[$k][] = $v;
        }

        return $qs;
    }

    /**
     * Obtient tous les quizs par formation
     *
     * @param integer $id
     * @return array|void
     */
    public function findAllQuizs(int $id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT(quiz.sections_id))
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN quiz
            ON sections.id = quiz.sections_id
            WHERE formations.id = :id
            AND quiz.sections_id IS NOT NULL");
        $result = $stmt->executeQuery(['id' => $id]);
        $specs = $result->fetchOne();

        return $specs;
    }

    // /**
    //  * @return Quiz[] Returns an array of Quiz objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
