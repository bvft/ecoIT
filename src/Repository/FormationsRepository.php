<?php

namespace App\Repository;

use App\Entity\Formations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method Formations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Formations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Formations[]    findAll()
 * @method Formations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationsRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, Formations::class);
        $this->conn = $this->getEntityManager()->getConnection();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Formations $entity, bool $flush = true): void
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
    public function remove(Formations $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne toutes les formations
     *
     * @return Object
     */
    public function findAlls()
    {
        return $this->createQueryBuilder('f');
    }

    /**
     * Retourne toutes les formations par instructeurs
     *
     * @param array $data
     * @return array|void
     */
    public function findFormationsByUser(array $data)
    {
        if(array_key_exists('data', $data))
        {
            $user_id = $data['data'][0];

            $stmt = $this->conn->prepare("SELECT title, id
                FROM formations
                WHERE person_details_id = :id");
            $result = $stmt->executeQuery(['id' => $user_id]);
            $specs = $result->fetchAllKeyValue();

            return $specs;
        }
    }

    /**
     * Retourne les 3 dernières formations rangées par date de création
     *
     * @return void
     */
    public function findLastFormationsByPublicationDate()
    {
        $stmt = $this->conn->prepare("SELECT title, picture, short_text, number
            FROM formations
            ORDER BY create_at DESC
            LIMIT 3");
        $result = $stmt->executeQuery();
        $specs = $result->fetchAllAssociative();

        return $specs;
    }

    /**
     * Retourne toutes les formations par rubrics
     *
     * @return array|void
     */
    public function findAllFormationsByRubrics()
    {
        $f = [];

        // Puisque les fetchAll... ne me retourne pas ce que je souhaite
        // J'utilise une toute autre technique qui me convient
        foreach($this->conn->iterateAssociativeIndexed(
            "SELECT name, title, picture, short_text, number, formations.id
            FROM rubrics
            RIGHT JOIN formations
            ON rubrics.id = formations.rubrics_id"
        ) as $k => $v)
        {
            $f[$k][] = $v;
        }

        return $f;
    }

    /**
     * Compte toutes les leçons par formation
     *
     * @return array|void
     */
    public function countAllLessons()
    {
        $stmt = $this->conn->prepare("SELECT formations.id, COUNT(lessons.id) AS total
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            WHERE lessons.sections_id IS NOT NULL
            GROUP BY formations.id");
        $result = $stmt->executeQuery();
        $specs = $result->fetchAllKeyValue();

        return $specs;
    }

    /**
     * Compte toutes les quizs par formation
     *
     * @return array|void
     */
    public function countAllQuizs()
    {
        $stmt = $this->conn->prepare("SELECT formations.id, COUNT(DISTINCT(quiz.sections_id)) AS total
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN quiz
            ON sections.id = quiz.sections_id
            WHERE quiz.sections_id IS NOT NULL
            GROUP BY formations.id");
        $result = $stmt->executeQuery();
        $specs = $result->fetchAllKeyValue();

        return $specs;
    }

    /**
     * Compte toutes les quizs complétés par les étudiants par formation
     * 
     * @param int $student_id
     * @return array|void
     */
    public function countAllQuizsStatusByStudent(int $student_id)
    {
        $stmt = $this->conn->prepare("SELECT formations.id, COUNT(DISTINCT(student_quiz_status.sections_id)) AS total
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN student_quiz_status
            ON sections.id = student_quiz_status.sections_id
            WHERE student_quiz_status.person_details_id = :student_id
            AND student_quiz_status.sections_id IS NOT NULL
            GROUP BY formations.id");
        $result = $stmt->executeQuery(['student_id' => $student_id]);
        $specs = $result->fetchAllKeyValue();

        return $specs;
    }

    /**
     * Compte toutes les leçons complétés par les étudiants par formation
     * 
     * @param int $student_id
     * @return array|void
     */
    public function countAllLessonsStatusByStudent(int $student_id)
    {
        $stmt = $this->conn->prepare("SELECT formations.id, COUNT(student_lesson_status.id) AS total
            FROM formations
            LEFT JOIN sections
            ON formations.id = sections.formations_id
            LEFT JOIN lessons
            ON sections.id = lessons.sections_id
            LEFT JOIN student_lesson_status
            ON lessons.id = student_lesson_status.lessons_id
            WHERE student_lesson_status.status = 2
            AND student_lesson_status.person_details_id = :student_id
            GROUP BY formations.id");
        $result = $stmt->executeQuery(['student_id' => $student_id]);
        $specs = $result->fetchAllKeyValue();

        return $specs;
    }

    /**
     * Retourne toutes les formations par instructeurs
     *
     * @param array $data
     * @return Object
     */
    /*public function findFormationsByUser(array $data)
    {
        if(\array_key_exists('data', $data)){
        $user_id = $data['data'][0];

        return $this->createQueryBuilder('f')
            ->where('f.person_details = :id')
            ->setParameter('id', $user_id);
        }
    }*/

    // /**
    //  * @return Formations[] Returns an array of Formations objects
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
    public function findOneBySomeField($value): ?Formations
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
