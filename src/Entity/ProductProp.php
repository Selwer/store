<?php

namespace App\Entity;

use App\Repository\ProductPropRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductPropRepository::class)
 */
class ProductProp
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $weight;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $volume;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_size;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_diameter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_model;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_layer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_execut;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tire_rim;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fork_length;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fork_section;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fork_class;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fork_load;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acb_size;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acb_tech;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acb_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acb_seria;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acb_model;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getVolume(): ?string
    {
        return $this->volume;
    }

    public function setVolume(?string $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getTireSize(): ?string
    {
        return $this->tire_size;
    }

    public function setTireSize(?string $tire_size): self
    {
        $this->tire_size = $tire_size;

        return $this;
    }

    public function getTireType(): ?string
    {
        return $this->tire_type;
    }

    public function setTireType(?string $tire_type): self
    {
        $this->tire_type = $tire_type;

        return $this;
    }

    public function getTireDiameter(): ?string
    {
        return $this->tire_diameter;
    }

    public function setTireDiameter(?string $tire_diameter): self
    {
        $this->tire_diameter = $tire_diameter;

        return $this;
    }

    public function getTireModel(): ?string
    {
        return $this->tire_model;
    }

    public function setTireModel(?string $tire_model): self
    {
        $this->tire_model = $tire_model;

        return $this;
    }

    public function getTireLayer(): ?string
    {
        return $this->tire_layer;
    }

    public function setTireLayer(?string $tire_layer): self
    {
        $this->tire_layer = $tire_layer;

        return $this;
    }

    public function getTireExecut(): ?string
    {
        return $this->tire_execut;
    }

    public function setTireExecut(?string $tire_execut): self
    {
        $this->tire_execut = $tire_execut;

        return $this;
    }

    public function getTireRim(): ?string
    {
        return $this->tire_rim;
    }

    public function setTireRim(?string $tire_rim): self
    {
        $this->tire_rim = $tire_rim;

        return $this;
    }

    public function getForkLength(): ?string
    {
        return $this->fork_length;
    }

    public function setForkLength(?string $fork_length): self
    {
        $this->fork_length = $fork_length;

        return $this;
    }

    public function getForkSection(): ?string
    {
        return $this->fork_section;
    }

    public function setForkSection(?string $fork_section): self
    {
        $this->fork_section = $fork_section;

        return $this;
    }

    public function getForkClass(): ?string
    {
        return $this->fork_class;
    }

    public function setForkClass(?string $fork_class): self
    {
        $this->fork_class = $fork_class;

        return $this;
    }

    public function getForkLoad(): ?string
    {
        return $this->fork_load;
    }

    public function setForkLoad(?string $fork_load): self
    {
        $this->fork_load = $fork_load;

        return $this;
    }

    public function getacbSize(): ?string
    {
        return $this->acb_size;
    }

    public function setacbSize(?string $acb_size): self
    {
        $this->acb_size = $acb_size;

        return $this;
    }

    public function getacbTech(): ?string
    {
        return $this->acb_tech;
    }

    public function setacbTech(?string $acb_tech): self
    {
        $this->acb_tech = $acb_tech;

        return $this;
    }

    public function getacbType(): ?string
    {
        return $this->acb_type;
    }

    public function setacbType(?string $acb_type): self
    {
        $this->acb_type = $acb_type;

        return $this;
    }

    public function getacbSeria(): ?string
    {
        return $this->acb_seria;
    }

    public function setacbSeria(?string $acb_seria): self
    {
        $this->acb_seria = $acb_seria;

        return $this;
    }

    public function getacbModel(): ?string
    {
        return $this->acb_model;
    }

    public function setacbModel(?string $acb_model): self
    {
        $this->acb_model = $acb_model;

        return $this;
    }
}
