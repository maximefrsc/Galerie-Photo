<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    // Âge minimum pour s'inscrire (à modifier si besoin)
    private const AGE_MIN = 13;
    private const AGE_MAX = 120;

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();
        $errors = [];
        $old = ['email' => '', 'pseudo' => '', 'age' => ''];

        // Quand on ouvre le formulaire : on retient la page d'où l'on vient
        // et on prépare une nouvelle question de captcha.
        if ($request->isMethod('GET')) {
            $session->set('register_back', $this->getPreviousPage($request, $session->get('register_back', '/')));
            $this->createCaptcha($request);
        }

        if ($request->isMethod('POST')) {
            // On récupère ce que l'internaute a saisi
            $old['email'] = trim((string) $request->request->get('email'));
            $old['pseudo'] = trim((string) $request->request->get('pseudo'));
            $old['age'] = trim((string) $request->request->get('age'));
            $captcha = trim((string) $request->request->get('captcha'));

            // Vérification du formulaire
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                $errors['general'] = 'Le formulaire a expiré, veuillez réessayer.';
            }

            if ($old['email'] === '') {
                $errors['email'] = "L'e-mail est obligatoire.";
            } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Cet e-mail n'est pas valide.";
            } elseif ($userRepository->findOneBy(['email' => $old['email']])) {
                $errors['email'] = 'Un compte existe déjà avec cet e-mail.';
            }

            if ($old['pseudo'] === '') {
                $errors['pseudo'] = 'Le pseudo est obligatoire.';
            } elseif (mb_strlen($old['pseudo']) < 3 || mb_strlen($old['pseudo']) > 50) {
                $errors['pseudo'] = 'Le pseudo doit faire entre 3 et 50 caractères.';
            } elseif ($userRepository->findOneBy(['pseudo' => $old['pseudo']])) {
                $errors['pseudo'] = 'Ce pseudo est déjà utilisé.';
            }

            if ($old['age'] === '') {
                $errors['age'] = "L'âge est obligatoire.";
            } elseif (!ctype_digit($old['age'])) {
                $errors['age'] = "L'âge doit être un nombre entier.";
            } elseif ((int) $old['age'] < self::AGE_MIN || (int) $old['age'] > self::AGE_MAX) {
                $errors['age'] = "L'âge doit être compris entre " . self::AGE_MIN . ' et ' . self::AGE_MAX . ' ans.';
            }

            if ($captcha === '' || $captcha !== (string) $session->get('captcha_answer')) {
                $errors['captcha'] = 'La réponse au captcha est incorrecte.';
            }

            // Pas d'erreur : on crée le compte
            if (count($errors) === 0) {
                $password = $this->generatePassword();

                $user = new User();
                $user->setEmail($old['email']);
                $user->setPseudo($old['pseudo']);
                $user->setAge((int) $old['age']);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $entityManager->persist($user);
                $entityManager->flush();

                // Le mot de passe est affiché une seule fois sur la page de retour
                $this->addFlash('success', 'Compte créé ! Votre mot de passe : ' . $password
                    . ' (connectez-vous avec votre e-mail).');

                $back = $session->get('register_back', '/');
                $session->remove('register_back');
                $session->remove('captcha_answer');
                $session->remove('captcha_question');

                // Retour sur la page consultée avant le formulaire
                return $this->redirect($back);
            }

            // Il y a des erreurs : nouvelle question de captcha, puis on réaffiche le formulaire
            $this->createCaptcha($request);
        }

        return $this->render('registration/register.html.twig', [
            'errors' => $errors,
            'old' => $old,
            'captcha_question' => $session->get('captcha_question'),
            'back_url' => $session->get('register_back', '/'),
        ]);
    }

    /**
     * Captcha très simple : une addition. La réponse est gardée en session.
     */
    private function createCaptcha(Request $request): void
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);

        $session = $request->getSession();
        $session->set('captcha_question', $a . ' + ' . $b);
        $session->set('captcha_answer', (string) ($a + $b));
    }

    /**
     * Mot de passe aléatoire de 10 caractères (sans les caractères qui se ressemblent : 0/O, 1/l/I).
     */
    private function generatePassword(): string
    {
        $characters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Retourne la page d'où vient l'internaute (en-tête Referer).
     * On accepte seulement une page de notre site, sinon on garde l'ancienne valeur.
     */
    private function getPreviousPage(Request $request, string $default): string
    {
        $referer = $request->headers->get('referer');

        if ($referer === null) {
            return $default;
        }

        $parts = parse_url($referer);

        // Page d'un autre site, ou formulaire d'inscription lui-même : on ignore
        if (($parts['host'] ?? '') !== $request->getHost() || str_starts_with($parts['path'] ?? '', '/inscription')) {
            return $default;
        }

        $url = $parts['path'] ?? '/';
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        return $url;
    }
}
