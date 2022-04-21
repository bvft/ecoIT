<?php
namespace App\Controller;

use App\Form\NewStudentType;
use App\Entity\PersonDetails;
use App\Entity\PersonLoginInfo;
use App\Repository\PersonDetailsRepository;
use App\Repository\PersonLoginInfoRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PreRegistrationStudentController extends AbstractController
{
    public function __construct(private ManagerRegistry $manager)
    {
        
    }
    
    #[Route("/pre-registration-student", methods: ["GET"], name: "pre_registration_student")]
    /**
     * @return Response
     */
    public function index(): Response
    {
        $form = $this->createForm(NewStudentType::class, null);

        return $this->render('page/pre_registration_student.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route("/pre-registration-student", methods: ["POST"], name: "new_pre_registration_student")]
    /**
     * @return Response
     */
    public function new(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $UPH,
        PersonLoginInfoRepository $student_repo, PersonDetailsRepository $student_details_repo): Response
    {
        $student = new PersonLoginInfo();

        $form = $this->createForm(NewStudentType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            if(is_object($datas['email']))
            {
                $datas['email'] = '';
            }

            if(is_object($datas['password']))
            {
                $datas['password'] = '';
            }

            $student->setEmail($datas['email']);
            $student->setPassword($datas['password']);

            // Comme le pseudo est mapped=fasle, le vérifier à part
            $errors = $validator->validate([$student, $form->get("pseudo")]);
            
            $existing = false;

            // On vérifie si l'email est unique
            $existing_email = $student_repo->findOneBy(['email' => $student->getEmail()]);
            
            if($existing_email !== null)
            {
                $existing = true;
                $form->get('email')->addError(new FormError('Cette adresse e-mail est déjà utilisé.'));
            }

            // On vérifie si le pseudo existe déjà
            $existing_pseudo = $student_details_repo->findOneBy(['pseudo' => $form->get("pseudo")->getData()]);

            if($existing_pseudo !== null)
            {
                $existing = true;
                $form->get('pseudo')->addError(new FormError('Ce pseudo est déjà utilisé.'));
            }

            if(count($errors) || $existing === true)
            {
                return $this->render('page/pre_registration_student.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $student->setRoles(["ROLE_STUDENT"]);
                $student->setPassword($UPH->hashPassword($student, $student->getPassword()));

                $student_details = new PersonDetails();
                $student_details->setPseudo($form->get("pseudo")->getData());
                $student_details->setPersonLoginInfo($student);

                $em->persist($student);
                $em->persist($student_details);
                
                $em->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès');

                return $this->redirectToRoute('pre_registration_student');
            }
        }
    }
}