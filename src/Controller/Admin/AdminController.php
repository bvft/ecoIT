<?php
namespace App\Controller\Admin;

use App\Form\ActionsAdminType;
use App\Entity\InstructorDetails;
use Symfony\Component\String\ByteString;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\PersonLoginInfoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\InstructorDetailsRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;

#[Route("/admin")]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(private ManagerRegistry $manager)
    {
        
    }
    
    #[Route("/", methods: ["GET"], name: "admin_home")]
    /**
     * @return Response
     */
    public function index(PersonLoginInfoRepository $person_repo): Response
    {
        return $this->render('page/admin/index.html.twig', [
            'stats' => $person_repo->countUserByRoles()
        ]);
    }

    #[Route("/show-instructors", methods: ["GET"], name: "admin_show_instructors")]
    /**
     * @return Response
     */
    public function showInstructors(InstructorDetailsRepository $instructors): Response
    {
        return $this->render('page/admin/show_instructors.html.twig', [
            'instructors' => $instructors->findAll()
        ]);
    }

    #[Route("/show-instructor-{person}-{id}", methods: ["GET"], name: "admin_show_instructor", requirements: ['person' => '\d+', 'id' => '\d+'])]
    /**
     * @return Response
     */
    public function showInstructor(int $person, int $id, InstructorDetailsRepository $instructor): Response
    {
        $form = $this->createForm(ActionsAdminType::class, null);

        return $this->render('page/admin/show_instructor.html.twig', [
            'instructor' => $instructor->findBy(['id' => $id, 'person_details' => $person]),
            'form' => $form->createView()
        ]);
    }

    #[Route("/show-instructor-{person}-{id}", methods: ["POST"], name: "admin_edit_instructor", requirements: ['person' => '\d+', 'id' => '\d+'])]
    /**
     * @return Response
     */
    public function editInstructor(Request $request, int $person, int $id, InstructorDetails $instructor,
        Filesystem $filesystem): Response
    {
        $form = $this->createForm(ActionsAdminType::class, null);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $status = -1;

            if($form->get('waiting')->isClicked())
            {
                $status = null;
            }
            else if($form->get('refuse')->isClicked())
            {
                $status = 0;
            }
            else if($form->get('validate')->isClicked())
            {
                $status = 1;
            }
            

            if($status != -1)
            {
                // Pour le moment pseudo pour l'instructeur vaut le nom de dossier pour le contenu des formations
                if($status == 1 && $instructor->getPersonDetails()->getPseudo() === null)
                {
                    $number = ByteString::fromRandom(10)->toString();
                    // Comme on prend les id en BDD, il est inutile de vérifier si number est déjà utilisé
                    $number .= $instructor->getId() . $instructor->getPersonDetails()->getId();

                    $instructor->getPersonDetails()->setPseudo($number);

                    $dir = 'img/lessons/' . $number;

                    // Si le dossier existe pas
                    // On créer un dossier pour l'instructeur pour ses contenus de formation
                    if(!$filesystem->exists($dir))
                    {
                        $filesystem->mkdir($dir, 0755);
                    }
                }

                $instructor->setStatus($status);

                $em = $this->manager->getManager();
                $em->flush();

                $this->addFlash('success', 'Le formateur a été modifiée avec succès');
            }

            return $this->redirectToRoute('admin_edit_instructor', [
                'person' => $person,
                'id' => $id
            ]);
        }

        return $this->redirectToRoute('admin_show_instructors');
    }

    #[Route("/show-student", methods: ["GET"], name: "admin_show_student")]
    /**
     * @return Response
     */
    public function showStudent(PersonLoginInfoRepository $students): Response
    {
        return $this->render('page/admin/show_students.html.twig', [
            'students' => $students->countUserByRoleStudent()
        ]);
    }
}