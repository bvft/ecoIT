<?php

namespace App\Entity;

use App\Repository\SectionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SectionsRepository::class)]
class Sections
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $rank_order;

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

    #[ORM\Column(type: 'string', length: 10)]
    private $number;

    #[ORM\ManyToOne(targetEntity: Formations::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false)]
    private $formations;

    #[ORM\OneToMany(mappedBy: 'sections', targetEntity: Lessons::class, orphanRemoval: true)]
    private $lessons;

    #[ORM\OneToMany(mappedBy: 'sections', targetEntity: Quiz::class, orphanRemoval: true)]
    private $quiz;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRankOrder(): ?int
    {
        return $this->rank_order;
    }

    public function setRankOrder(int $rank_order): self
    {
        $this->rank_order = $rank_order;

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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getFormations(): ?Formations
    {
        return $this->formations;
    }

    public function setFormations(?Formations $formations): self
    {
        $this->formations = $formations;

        return $this;
    }

    /**
     * @return Collection<int, Lessons>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lessons $lesson): self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons[] = $lesson;
            $lesson->setSections($this);
        }

        return $this;
    }

    public function removeLesson(Lessons $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getSections() === $this) {
                $lesson->setSections(null);
            }
        }

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(Quiz $quiz): self
    {
        // set the owning side of the relation if necessary
        if ($quiz->getSections() !== $this) {
            $quiz->setSections($this);
        }

        $this->quiz = $quiz;

        return $this;
    }
}
