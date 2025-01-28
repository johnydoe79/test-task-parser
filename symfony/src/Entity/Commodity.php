<?php

namespace App\Entity;

use App\Repository\CommodityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommodityRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_commodity_name', columns: ['name'])]
class Commodity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commodity:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['commodity:read'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['commodity:read'])]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['commodity:read'])]
    private ?string $photo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['commodity:read'])]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;

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
}
