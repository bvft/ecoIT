<?php

namespace App\Entity;

use App\Repository\StudentFormationStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentFormationStatusRepository::class)]
class StudentFormationStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'smallint')]
    private $status;

    #[ORM\ManyToOne(targetEntity: PersonDetails::class)]
    private $person_details;

    #[ORM\ManyToOne(targetEntity: Formations::class)]
    private $formations;

    public function __construct()
    {
        
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPersonDetails(): ?PersonDetails
    {
        return $this->person_details;
    }

    public function setPersonDetails(?PersonDetails $person_details): self
    {
        $this->person_details = $person_details;

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
}
