<?php

namespace App\Entity;

use App\Repository\LessonsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LessonsRepository::class)]
class Lessons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[
        ORM\Column(type: 'text'),
        Assert\Length(
            min: 5,
            minMessage: 'Le contenu entre 5 et 255 caractères'
        ),
        Assert\NotBlank(message: 'Vous devez définir un minimum de contenu')
    ]
    private $content;

    #[ORM\Column(type: 'integer')]
    private $rank_order;

    #[ORM\ManyToOne(targetEntity: Sections::class, inversedBy: 'lessons')]
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

    public function __construct()
    {
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
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

    public function getSections(): ?Sections
    {
        return $this->sections;
    }

    public function setSections(?Sections $sections): self
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
