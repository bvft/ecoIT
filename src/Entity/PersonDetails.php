<?php

namespace App\Entity;

use App\Repository\PersonDetailsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonDetailsRepository::class)]
class PersonDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[
        ORM\Column(type: 'string', length: 45, nullable: true),
        Assert\Length(min: 2, max: 45,
            minMessage: 'Le nom entre 2 et 45 caractères',
            maxMessage: 'Le nom entre 2 et 45 caractères'),
        Assert\NotBlank(message: 'Le nom ne peut être vide')
    ]
    private $name;

    #[
        ORM\Column(type: 'string', length: 45, nullable: true),
        Assert\Length(min: 2, max: 45,
            minMessage: 'Le prénom entre 2 et 45 caractères',
            maxMessage: 'Le prénom entre 2 et 45 caractères'),
        Assert\NotBlank(message: 'Le prénom ne peut être vide')
    ]
    private $first_name;

    #[
        ORM\Column(type: 'string', length: 45, nullable: true),
        Assert\Length(min: 5, max: 45, minMessage: 'Pseudo entre 5 et 45 caractères', maxMessage: 'Pseudo entre 5 et 45 caractères'),
        Assert\NotBlank(message: 'Le pseudo ne peut être vide')
    ]
    private $pseudo;

    #[ORM\OneToOne(inversedBy: 'personDetails', targetEntity: PersonLoginInfo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $person_login_info;

    #[ORM\OneToOne(mappedBy: 'person_details', targetEntity: InstructorDetails::class, cascade: ['persist', 'remove'])]
    private $instructorDetails;

    #[ORM\OneToMany(mappedBy: 'person_details', targetEntity: Formations::class)]
    private $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getPersonLoginInfo(): ?PersonLoginInfo
    {
        return $this->person_login_info;
    }

    public function setPersonLoginInfo(PersonLoginInfo $person_login_info): self
    {
        $this->person_login_info = $person_login_info;

        return $this;
    }

    public function getInstructorDetails(): ?InstructorDetails
    {
        return $this->instructorDetails;
    }

    public function setInstructorDetails(InstructorDetails $instructorDetails): self
    {
        // set the owning side of the relation if necessary
        if ($instructorDetails->getPersonDetails() !== $this) {
            $instructorDetails->setPersonDetails($this);
        }

        $this->instructorDetails = $instructorDetails;

        return $this;
    }

    /**
     * @return Collection<int, Formations>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formations $formation): self
    {
        if (!$this->formations->contains($formation)) {
            $this->formations[] = $formation;
            $formation->setPersonDetails($this);
        }

        return $this;
    }

    public function removeFormation(Formations $formation): self
    {
        if ($this->formations->removeElement($formation)) {
            // set the owning side to null (unless already changed)
            if ($formation->getPersonDetails() === $this) {
                $formation->setPersonDetails(null);
            }
        }

        return $this;
    }
}
