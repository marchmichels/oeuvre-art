<?php
/**
 * Author: Marc Michels
 * Date: 9/1/22
 * File: Art.php
 * Description: The Art Class Entity represents an art object within the Doctrine object-relational mapper
 * RepositoryClass: ArtRepository
 * Entity Structure:
 *
 * Primary Key ->   id  int(11)
 *                  fileurl varchar(255)
 * Foreign Key ->   userid int(11)
 *                  date date
 *
 * Public Methods: getId - returns entity id
 *                 getFileurl - returns Art file url
 *                 setFileurl - sets Art file url
 *                 getUserid - returns User id for Art entity
 *                 setUserid - sets User id for Art entity
 *                 getDate - returns DateTimeInterface for Art entity
 *                 setDate - sets DateTimeInterface for Art entity
 */

namespace App\Entity;

use App\Repository\ArtRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtRepository::class)]
class Art
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fileurl = null;

    #[ORM\Column]
    private ?int $userid = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileurl(): ?string
    {
        return $this->fileurl;
    }

    public function setFileurl(string $fileurl): self
    {
        $this->fileurl = $fileurl;

        return $this;
    }

    public function getUserid(): ?int
    {
        return $this->userid;
    }

    public function setUserid(int $userid): self
    {
        $this->userid = $userid;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
