<?php
namespace App\Controller;

use App\Entity\PersonDetails;
use App\Entity\PersonLoginInfo;
use App\Form\NewInstructorType;
use App\Entity\InstructorDetails;
use Symfony\Component\Form\FormError;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\PersonLoginInfoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PreRegistrationInstructorController extends AbstractController
{
    public function __construct(private ManagerRegistry $manager)
    {
        
    }
    
    #[Route("/pre-registration-instructor", methods: ["GET"], name: "pre_registration_instructor")]
    /**
     * @return Response
     */
    public function index(): Response
    {
        $form = $this->createForm(NewInstructorType::class, null);

        return $this->render('page/pre_registration_instructor.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route("/pre-registration-instructor", methods: ["POST"], name: "new_pre_registration_instructor")]
    /**
     * @return Response
     */
    public function new(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $UPH,
        PersonLoginInfoRepository $instructor_repo, SluggerInterface $slugger): Response
    {
        $instructor = new PersonLoginInfo();
        $instructor_details = new PersonDetails();
        $instructor_infos = new InstructorDetails();

        $form = $this->createForm(NewInstructorType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            if(is_object($datas['first_name']))
            {
                $datas['first_name'] = '';
            }

            if(is_object($datas['name']))
            {
                $datas['name'] = '';
            }

            if(is_object($datas['email']))
            {
                $datas['email'] = '';
            }

            if(is_object($datas['password']))
            {
                $datas['password'] = '';
            }

            if(is_object($datas['desc_specs']))
            {
                $datas['desc_specs'] = '';
            }

            $instructor_details->setFirstName($datas['first_name']);
            $instructor_details->setName($datas['name']);
            // NOTE : Si un champ à Notblank et qu'il peut être nullable en BDD
            // la validation ne passe pas.
            // https://github.com/symfony/symfony/issues/27876
            // UNE SOLUTION : définir une valeur bidon, puis redefinir à null après le validator
            $instructor_details->setPseudo('PassValidation');
            $instructor->setEmail($datas['email']);
            $instructor->setPassword($datas['password']);
            $instructor_infos->setDescSpecs($datas['desc_specs']);

            $errors = $validator->validate([
                $instructor_details,
                $instructor,
                $instructor_infos,
                $form->get("picture")
            ]);

            $existing = false;

            // On vérifie si l'email est unique
            $existing_email = $instructor_repo->findOneBy(['email' => $instructor->getEmail()]);
            
            if($existing_email !== null)
            {
                $existing = true;
                $form->get('email')->addError(new FormError('Cette adresse e-mail est déjà utilisé.'));
            }

            // Si on ne choisit pas de photo de profil. Le NotBlank ne fctne pas.
            if($form->get("picture")->getData() === null)
            {
                $existing = true;
                $form->get('picture')->addError(new FormError('Veuillez choisir une photo de profil'));
            }

            if(count($errors) || $existing === true)
            {
                return $this->render('page/pre_registration_instructor.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }

            if($form->isValid())
            {
                $picture = $form->get("picture")->getData();

                if($picture)
                {
                    $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$picture->guessExtension();

                    try {
                        $picture->move(
                            $this->getParameter('instructor_picture'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // Par exemple problème de droit d'accès au fichier
                        $this->addFlash('error_intern', 'Une erreur est survenue lors de la validation de votre compte.');

                        return $this->redirectToRoute('pre_registration_instructor');
                    }

                    $instructor_infos->setPicture($newFilename);
                }

                $em = $this->manager->getManager();

                $instructor->setRoles(["ROLE_INSTRUCTOR"]);
                $instructor->setPassword($UPH->hashPassword($instructor, $instructor->getPassword()));

                $instructor_details->setPseudo(null);
                $instructor_details->setPersonLoginInfo($instructor);

                $instructor_infos->setPersonDetails($instructor_details);

                $em->persist($instructor);
                $em->persist($instructor_details);
                $em->persist($instructor_infos);
                
                $em->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès');

                return $this->redirectToRoute('pre_registration_instructor');
            }
        }
    }
}