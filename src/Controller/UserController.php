<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\JwtToken;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class UserController extends AbstractController
{
    #[Route('/register', name: 'user_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('register_success');
        }

        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register_success', name: 'register_success')]
    public function success(): Response
    {
        return $this->render('register/success.html.twig');
    }

    #[Route('/login', name: 'login_form', methods: ['GET'])]
    public function loginForm(): Response
    {
        return $this->render('login/index.html.twig'); // Formulaire de connexion
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordEncoder, JwtService $jwtService, EntityManagerInterface $em): Response {
        $data = json_decode($request->getContent(), true);  // Ici, tu essaies de décoder un JSON
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $user = $userRepository->findOneBy(['email' => $email]);


        if (!$user || !$passwordEncoder->isPasswordValid($user, $password)) {
            dump('Invalid credentials');
            return new JsonResponse(['error' => 'Invalid credentials.'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        

        // Generate JWT
        $token = $jwtService->createToken($user);
        $tokenString = $token->toString();
        $expiresAt = $token->claims()->get('exp');

        // Store JWT in the database
        $jwtToken = new JwtToken();
        $jwtToken->setToken($tokenString);
        $jwtToken->setExpiresAt($expiresAt);
        $jwtToken->setUser($user);

        $em->persist($jwtToken);
        $em->flush();

        return $this->redirectToRoute('login_success');
    }


    #[Route('/login_success', name: 'login_success')]
    public function log_success(): Response
    {
        return $this->render('login/success.html.twig');
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $tokenString = substr($authHeader, 7);
        $jwtToken = $em->getRepository(JwtToken::class)->findOneBy(['token' => $tokenString]);

        if ($jwtToken) {
            $em->remove($jwtToken);
            $em->flush();
        }

        return new JsonResponse(['message' => 'Successfully logged out.']);
    }
}
