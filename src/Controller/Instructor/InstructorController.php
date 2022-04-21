<?php
namespace App\Controller\Instructor;

use App\Entity\Quiz;
use App\Entity\Lessons;
use App\Entity\Sections;
use App\Entity\Formations;
use App\Form\NewCourseType;
use App\Form\NewSectionType;
use App\Form\NewFormationType;
use App\Form\EditFormationType;
use App\Form\NewQuizSectionType;
use App\Entity\InstructorDetails;
use App\Form\EditQuizSectionType;
use App\Form\NewCourseSectionType;
use App\Repository\QuizRepository;
use App\Form\EditCourseSectionType;
use App\Form\NewSectionFormationType;
use App\Repository\LessonsRepository;
use Symfony\Component\Form\FormError;
use App\Form\EditSectionFormationType;
use App\Repository\SectionsRepository;
use App\Repository\FormationsRepository;
use Symfony\Component\String\ByteString;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * On aurait voulu ne pas obliger le formateur à "retélécharger" une image, mais sur une valeur null
 * symfony sort une violation même si l'on définit une valeur pour le "value" de l'input ou "empty_data"
 * dans le FormType.
 * 2 solutions peuvent être mise en place :
 *  - "Forcer" le formateur a retélécharger l'image (option retenu dans le code)
 *  - Créer 2 form, 1 pour le titre, courte description etc..., et un 2eme pour l'upload de l'image
 */
#[Route("/instructor")]
#[IsGranted('ROLE_INSTRUCTOR')]
class InstructorController extends AbstractController
{
    public function __construct(private ManagerRegistry $manager, private Security $security)
    {
        
    }
    
    #[Route("/", methods: ["GET"], name: "instructor_home")]
    /**
     * @return Response
     */
    public function index(FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        return $this->render('page/instructor/index.html.twig', [
            'formations' => $formations->findBy(['person_details' => $user_id])
        ]);
    }

    #[Route("/create", methods: ["GET"], name: "instructor_new_formation")]
    /**
     * @return Response
     */
    public function new(): Response
    {
        $form = $this->createForm(NewFormationType::class, null);

        return $this->render('page/instructor/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route("/create", methods: ["POST"], name: "instructor_create_formation")]
    /**
     * @return Response
     */
    public function create(Request $request, ValidatorInterface $validator, SluggerInterface $slugger,
        FormationsRepository $f): Response
    {
        $formation = new Formations();

        $form = $this->createForm(NewFormationType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            if(is_object($datas['title']))
            {
                $datas['title'] = '';
            }

            if(is_object($datas['short_text']))
            {
                $datas['short_text'] = '';
            }

            $formation->setTitle($datas['title']);
            $formation->setShortText($datas['short_text']);

            $errors = $validator->validate([
                $formation,
                $form->get("picture")
            ]);
            
            $existing = false;

            // Si on ne choisit pas de photo de profil. Le NotBlank ne fctne pas.
            if($form->get("picture")->getData() === null)
            {
                $existing = true;
                $form->get('picture')->addError(new FormError('Veuillez choisir une image à votre formation'));
            }

            if(!array_key_exists('rubrics', $datas))
            {
                $existing = true;
                $form->get('rubrics')->addError(new FormError('Veuillez choisir une rubrique valide'));
            }

            if(count($errors) || $existing === true)
            {
                return $this->render('page/instructor/create.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }

            // Génère un nombre aléatoire
            $num = ByteString::fromRandom(10)->toString();

            $number = $this->unicity($num, $f);

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
                            $this->getParameter('formations_picture'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // Par exemple problème de droit d'accès au fichier
                        $this->addFlash('error_intern', 'Une erreur est survenue lors de la création de la formation.');

                        return $this->redirectToRoute('instructor_create_formation');
                    }

                    $formation->setPicture($newFilename);
                }

                
                $em = $this->manager->getManager();

                $formation->setCreateAt(new \DateTime());
                $formation->setNumber($number);
                $formation->setStatus(0);
                $formation->setRubrics($datas['rubrics']);

                /** @var  InstructorDetails $user */
                $user = $this->security->getUser();

                /** @var  InstructorDetails $user */
                $u = $user->getPersonDetails();

                $formation->setPersonDetails($u);

                $em->persist($formation);
                
                $em->flush();

                $this->addFlash('success', 'La formation a été créé avec succès');

                return $this->redirectToRoute('instructor_create_formation');
            }
        }
    }

    #[Route("/edit/{number}", methods: ["GET"], name: "instructor_edit_formation")]
    /**
     * @return Response
     */
    public function edit(Formations $formation, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $formation]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditFormationType::class, $formation);

        return $this->render('page/instructor/edit.html.twig', [
            'form' => $form->createView(),
            'formation' => $formation
        ]);
    }

