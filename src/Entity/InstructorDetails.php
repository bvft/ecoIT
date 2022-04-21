<?php

namespace App\Entity;

use App\Repository\InstructorDetailsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InstructorDetailsRepository::class)]
class InstructorDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[
        ORM\Column(type: 'text'),
        Assert\Length(max: 500, maxMessage: 'Vos spécialités : {{ max }} caractères max.'),
        Assert\NotBlank(message: 'Vous devez définir vos spécialités')
    ]
    private $desc_specs;

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
            maxSize: '5M',
            maxSizeMessage: 'Taille maximum de la photo : {{ size }}'
        )
    ]
    private $picture;

    #[ORM\Column(type: 'smallint', nullable: true, options: ["default" => NULL])]
    private $status;

    #[ORM\OneToOne(inversedBy: 'instructorDetails', targetEntity: PersonDetails::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $person_details;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescSpecs(): ?string
    {
        return $this->desc_specs;
    }

    public function setDescSpecs(string $desc_specs): self
    {
        $this->desc_specs = $desc_specs;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPersonDetails(): ?PersonDetails
    {
        return $this->person_details;
    }

    public function setPersonDetails(PersonDetails $person_details): self
    {
        $this->person_details = $person_details;

        return $this;
    }
}
