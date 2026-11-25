<?php

namespace App\DataFixtures;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de test : 3 utilisateurs avec des photos publiées
 * et 1 utilisateur sans photo publiée (il n'apparaîtra pas dans la liste).
 * Mot de passe de tous les comptes : "password"
 */
class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $pseudos = ['alice', 'bob', 'chloe', 'david'];
        $photoNumber = 1;

        foreach ($pseudos as $pseudo) {
            $user = new User();
            $user->setEmail($pseudo . '@test.fr');
            $user->setPseudo($pseudo);
            $user->setAge(25);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);

            // david a une photo NON publiée (position vide)
            for ($i = 1; $i <= 3; $i++) {
                $photo = new Photo();
                $photo->setFilename('photo' . $photoNumber . '.svg');
                $photo->setPosition($pseudo === 'david' ? null : $i);
                $photo->setUser($user);
                $manager->persist($photo);
                $photoNumber++;
            }
        }

        $manager->flush();
    }
}
