<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UsersRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Pour rediriger sur la page login si connecté
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/oubli-mot-de-passe', name: 'forgotten_password')]
    public function forgottenPassword(
        Request $request,
        UsersRepository $usersRepository,
        TokenGeneratorInterface $tokengeneratorinterface,
        EntityManagerInterface $em,
        SendMailService $mailer
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            //on cherche l'utilisateur par l'email
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
            // dd($user);
            // on verif si on a un utilisateur
            if($user){
                // on génère un token de reinitialisation
                $token = $tokengeneratorinterface->generateToken();
                // dd($token);
                $user->setResetToken($token);
                $em->persist($user);
                $em->flush();

                //on génère le lien de réinitialisation du mot de passe 
                $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL); // pour generer l'url 

                // On crée les données du mail
                $context = compact('url', 'user');

                // envoi du mail
                $mailer->send(
                    'no-reply@snowtricks.fr',
                    $user->getEmail(),
                    'Réinitialisation de votre mot de passe',
                    'reset_password',
                    $context

                );
                $this->addFlash('success', 'Email de réinitialisation envoyé');
                return $this->redirectToRoute('app_login');
            }
            //Si pas d'utilisateur $user est null 
            $this->addFlash('danger', 'Un problème est survenu');
        }
        return $this->render('security/reset_password_request.html.twig', [
            'requestPassForm' => $form->createView()
        ]);
    }

    #[Route(path: '/oubli-pass/{token}', name: 'reset_pass')]
    public function resetPass(
        string $token,
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // verif si le token est enregistré en bdd 
        $user = $usersRepository->findOneByResetToken($token);

        if($user){

            $form = $this->createForm(ResetPasswordFormType::class);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                //efface le token
                $user->setResetToken('');
                //nouveau mot de passe du formulaire à hasher
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $form->get('password')->getData())
                );
                //envoi
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Mot de passe enregistré');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig', [
            'PassForm' => $form->createView()
            ]);
        }
        $this->addFlash('danger', 'Un problème est survenu');
        return $this->redirectToRoute('app_login');

    }
}
