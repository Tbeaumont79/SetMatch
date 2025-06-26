<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PostFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = [];

        $admin = new User();
        $admin->setEmail('admin@setmatch.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        $users[] = $admin;

        $tennisPlayers = [
            'marie.tennis@gmail.com',
            'thomas.raquette@outlook.fr',
            'sarah.court@yahoo.fr',
            'julien.ace@hotmail.com',
            'camille.smash@gmail.com',
            'antoine.volley@free.fr',
            'laura.revers@gmail.com',
            'maxime.service@outlook.fr'
        ];

        foreach ($tennisPlayers as $email) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $manager->persist($user);
            $users[] = $user;
        }

        $tennisImages = [
            'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?w=600&h=400&fit=crop&crop=center', // Tennis court
            'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=600&h=400&fit=crop&crop=center', // Tennis ball
            'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?w=600&h=400&fit=crop&crop=center', // Tennis racket
            'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=600&h=400&fit=crop&crop=center', // Tennis match
            'https://images.unsplash.com/photo-1599391684792-079d98689031?w=600&h=400&fit=crop&crop=center', // Tennis player
            'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&h=400&fit=crop&crop=center', // Tennis equipment
            'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&h=400&fit=crop&crop=center', // Tennis court view
            'https://images.unsplash.com/photo-1566577134770-3d85bb3a9cc4?w=600&h=400&fit=crop&crop=center', // Tennis action
        ];

        $tennisMatchPosts = [
            "🎾 Quelqu'un pour un match de tennis ce weekend ? J'ai réservé un court pour samedi matin 9h au Tennis Club Central 🌞 #tennis #match #partenaire",
            "🎾 Cherche partenaire pour jouer au tennis demain soir 18h ! Court n°3 du complexe sportif. Niveau intermédiaire 💪 #tennis #match #soir",
            "🎾 Match de tennis prévu dimanche après-midi 14h. Il me manque 1 joueur pour faire un double ! Qui est motivé ? 🔥 #tennis #double #dimanche",
            "🎾 Proposition de match de tennis vendredi 17h au Tennis Club des Acacias. Niveau débutant/intermédiaire 😊 #tennis #match #vendredi",
            "🎾 Envie d'un match de tennis ce mercredi matin 10h ? J'ai accès aux courts couverts du centre sportif 🏟️ #tennis #match #mercredi",
            "🎾 Qui veut faire un match de tennis samedi 16h ? Court réservé au Tennis Club Municipal. Niveau confirmé 🎯 #tennis #match #weekend",
            "🎾 Recherche adversaire pour match de tennis jeudi 19h ! Court en terre battue disponible 🏆 #tennis #match #terrebattue",
            "🎾 Match de tennis proposé pour ce soir 20h sous les projecteurs ! Qui relève le défi ? ⚡ #tennis #match #soir #projecteurs",
            "🎾 Envie d'un petit match de tennis relaxant dimanche matin 10h ? Courts du parc municipal disponibles 🌳 #tennis #match #dimanche #détente",
            "🎾 Proposition de double en tennis mardi 18h30. On cherche 2 joueurs pour compléter l'équipe ! 👥 #tennis #double #mardi",
            "🎾 Match de tennis prévu lundi 12h pendant la pause déj' ! Court d'entreprise disponible 🏢 #tennis #match #pausedej",
            "🎾 Quelqu'un pour un match de tennis intensif samedi 8h du matin ? Parfait pour bien commencer le weekend ! 🌅 #tennis #match #intensif #weekend",
            "🎾 Proposition de match de tennis en indoor mercredi 21h. Climatisé et éclairé, parfait pour l'hiver ! ❄️ #tennis #match #indoor #hiver",
            "🎾 Cherche partenaire régulier pour matchs de tennis le jeudi soir. Niveau intermédiaire/confirmé 📅 #tennis #match #régulier #jeudi",
            "🎾 Match de tennis décontracté dimanche 15h suivi d'un apéro au club house ! Qui vient ? 🍹 #tennis #match #apéro #clubhouse"
        ];

        for ($i = 0; $i < 15; $i++) {
            $post = new Post();
            $post->setContent($tennisMatchPosts[$i]);
            $post->setAuthor($users[array_rand($users)]);

            if (rand(1, 10) <= 9) {
                $post->setImage($tennisImages[array_rand($tennisImages)]);
            }

            $randomDays = rand(0, 15);
            $randomHours = rand(0, 23);
            $randomMinutes = rand(0, 59);
            $createdAt = new \DateTimeImmutable("-{$randomDays} days +{$randomHours} hours +{$randomMinutes} minutes");
            $post->setCreatedAt($createdAt);

            $manager->persist($post);
        }

        $manager->flush();
    }
}