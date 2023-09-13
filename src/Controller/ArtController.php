<?php
/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: ArtController.php
 * Description: The ArtController controls routes for displaying all artist contributions
 *              and for displaying artwork upload form.
 * Extends: AbstractController
 * Routes: /artist_contributions                    name: artist_contributions
 *         /artist_contributions/upload             name: add_art
 */

namespace App\Controller;

use App\Entity\Art;
use App\Form\ArtFormType;
use App\Repository\ArtRepository;
use App\Repository\UserRepository;
use App\Utilities\UploaderHelper;
use Aws\S3\S3Client;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtController extends AbstractController
{

    //get all artwork and display artist contribution page
    #[Route('/art', name: 'art')]
    public function displayArtistContributions(ArtRepository $artRepository, UploaderHelper $uploaderHelper): Response
    {

        // get all art objects from SQL
        $art = $artRepository->findAll(); //an array of art Entity objects

        // empty array
        $artIdArray = array();

        // for each object from SQL array...
        foreach ($art as $a) {

            // get art id from SQL
            $artId = $a->getId();
            // call AWS S3 to get public url
            $signedUrl = $uploaderHelper->getPublicPath($a->getFileurl());

            // populate array with public URL based on SQL artID
            $artIdArray[$artId] = $signedUrl;

        }


        // render artist contribution page
        return $this->render('art/art.html.twig', [
            'title' => 'Artist Contributions',
            'artData' => $artIdArray
        ]);
    }


    // get art by id and display art detail page
    #[Route('/art/detail/{id}', name: 'art_detail')]
    public function displayArtistDetail(ArtRepository $artRepository, UserRepository $userRepository, int $id, UploaderHelper $uploaderHelper): Response
    {


        // get an Art entity for the id
        $art = $artRepository->findOneBy(['id' => $id]);
        // get the user id of the user who uploaded the art
        $userId = $art->getUserid();
        // get the User entity for the user who uploaded the art
        $uploadedBy = $userRepository->findOneBy(['id' => $userId]);
        // get the username of the user who uploaded the art
        $uploadedByUserName = $uploadedBy->getUsername();

        // get the upload date from the Art entity
        $uploadDate = $art->getDate();
        // format date
        $formatDate = $uploadDate->format('F j, Y');


        $detailArtUrl = $uploaderHelper->getPublicPath($art->getFileurl());


        $additionalArt = $artRepository->findBy(['userid' => $userId]);


        $artIdArray = array();


            // for each object from SQL array...
            foreach ($additionalArt as $a) {

                // get art id from SQL
                $artId = $a->getId();


                if($id != $artId) {
                    // call AWS S3 to get public url
                    $signedUrl = $uploaderHelper->getPublicPath($a->getFileurl());
                    // populate array with public URL based on SQL artID
                    $artIdArray[$artId] = $signedUrl;
                }


            }


        return $this->render('art/art_detail.html.twig', [
            'title' => 'Art Detail',
            'artDetail' => $detailArtUrl,
            'userName' => $uploadedByUserName,
            'uploadDate' => $formatDate,
            'artData' => $artIdArray
        ]);
    }




    // display art upload form
    #[Route('/art/upload', name: 'upload_art')]
    public function addArt(EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {

        $form = $this->createForm(ArtFormType::class);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Art $art */
            $art = $form->getData();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['artfile']->getData();

            if ($uploadedFile) {
                $newFilename = $uploaderHelper->uploadArt($uploadedFile, $art->getFileurl());
                $art->setFileurl($newFilename);
                $user = $this->getUser()->getId();
                $art->setUserid($user);
                $time = new DateTime();
                $art->setDate($time);
            }

            $em->persist($art);
            $em->flush();

            $this->addFlash('success', 'image uploaded');

            return $this->redirectToRoute('art');
        }


        return $this->render('art/upload_art.html.twig', [
            'title' => 'Upload Art',
            'art_upload_form' => $form->createView()
        ]);

    }


    // remove art by id, then render manage my account page
    #[Route('/art/remove/{id}', name: 'remove_art')]
    public function removeArt(EntityManagerInterface $em, ArtRepository $artRepository, int $id, S3Client $s3Client, UploaderHelper $uploaderHelper) : Response
    {

        $userId = $this->getUser()->getId();


        $art = $artRepository->findOneBy(['id' => $id]);
        $art_userId = $art->getUserid();

        if ($userId == $art_userId) {

            $file_url = $art->getFileurl();

            // delete image object from AWS S3
            $uploaderHelper->deleteArt($file_url);

            // remove Art entity from SQL database
            $em->remove($art);
            $em->flush();

        } else {

            $this->createAccessDeniedException();

        }

        $art = $artRepository->findBy(['userid' => $userId]);


        $artIdArray = array();


        foreach ($art as $a) {

            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => 'oeuvreartbucket',
                'Key' => 'art_image/' . $a->getFileurl()
            ]);

            $artId = $a->getId();


            $request = $s3Client->createPresignedRequest($cmd, '+10 minutes');

            $signedUrl = (string)$request->getUri();

            $artIdArray[$artId] = $signedUrl;

        }



        return $this->redirectToRoute('user');


    }













































}