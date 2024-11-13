<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    #[Route('/register', name: 'user_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle instance de l'entité User
        $user = new User();

        // Créer le formulaire et le lier à l'entité
        $form = $this->createForm(UserType::class, $user);

        // Traiter la requête HTTP
        $form->handleRequest($request);

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Rediriger vers une autre page ou afficher un message de succès
            return $this->redirectToRoute('register_success');
        }

        // Rendre le formulaire pour qu'il soit affiché à l'utilisateur
        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);


    }
    
    #[Route('/register_success', name: 'register_success')]
    public function success(): Response
    {
        return $this->render('register/success.html.twig');
    }
    
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush(); 
            return $this->redirectToRoute('login_success');
        }
        
        
        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
        
    }
    
    #[Route('/login_success', name: 'login_success')]
    public function log_success(): Response
    {
        return $this->render('login/success.html.twig');
    }
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Symfony fait la deco 
    }
}