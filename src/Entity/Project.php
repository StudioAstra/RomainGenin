<?php

namespace App\Entity;

use App\Enum\ProjectKind;
use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: ProjectKind::class)]
    private ProjectKind $kind = ProjectKind::Pro;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    private ?string $title = null;

    /** Ex. « Client · Agence ». */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $client = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $url = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $technologies = [];

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $published = true;

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** Domaine affiché sous le lien, ex. « getstudioastra.fr ». */
    public function getUrlLabel(): ?string
    {
        if (null === $this->url) {
            return null;
        }

        $host = parse_url($this->url, \PHP_URL_HOST) ?: $this->url;

        return preg_replace('/^www\./', '', $host);
    }

    public function getKind(): ProjectKind
    {
        return $this->kind;
    }

    public function setKind(?ProjectKind $kind): static
    {
        $this->kind = $kind ?? ProjectKind::Pro;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(?string $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /** @return list<string> */
    public function getTechnologies(): array
    {
        return $this->technologies;
    }

    /** @param list<string>|null $technologies */
    public function setTechnologies(?array $technologies): static
    {
        $this->technologies = array_values(array_filter($technologies ?? [], static fn ($s) => '' !== trim((string) $s)));

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 0;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(?bool $published): static
    {
        $this->published = (bool) $published;

        return $this;
    }
}
