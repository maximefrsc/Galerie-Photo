<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Page d'accueil : on affiche la galerie d'un utilisateur tiré au hasard.
     */
    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository, PhotoRepository $photoRepository): Response
    {
        // Liste des utilisateurs qui ont publié des photos
        $users = $userRepository->findWithPublishedPhotos();

        $gallery = null;
        $photos = [];

        if (count($users) > 0) {
            // On choisit un utilisateur au hasard dans la liste
            $gallery = $users[array_rand($users)];
            $photos = $photoRepository->findPublishedByUser($gallery);
        }

        return $this->render('home/index.html.twig', [
            'users' => $users,
            'gallery' => $gallery,
            'photos' => $photos,
        ]);
    }

    /**
     * Quand on clique sur un pseudo dans la liste de gauche,
     * on affiche la galerie de cet utilisateur.
     */
    #[Route('/galerie/{id}', name: 'app_gallery', requirements: ['id' => '\d+'])]
    public function gallery(
        int $id,
        UserRepository $userRepository,
        PhotoRepository $photoRepository
    ): Response {
        $user = $userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException('Galerie introuvable.');
        }

        return $this->render('home/index.html.twig', [
            'users' => $userRepository->findWithPublishedPhotos(),
            'gallery' => $user,
            'photos' => $photoRepository->findPublishedByUser($user),
        ]);
    }
}