    #[Route("/edit/{number}", methods: ["POST"], name: "instructor_update_formation")]
    /**
     * @return Response
     */
    public function update(Request $request, Formations $formation, ValidatorInterface $validator,
        Filesystem $fileSystem, SluggerInterface $slugger, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $formation]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }
        // Il est impérative de passer null en deuxième argument car symfony renvoie toujours une
        // erreur de type violation que le fichier ne peut être trouvé.
        // Symfony renvoi une violation mais dans la validator tout est OK.
        // Il ne pass jamais $form->isvalid() ET il faut refaire toutes les vérif.
        // Je n'ai pas réussi à trouver une solution plus appropriée.
        $form = $this->createForm(EditFormationType::class, null);

        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            $p =  $_POST['edit_formation'];

            $formation->setTitle($p['title']);
            $formation->setShortText($p['short_text']);

            $existing = false;

            // Si on ne choisit pas de photo de profil. Le NotBlank ne fctne pas.
            if($form->get("picture")->getData() === null)
            {
                $existing = true;
                $form->get('picture')->addError(new FormError('Veuillez choisir une image à votre formation'));
            }

            $rubric = $form->get("rubrics")->getData();

            if($rubric === null)
            {
                $existing = true;
                $form->get('rubrics')->addError(new FormError('Veuillez choisir une rubrique valide'));
            }

            $title = $form->get("title")->getData();

            if($title === null || mb_strlen($title) < 5 || mb_strlen($title) > 255)
            {
                $existing = true;
                $form->get('title')->addError(new FormError('Le titre entre 5 et 255 caractères'));
            }

            $desc = $form->get("short_text")->getData();

            if($desc === null || mb_strlen($desc) < 15 || mb_strlen($desc) > 255)
            {
                $existing = true;
                $form->get('short_text')->addError(new FormError('La courte description entre 15 et 255 caractères'));
            }

            
            $errors = $validator->validate([
                $form->get("title"),
                $form->get("short_text"),
                $form->get("picture"),
                $form->get("rubrics"),
            ]);

            if(count($errors) || $existing === true)
            {
                return $this->render('page/instructor/create.html.twig', [
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
                        $dir = $this->getParameter('formations_picture');
                        
                        $picture->move(
                            $dir,
                            $newFilename
                        );

                        // On supprime l'ancien fichier
                        $fileSystem->remove($dir . '/' . $formation->getPicture());
                    } catch (FileException | IOException $e) {
                        // Par exemple problème de droit d'accès au fichier
                        $this->addFlash('error_intern', 'Une erreur est survenue lors de l\'édition de la formation.');

                        return $this->redirectToRoute('instructor_edit_formation', [
                            'number' => $formation->getNumber()
                        ]);
                    }

                    $formation->setPicture($newFilename);
                }

                $formation->setRubrics($rubric);

                $em = $this->manager->getManager();

                $em->flush();

                $this->addFlash('success', 'La formation a été modifiée avec succès');

                return $this->redirectToRoute('instructor_edit_formation', [
                    'number' => $formation->getNumber()
                ]);
            }
        }

        return $this->render('page/instructor/edit.html.twig', [
            'form' => $form->createView(),
            'formation' => $formation
        ]);
    }

    #[Route("/sections", methods: ["GET"], name: "instructor_sections")]
    /**
     * @return Response
     */
    public function sections(FormationsRepository $formations, SectionsRepository $sections): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $user_formations = $formations->findBy(['person_details' => $user_id]);

        return $this->render('page/instructor/sections.html.twig', [
            'formations' => $user_formations,
            'sections' => $sections->findBy(['formations' => $user_formations])
        ]);
    }

    #[Route("/create-section", methods: ["GET"], name: "instructor_new_section")]
    /**
     * @return Response
     */
    public function newSection(FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $form = $this->createForm(NewSectionType::class, [$user_id]);

        return $this->render('page/instructor/create_section.html.twig', [
            'formations' => $formations->findBy(['person_details' => $user_id]),
            'form' => $form->createView()
        ]);
    }

    #[Route("/create-section", methods: ["POST"], name: "instructor_create_section")]
    /**
     * @return Response
     */
    public function createSection(Request $request, FormationsRepository $f): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $form = $this->createForm(NewSectionType::class, [$user_id]);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            if(empty($datas) || !array_key_exists('rubrics', $datas))
            {
                $this->addFlash('error_intern', 'Veuillez choisir une formation valide!');

                return $this->redirectToRoute('instructor_new_section');
            }

            if($form->isValid())
            {
                $formation = $f->findOneBy(['id' => $datas['rubrics']]);
                $number = $formation->getNumber();

                return $this->redirectToRoute('instructor_new_section_formation', [
                    'number' => $number
                ]);
            }
        }

        return $this->render('page/instructor/create_section.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route("/create-section/{number}", methods: ["GET"], name: "instructor_new_section_formation")]
    /**
     * @return Response
     */
    public function newSectionFormation(Formations $f, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(NewSectionFormationType::class, null);

        return $this->render('page/instructor/create_section_formation.html.twig', [
            'form' => $form->createView(),
            'formation' => $f
        ]);
    }

    #[Route("/create-section/{number}", methods: ["POST"], name: "instructor_create_section_formation")]
    /**
     * @return Response
     */
    public function createSectionFormation(Request $request, Formations $f, ValidatorInterface $validator,
        SectionsRepository $s, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $section = new Sections();
        
        $form = $this->createForm(NewSectionFormationType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            if(is_object($datas['title']))
            {
                $datas['title'] = '';
            }

            $section->setTitle($datas['title']);

            $errors = $validator->validate([
                $section,
            ]);

            if(count($errors))
            {
                return $this->render('page/instructor/create_section_formation.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }

            // Génère un nombre aléatoire
            $num = ByteString::fromRandom(10)->toString();

            $number = $this->unicity($num, $s);

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $last_rank = $s->findMaxRankBySection($f->getId())[0]['last_rank'];

                $rank = $last_rank + 1;
                
                $section->setRankOrder($rank);
                $section->setFormations($f);
                $section->setNumber($number);

                $em->persist($section);
                
                $em->flush();

                $this->addFlash('success', 'La section a été créé avec succès');

                return $this->redirectToRoute('instructor_new_section_formation', [
                    'number' => $f->getNumber()
                ]);
            }
        }

        return $this->render('page/instructor/create_section_formation.html.twig', [
            'form' => $form->createView(),
            'formation' => $f
        ]);
    }

    #[
        Route("/edit-section/{number_f}/{number_s}", methods: ["GET"], name: "instructor_edit_section_formation"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function editSectionFormation(Formations $f, Sections $s, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditSectionFormationType::class, $s);

        return $this->render('page/instructor/edit_section_formation.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s
        ]);
    }

    #[
        Route("/edit-section/{number_f}/{number_s}", methods: ["POST"], name: "instructor_update_section_formation"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function updateSectionFormation(Request $request, Formations $f, Sections $s, ValidatorInterface $validator,
        FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditSectionFormationType::class, $s);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            $s->setTitle($_POST['edit_section_formation']['title']);

            $errors = $validator->validate([
                $s,
            ]);

            if(count($errors))
            {
                return $this->render('page/instructor/edit_section_formation.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors,
                    'formation' => $f,
                    'section' => $s
                ]);
            }

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $em->persist($s);
                
                $em->flush();

                $this->addFlash('success', 'La section a été modifiée avec succès');

                return $this->redirectToRoute('instructor_edit_section_formation', [
                    'number_f' => $f->getNumber(),
                    'number_s' => $s->getNumber(),
                ]);
            }
        }

        return $this->render('page/instructor/edit_section_formation.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s
        ]);
    }

    #[Route("/courses", methods: ["GET"], name: "instructor_courses")]
    /**
     * @return Response
     */
    public function courses(FormationsRepository $f, SectionsRepository $s, LessonsRepository $l): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $user_formations = $f->findBy(['person_details' => $user_id]);

        $user_sections = $s->findAllSectionsByUser($user_id);

        return $this->render('page/instructor/courses.html.twig', [
            'formations' => $user_formations,
            'sections' => $s->findBy(['formations' => $user_formations]),
            'lessons' => $l->findBy(['sections' => array_keys($user_sections)])
        ]);
    }

    #[Route("/create-course", methods: ["GET"], name: "instructor_new_course")]
    /**
     * @return Response
     */
    public function newCourse(FormationsRepository $f, SectionsRepository $s): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $user_formations = $f->findBy(['person_details' => $user_id]);
        $user_sections = $s->findBy(['formations' => $user_formations]);

        $form = $this->createForm(NewCourseType::class, [$user_id, $f, $s]);

        return $this->render('page/instructor/create_course.html.twig', [
            'formations' => $user_formations,
            'sections' => $user_sections,
            'form' => $form->createView()
        ]);
    }

    #[Route("/create-course", methods: ["POST"], name: "instructor_create_course")]
    /**
     * @return Response
     */
    public function createCourse(Request $request, FormationsRepository $f, SectionsRepository $s): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $user_formations = $f->findBy(['person_details' => $user_id]);
        $user_sections = $s->findBy(['formations' => $user_formations]);

        $form = $this->createForm(NewCourseType::class, [$user_id, $f, $s, $user_sections]);

        // Puisque Symfony me place les Sections après le bouton Submit, on le supprime
        $form->remove('send');

        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            $formation = $form->get('formations')->getData();
            $section = $form->get('sections')->getData();

            if($formation === null)
            {
                $this->addFlash('error_intern', 'Veuillez choisir une formation valide!');

                return $this->redirectToRoute('instructor_new_course');
            }

            if(null !== $p = $request->get('new_course'))
            {
                if(array_key_exists('sections', $p))
                {
                    if($section === null)
                    {
                        $this->addFlash('error_intern', 'Veuillez choisir une section valide!');

                        return $this->redirectToRoute('instructor_new_course');
                    }
                    else
                    {
                        // On récupère le numéro de la formation et de la section
                        $current_f = $f->findBy(['person_details' => $user_id, 'id' => $formation]);
                        $current_s = $s->findBy(['formations' => $formation, 'id' => $section]);

                        // Si on veut s'assurer de plus de sérénnité et de sécurité
                        // On pourrait vérifier si le number dans $current_f est identique dans $current_s

                        $number_s = $current_s[0]->getNumber();
                        $number_f = $current_s[0]->getFormations()->getNumber();

                        // Si on click sur le bouton créer un quiz
                        if($form->get('send_quiz')->isClicked())
                        {
                            return $this->redirectToRoute('instructor_new_quiz_section', [
                                'number_f' => $number_f,
                                'number_s' => $number_s,
                            ]);
                        }

                        return $this->redirectToRoute('instructor_new_course_section', [
                            'number_f' => $number_f,
                            'number_s' => $number_s,
                        ]);
                    }
                }
            }
            else
            {
                $this->addFlash('error_intern', 'Veuillez choisir correctement un élément valide!');

                return $this->redirectToRoute('instructor_new_course');
            }
        }

        return $this->render('page/instructor/create_course.html.twig', [
            'formations' => $user_formations,
            'sections' => $user_sections,
            'form' => $form->createView()
        ]);
    }

    #[
        Route("/create-course/{number_f}/{number_s}", methods: ["GET"], name: "instructor_new_course_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function newCourseSection(Formations $f, Sections $s, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(NewCourseSectionType::class, $user);

        return $this->render('page/instructor/create_course_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s
        ]);
    }

    #[
        Route("/create-course/{number_f}/{number_s}", methods: ["POST"], name: "instructor_create_course_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function createCourseSection(Request $request, Formations $f, Sections $s, FormationsRepository $formations,
        ValidatorInterface $validator, LessonsRepository $l): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $lesson = new Lessons();

        $form = $this->createForm(NewCourseSectionType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            $p = $request->get('new_course_section');

            if($p)
            {
                if(array_key_exists('title', $p))
                {
                    $lesson->setTitle($p['title']);
                }

                if(array_key_exists('content', $p))
                {
                    $lesson->setContent($p['content']);
                }
            }

            $errors = $validator->validate([
                $lesson,
            ]);

            if(count($errors))
            {
                return $this->render('page/instructor/create_course_section.html.twig', [
                    'form' => $form->createView(),
                    'formation' => $f,
                    'section' => $s,
                    'errors' => $errors
                ]);
            }

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $last_rank = $l->findMaxRankCourseBySection($s->getId())[0]['last_rank'];

                $rank = $last_rank + 1;
                
                $lesson->setRankOrder($rank);
                $lesson->setSections($s);
               
                $em->persist($lesson);
                
                $em->flush();

                $this->addFlash('success', 'Le cours a été créé avec succès');

                return $this->redirectToRoute('instructor_new_course_section', [
                    'number_f' => $f->getNumber(),
                    'number_s' => $s->getNumber(),
                ]);
            }
        }

        return $this->render('page/instructor/create_course_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s
        ]);
    }

    #[
        Route("/edit-course/{number_f}/{number_s}/{id}", methods: ["GET"], name: "instructor_edit_course_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function editCourseSection(Formations $f, Sections $s, Lessons $l, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditCourseSectionType::class, [$l, $user]);

        return $this->render('page/instructor/edit_course_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s,
            'lesson' => $l
        ]);
    }

    #[
        Route("/edit-course/{number_f}/{number_s}/{id}", methods: ["POST"], name: "instructor_update_course_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function updateCourseSection(Request $request, Formations $f, Sections $s, Lessons $l,
        ValidatorInterface $validator, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditCourseSectionType::class, [$l, $user]);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            $p = $request->get('edit_course_section');

            if($p)
            {
                if(array_key_exists('title', $p))
                {
                    $l->setTitle($p['title']);
                }

                if(array_key_exists('content', $p))
                {
                    $l->setContent($p['content']);
                }
            }

            $errors = $validator->validate([
                $l,
            ]);

            if(count($errors))
            {
                return $this->render('page/instructor/edit_course_section.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors,
                    'formation' => $f,
                    'section' => $s,
                    'lesson' => $l
                ]);
            }

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $em->persist($l);
                
                $em->flush();

                $this->addFlash('success', 'Le cours a été modifiée avec succès');

                return $this->redirectToRoute('instructor_edit_course_section', [
                    'number_f' => $f->getNumber(),
                    'number_s' => $s->getNumber(),
                    'id' => $l->getId()
                ]);
            }
        }

        return $this->render('page/instructor/edit_course_section.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
            'formation' => $f,
            'section' => $s,
            'lesson' => $l
        ]);
    }

    #[Route("/quizs", methods: ["GET"], name: "instructor_quizs")]
    /**
     * @return Response
     */
    public function quizs(FormationsRepository $f, SectionsRepository $s, QuizRepository $q): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        $user_formations = $f->findBy(['person_details' => $user_id]);

        $user_sections = $s->findAllSectionsByUser($user_id);

        return $this->render('page/instructor/quizs.html.twig', [
            'formations' => $user_formations,
            'sections' => $s->findBy(['formations' => $user_formations]),
            'quizs' => $q->findBy(['sections' => array_keys($user_sections)])
        ]);
    }

    #[
        Route("/create-quiz/{number_f}/{number_s}", methods: ["GET"], name: "instructor_new_quiz_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function newQuizSection(Formations $f, Sections $s, FormationsRepository $formations, LessonsRepository $lessons,
        QuizRepository $quizs): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }
        
        $form = $this->createForm(NewQuizSectionType::class, null);

        return $this->render('page/instructor/create_quiz_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s,
            'count_lesson' => $lessons->findBy(['sections' => $s->getId()]),
            'title_quiz' => $quizs->findOneBy(['sections' => $s->getId()])
        ]);
    }

    #[
        Route("/create-quiz/{number_f}/{number_s}", methods: ["POST"], name: "instructor_create_quiz_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function createQuizSection(Formations $f, Sections $s, FormationsRepository $formations, LessonsRepository $lessons,
        QuizRepository $quizs, Request $request, ValidatorInterface $validator): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $quiz = new Quiz();
        
        $form = $this->createForm(NewQuizSectionType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            $p = $request->get('new_quiz_section');
            $existing = false;
            $count = 0;

            if($p)
            {
                if(array_key_exists('title', $p))
                {
                    $quiz->setTitle($p['title']);
                }

                if(array_key_exists('question', $p))
                {
                    $quiz->setQuestion($p['question']);
                }

                if(array_key_exists('answers', $p))
                {
                    $count = mb_substr_count($p['answers'], ';');
                    
                    if(!preg_match('#^([a-zA-Z0-9-_ ]{1,}:[a-z0-9]{1,};){1,}$#', $p['answers']))
                    {
                        $existing = true;
                        $form->get('answers')->addError(new FormError('Veuillez respecter le format demandé.'));
                    }
                }

                if(array_key_exists('solution', $p))
                {
                    if($p['solution'] < 1 || $p['solution'] > $count)
                    {
                        $existing = true;
                        $form->get('solution')->addError(new FormError('Le numéro de la solution proposé est incorrect.'));
                    }
                }
            }

            $errors = $validator->validate([
                $quiz
            ]);

            if(count($errors) || $existing === true)
            {
                return $this->render('page/instructor/create_quiz_section.html.twig', [
                    'form' => $form->createView(),
                    'formation' => $f,
                    'section' => $s,
                    'count_lesson' => $lessons->findBy(['sections' => $s->getId()]),
                    'title_quiz' => $quizs->findOneBy(['sections' => $s->getId()]),
                    'errors' => $errors
                ]);
            }

            if($form->isValid())
            {
                $quiz->setAnswers([$p['answers']]);
                $quiz->setSolution($p['solution']);
                $quiz->setSections($s);

                $em = $this->manager->getManager();

                $em->persist($quiz);
                
                $em->flush();

                $this->addFlash('success', 'Le quiz a été créé avec succès');

                return $this->redirectToRoute('instructor_new_quiz_section', [
                    'number_f' => $f->getNumber(),
                    'number_s' => $s->getNumber(),
                ]);
            }
        }

        return $this->render('page/instructor/create_quiz_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s,
            'count_lesson' => $lessons->findBy(['sections' => $s->getId()]),
            'title_quiz' => $quizs->findOneBy(['sections' => $s->getId()])
        ]);
    }

    #[
        Route("/edit-quiz/{number_f}/{number_s}/{id}", methods: ["GET"], name: "instructor_edit_quiz_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function editQuizSection(Formations $f, Sections $s, Quiz $q, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditQuizSectionType::class, null);

        return $this->render('page/instructor/edit_quiz_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s,
            'quiz' => $q
        ]);
    }

    #[
        Route("/edit-quiz/{number_f}/{number_s}/{id}", methods: ["POST"], name: "instructor_update_quiz_section"),
        ParamConverter('f', options: ['mapping' => ['number_f' => 'number']]),
        ParamConverter('s', options: ['mapping' => ['number_s' => 'number']]),
    ]
    /**
     * @return Response
     */
    public function updateQuizSection(Request $request, Formations $f, Sections $s, Quiz $q,
        ValidatorInterface $validator, FormationsRepository $formations): Response
    {
        /** @var Security $user */
        $user = $this->security->getUser();
        /** @var InstructorDetails $user */
        $user_id = $user->getPersonDetails()->getId();

        // On vérifie si la formation correspond bien à son formateur
        $user_formations = $formations->findBy(['person_details' => $user_id, 'id' => $f]);

        if($user_formations === null || empty($user_formations))
        {
            return $this->redirectToRoute('instructor_home');
        }

        $form = $this->createForm(EditQuizSectionType::class, null);

        $form->handleRequest($request);

        $datas = $form->getData();

        if($form->isSubmitted())
        {
            $p = $request->get('edit_quiz_section');

            $existing = false;
            $count = 0;

            if($p)
            {
                if(array_key_exists('title', $p))
                {
                    if(mb_strlen($p['title']) < 5 || mb_strlen($p['title']) > 255)
                    {
                        $existing = true;
                        $form->get('title')->addError(new FormError('Le titre entre 5 et 255 caractères.'));
                    }
                }

                if(array_key_exists('question', $p))
                {
                    if(mb_strlen($p['question']) < 5 || mb_strlen($p['question']) > 255)
                    {
                        $existing = true;
                        $form->get('question')->addError(new FormError('La question entre 5 et 255 caractères.'));
                    }
                }

                if(array_key_exists('answers', $p))
                {
                    $count = mb_substr_count($p['answers'], ';');
                    
                    if(!preg_match('#^([a-zA-Z0-9-_ ]{1,}:[a-z0-9]{1,};){1,}$#', $p['answers']))
                    {
                        $existing = true;
                        $form->get('answers')->addError(new FormError('Veuillez respecter le format demandé.'));
                    }
                }

                if(array_key_exists('solution', $p))
                {
                    if($p['solution'] < 1 || $p['solution'] > $count)
                    {
                        $existing = true;
                        $form->get('solution')->addError(new FormError('Le numéro de la solution proposé est incorrect.'));
                    }
                }
            }

            $errors = $validator->validate([
                $form->get('title'),
                $form->get('question')
            ]);

            if(count($errors) || $existing === true)
            {
                return $this->render('page/instructor/edit_quiz_section.html.twig', [
                    'form' => $form->createView(),
                    'formation' => $f,
                    'section' => $s,
                    'quiz' => $q,
                    'errors' => $errors
                ]);
            }

            if($form->isValid())
            {
                $em = $this->manager->getManager();

                $q->setTitle($p['title']);
                $q->setQuestion($p['question']);
                $q->setAnswers([$p['answers']]);
                $q->setSolution($p['solution']);

                $em->persist($q);
                
                $em->flush();

                $this->addFlash('success', 'Le quiz a été modifiée avec succès');

                return $this->redirectToRoute('instructor_edit_quiz_section', [
                    'number_f' => $f->getNumber(),
                    'number_s' => $s->getNumber(),
                    'id' => $q->getId()
                ]);
            }
        }

        return $this->render('page/instructor/edit_quiz_section.html.twig', [
            'form' => $form->createView(),
            'formation' => $f,
            'section' => $s,
            'quiz' => $q
        ]);
    }

    /**
     * Permet de chercher si le numéro de la formation exite déjà ou pas
     * si il existe on fait une récurssion
     *
     * @param string $n
     * @param FormationsRepository|SectionsRepository $f
     * @return self|string
     */
    private function unicity(string $n, Object $f): self|string
    {
        if(empty($f->findBy(['number' => $n])))
        {
            return $n;
        }
        else
        {
            $number = ByteString::fromRandom(10)->toString();
        
            return self::unicity($number, $f);
        }
    }
}