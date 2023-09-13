<?php
/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: ArtRepository.php
 * Description: The ArtRepository Class provides methods for querying the Art table of the database using
 *              the Doctrine object-relational mapper.
 *
 * Entity Class: Art
 * Entity Structure:
 *
 * Primary Key ->   id  int(11)
 *                  fileurl varchar(255)
 * Foreign Key ->   userid int(11)
 *                  date date
 *
 * Public Methods: add - adds an Art entity to the database
 *                 remove - removes an Art entity from the database
 */

namespace App\Repository;

use App\Entity\Art;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Art>
 *
 * @method Art|null find($id, $lockMode = null, $lockVersion = null)
 * @method Art|null findOneBy(array $criteria, array $orderBy = null)
 * @method Art[]    findAll()
 * @method Art[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Art::class);
    }

    public function add(Art $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Art $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
