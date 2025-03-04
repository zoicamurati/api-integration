<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\Column(type: 'string', length: 100)]
    private string $source; // Sprengnetter or Europace

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastUpdated;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }


    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }
}
