<?php

/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: HomeController.php
 * Description: The HomeController controls the route for the home page.
 * Extends: AbstractController
 * Routes: /                    name: home
 */


namespace App\Controller;


use App\Entity\User;
use App\Utilities\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Aws\Lambda\LambdaClient;



class HomeController extends AbstractController
{
    // renders the home page
    #[Route('/', name: 'home')]
    public function displayHome(UploaderHelper $uploaderHelper): Response
    {

        $signedUrl = $uploaderHelper->getMosaicPublicPath();

        // render the home page
        return $this->render('home/home.html.twig', [
            'title' => 'Oeuvre Art',
            'image_url' => $signedUrl
        ]);
    }


    // runs a python script and renders the homepage with the output from the python script
    #[Route('/regenerate', name: 'regenerate')]
    public function regenerateHome(UploaderHelper $uploaderHelper, LambdaClient $lambdaClient): Response
    {
        // prevent route from being accessed unless authenticated with developer role
        $this->denyAccessUnlessGranted('ROLE_DEVELOPER');

        $client = HttpClient::create();

        $lambdaClient->Invoke(['FunctionName' => 'oeuvreart_python']);

        //$result = $client


        $signedUrl = $uploaderHelper->getMosaicPublicPath();


        // render the home page with output string from Python script
        return $this->render('home/home.html.twig', [
            'title' => 'Oeuvre Art',
            'image_url' => $signedUrl
        ]);
    }
}
