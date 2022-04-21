<?php

namespace App\Entity;

use App\Repository\PersonLoginInfoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonLoginInfoRepository::class)]
class PersonLoginInfo implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[
        ORM\Column(type: 'string', length: 180, unique: true),
        Assert\Email(mode: 'html5', message: 'L\'adresse e-mail {{ value }} est invalide'),
        Assert\NotBlank(message: 'L\'adresse e-mail ne peut être vide')
    ]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[
        ORM\Column(type: 'string'),
        Assert\NotBlank(message: 'Le mot de passe ne peut être vide.'),
        Assert\Length(min: 6, minMessage: 'Minimum 6 caractères')
    ]
    private $password;

    #[ORM\OneToOne(mappedBy: 'person_login_info', targetEntity: PersonDetails::class, cascade: ['persist', 'remove'])]
    private $personDetails;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPersonDetails(): ?PersonDetails
    {
        return $this->personDetails;
    }

    public function setPersonDetails(PersonDetails $personDetails): self
    {
        // set the owning side of the relation if necessary
        if ($personDetails->getPersonLoginInfo() !== $this) {
            $personDetails->setPersonLoginInfo($this);
        }

        $this->personDetails = $personDetails;

        return $this;
    }
}
