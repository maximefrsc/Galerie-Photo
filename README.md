# Galerie photo – Exercice 2.1 (Symfony 8.1)

## Prérequis
- **PHP 8.4 ou plus** (obligatoire pour Symfony 8.1) : `php -v`
- Extension **pdo_sqlite** activée : `php -m | grep -i sqlite`
  (Windows : dans `php.ini`, retirer le `;` devant `extension=pdo_sqlite` et `extension=sqlite3`)
- **Composer** : `composer -V`

## Installation (4 commandes, dans le dossier du projet)

```bash
composer install
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
php -S localhost:8000 -t public
```

Puis ouvrir http://localhost:8000

Pour repartir de zéro : supprimer `var/data.db` et relancer les commandes 2 et 3.

## Comptes de test (mot de passe : `password`)
`alice@test.fr`, `bob@test.fr`, `chloe@test.fr`, `david@test.fr`
(david n'a aucune photo publiée : il n'apparaît pas dans la liste de gauche)

## Fichiers à connaître (le reste est la configuration standard de Symfony)
| Fichier | Rôle |
|---|---|
| `src/Entity/User.php` | Un utilisateur : e-mail, pseudo, mot de passe, rôles |
| `src/Entity/Photo.php` | Une photo : nom du fichier, numéro d'ordre (vide = non publiée), propriétaire |
| `src/Repository/UserRepository.php` | Les utilisateurs qui ont au moins une photo publiée |
| `src/Repository/PhotoRepository.php` | Les photos publiées d'un utilisateur, triées par numéro d'ordre |
| `src/Controller/HomeController.php` | `/` : galerie au hasard (`array_rand`) ; `/galerie/{id}` : galerie choisie |
| `src/Controller/SecurityController.php` | Page `/login` (affiche l'erreur) et `/logout` |
| `config/packages/security.yaml` | Connexion par formulaire (champs `_username` / `_password`, CSRF) |
| `templates/base.html.twig` | Layout commun avec le formulaire de connexion en haut à droite |
| `templates/home/index.html.twig` | Liste des galeries à gauche + galerie au centre |
| `src/DataFixtures/AppFixtures.php` | Données de test |

## Fonctionnement en bref
1. `HomeController::index` demande la liste des utilisateurs avec photos publiées.
2. Il en choisit un au hasard avec `array_rand` et récupère ses photos publiées.
3. Twig affiche la liste des pseudos (gauche) et les photos (centre).
4. Le formulaire du header envoie e-mail + mot de passe vers `/login` : Symfony vérifie grâce à `form_login`. Si c'est faux, on arrive sur `/login` avec un message d'erreur.
