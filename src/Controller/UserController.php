<?php
/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: UserController.php
 * Description: The UserController controls the route for the displaying the manage my account page.
 * Extends: AbstractController
 * Routes: /manage_my_account                    name: manage_my_account
 */

namespace App\Controller;


use App\Entity\User;
use App\Repository\ArtRepository;
use App\Repository\UserRepository;
use App\Utilities\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Model\ChangePassword;
use App\Form\ChangePasswordType;


class UserController extends AbstractController
{

    // get art by user id and display manage my account page
    #[Route('/user', name: 'user')]
    public function displayManageMyAccount(ArtRepository $artRepository, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, UploaderHelper $uploaderHelper): Response
    {

        // get current User
        $user = $this->getUser();
        // get current user's id
        $userId = $user->getId();


        $changePasswordModel = new ChangePassword();
        $form = $this->createForm(ChangePasswordType::class, $changePasswordModel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $entityManager->find(User::class, $this->getUser()->getId());
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('newPassword')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirect($this->generateUrl('user'));
        }


        // get all the user's art
        $art = $artRepository->findBy(['userid' => $userId]);

        // empty array
        $artIdArray = array();

        // for each Art Entity uploaded by this user...
        foreach ($art as $a) {

            // get art id from SQL
            $artId = $a->getId();
            // call AWS S3 to get public url
            $signedUrl = $uploaderHelper->getPublicPath($a->getFileurl());
            // populate array with public URL based on SQL artID
            $artIdArray[$artId] = $signedUrl;

        }


        return $this->render('user/user.html.twig', [
            'title' => 'Manage My Account',
            'artData' => $artIdArray,
            'passForm' => $form->createView()
        ]);
    }

    //delete user
    #[Route('/user/delete_user', name: 'user_delete')]
    public function UserDelete(Request $request, UserRepository $userRepository, ArtRepository $artRepository, UploaderHelper $uploaderHelper, EntityManagerInterface $em, Session $session): Response
    {

        // get current User
        $user = $this->getUser();
        // get current user's id
        $userId = $user->getId();

        //delete the user
        $this->deleteUser($userId, $request, $artRepository, $uploaderHelper, $em, $userRepository);

        //redirect and display the homepage
        return $this->redirectToRoute('home');

    }






    // renders the admin dashboard
    #[Route('/user/administrator', name: 'admin_dashboard')]
    public function displayAdminDashboard(UserRepository $userRepository, ArtRepository $artRepository, UploaderHelper $uploaderHelper): Response
    {
        // prevent route from being accessed unless authenticated with admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        // get all art objects from SQL
        return $this->renderAdminDashboard($artRepository, $uploaderHelper, $userRepository); //an array of art Entity objects


    }


    //delete art by id and render admin dashboard
    #[Route('/user/administrator/delete_art/{id}', name: 'admin_art_delete')]
    public function adminArtDelete(EntityManagerInterface $em, int $id, ArtRepository $artRepository, UploaderHelper $uploaderHelper, UserRepository $userRepository): Response
    {

        // prevent route from being accessed unless authenticated with admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $art = $artRepository->findOneBy(['id' => $id]);
        $file_url = $art->getFileurl();

        // delete image object from AWS S3
        $uploaderHelper->deleteArt($file_url);

        // remove Art entity from SQL database
        $em->remove($art);
        $em->flush();


        // get all art objects from SQL
        return $this->renderAdminDashboard($artRepository, $uploaderHelper, $userRepository);
    }


    //delete user by id and render admin dashboard
    #[Route('/user/administrator/delete_user/{id}', name: 'admin_user_delete')]
    public function adminUserDelete(int $id, Request $request, UserRepository $userRepository, ArtRepository $artRepository, UploaderHelper $uploaderHelper, EntityManagerInterface $em): Response
    {
        // prevent route from being accessed unless authenticated with admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $this->deleteUser($id, $request, $artRepository, $uploaderHelper, $em, $userRepository);

        return $this->renderAdminDashboard($artRepository, $uploaderHelper, $userRepository);
    }


    public function deleteUser($id, Request $request, ArtRepository $artRepository, UploaderHelper $uploaderHelper, EntityManagerInterface $em, UserRepository $userRepository)
    {

        $currentUserId = $this->getUser()->getId();

        if ($currentUserId == $id)
        {
            $session = $request->getSession();
            $session->invalidate();
            $this->container->get('security.token_storage')->setToken(null);
        }

        //delete the user's art
        $userArt = $artRepository->findBy(['userid' => $id]);


        foreach ($userArt as $a) {

            //get public url
            $file_url = $a->getFileurl();
            //remove image from S3
            $uploaderHelper->deleteArt($file_url);
            //remove Art entity from SQL database
            $em->remove($a);
            $em->flush();


        }


        //delete the User entity from SQL database
        $user = $userRepository->findOneBy(['id' => $id]);
        $userRepository->remove($user, true);

    }

    /**
     * @param ArtRepository $artRepository
     * @param UploaderHelper $uploaderHelper
     * @param UserRepository $userRepository
     * @return Response
     */
    public function renderAdminDashboard(ArtRepository $artRepository, UploaderHelper $uploaderHelper, UserRepository $userRepository): Response
    {
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


        //display all users
        $users = $userRepository->findAll();


        // render admin dashboard
        return $this->render('admin/admin.html.twig', [
            'title' => 'Admin Dashboard',
            'users' => $users,
            'arts' => $artIdArray
        ]);
    }


}