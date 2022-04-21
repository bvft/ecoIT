<?php

namespace App\Entity;

use App\Repository\FormationsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationsRepository::class)]
class Formations
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
            minMessage: 'Le titre entre 5 et 255 caractères',
            maxMessage: 'Le titre entre 5 et 255 caractères'
        ),
        Assert\NotBlank(message: 'Vous devez définir un titre')
    ]
    private $title;

    #[
        ORM\Column(type: 'string', length: 255),
        Assert\Image(
            detectCorrupted: true,
            corruptedMessage: 'Votre photo est corrompue',
            mimeTypes: [
                'image/png',
                'image/jpeg'
            ],
            mimeTypesMessage: 'Format accepté : png, jpeg, jpg',
            maxSize: '10M',
            maxSizeMessage: 'Taille maximum de la photo : {{ size }}'
        )
    ]
    private $picture;

    #[
        ORM\Column(type: 'string', length: 255),
        Assert\Length(
            min: 15, 
            max: 255, 
            minMessage: 'La courte description entre 15 et 255 caractères',
            maxMessage: 'La courte description entre 15 et 255 caractères'
        ),
        Assert\NotBlank(message: 'Vous devez définir une courte description')
    ]
    private $short_text;

    #[ORM\Column(type: 'datetime')]
    private $create_at;

    #[ORM\Column(type: 'smallint', options: ["default" => 0])]
    private $status;

    #[ORM\Column(type: 'string', length: 10)]
    private $number;

    #[ORM\ManyToOne(targetEntity: Rubrics::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private $rubrics;

    #[ORM\ManyToOne(targetEntity: PersonDetails::class, inversedBy: 'formations')]
    private $person_details;

    #[ORM\OneToMany(mappedBy: 'formations', targetEntity: Sections::class, orphanRemoval: true)]
    private $sections;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getShortText(): ?string
    {
        return $this->short_text;
    }

    public function setShortText(string $short_text): self
    {
        $this->short_text = $short_text;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->create_at;
    }

    public function setCreateAt(\DateTimeInterface $create_at): self
    {
        $this->create_at = $create_at;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    public function getRubrics(): ?Rubrics
    {
        return $this->rubrics;
    }

    public function setRubrics(?Rubrics $rubrics): self
    {
        $this->rubrics = $rubrics;

        return $this;
    }

    public function getPersonDetails(): ?PersonDetails
    {
        return $this->person_details;
    }

    public function setPersonDetails(?PersonDetails $person_details): self
    {
        $this->person_details = $person_details;

        return $this;
    }

    /**
     * @return Collection<int, Sections>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Sections $section): self
    {
        if (!$this->sections->contains($section)) {
            $this->sections[] = $section;
            $section->setFormations($this);
        }

        return $this;
    }

    public function removeSection(Sections $section): self
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getFormations() === $this) {
                $section->setFormations(null);
            }
        }

        return $this;
    }
}
