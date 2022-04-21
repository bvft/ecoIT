<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[
        ORM\Column(type: 'string', length: 255),
        Assert\Length(
            min: 5, 
            max: 255, 
            minMessage: 'La question entre 5 et 255 caractères',
            maxMessage: 'La question entre 5 et 255 caractères'
        ),
        Assert\NotBlank(message: 'Vous devez définir une question')
    ]
    private $question;

    #[ORM\Column(type: 'json')]
    private $answers = [];

    #[ORM\Column(type: 'smallint')]
    private $solution;

    #[ORM\ManyToOne(targetEntity: Sections::class, inversedBy: 'quiz')]
    #[ORM\JoinColumn(nullable: false)]
    private $sections;

    #[
        ORM\Column(type: 'string', length: 255),
        Assert\Length(
            min: 5, 
            max: 255, 
            minMessage: 'Le titre entre 5 et 255 caractères',
            maxMessage: 'Le titre entre 5 et 255 caractères'
        ),
        Assert\NotBlank(message: 'Vous devez définir un titre')
    ]
    private $title;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    public function getSolution(): ?int
    {
        return $this->solution;
    }

    public function setSolution(int $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function getSections(): ?Sections
    {
        return $this->sections;
    }

    public function setSections(Sections $sections): self
    {
        $this->sections = $sections;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
