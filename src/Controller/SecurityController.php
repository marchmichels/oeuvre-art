<?php
/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: SecurityController.php
 * Description: The SecurityController controls the routes for displaying the user login form
 *              and for logging out the user and returning to the homepage.
 * Extends: AbstractController
 * Routes: /login                name: app_login
 *         /logout               name: app_logout
 */

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    //Generate login form and render login page
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }


    //Logout and render homepage
    #[Route('/logout', name: 'app_logout')]
    public function logout() : Response
    {
        return $this->render('home/home.html.twig', [
            'title' => 'Collaborative Art',
        ]);
    }

}
