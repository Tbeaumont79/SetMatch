# 🎾 SetMatch - Plateforme Social Tennis

SetMatch est une plateforme sociale dédiée au tennis permettant aux joueurs de se connecter, partager des posts et communiquer en temps réel via un système de chat intégré.

## 📋 Table des Matières

-   [🚀 Fonctionnalités](#-fonctionnalités)
-   [🏗️ Architecture](#️-architecture)
-   [🛠️ Technologies](#️-technologies)
-   [📦 Installation](#-installation)
-   [🎯 Utilisation](#-utilisation)
-   [🏛️ Principes SOLID](#️-principes-solid)
-   [📁 Structure du Projet](#-structure-du-projet)
-   [🔧 Configuration](#-configuration)
-   [🧪 Tests](#-tests)
-   [🤝 Contribution](#-contribution)

## 🚀 Fonctionnalités

### 👥 Gestion des Utilisateurs

-   **Inscription/Connexion** sécurisée avec Symfony Security
-   **Profils utilisateurs** avec avatars personnalisables
-   **Authentification** par email et mot de passe

### 📝 Système de Posts

-   **Création de posts** avec contenu texte et images
-   **Upload d'images** optimisé avec Vich Uploader et Liip Imagine
-   **Timeline** des posts avec pagination
-   **Gestion des médias** sécurisée

### 💬 Chat Temps Réel

-   **Conversations privées** entre utilisateurs
-   **Messages instantanés** via Mercure Hub
-   **Interface moderne** avec DaisyUI
-   **Recherche d'utilisateurs** pour démarrer des conversations
-   **Historique des messages** persistant

### 🎨 Interface Utilisateur

-   **Design responsive** avec TailwindCSS et DaisyUI
-   **Composants réutilisables** Stimulus
-   **Expérience utilisateur** optimisée
-   **Thème moderne** et accessible

## 🏗️ Architecture

SetMatch suit une **architecture hexagonale** avec les **principes SOLID** pour garantir la maintenabilité et l'extensibilité.

### 🎯 Couches Architecturales

```
┌─────────────────────────────────────────────────────────────┐
│                    PRÉSENTATION                             │
├─────────────────────────────────────────────────────────────┤
│  Controllers │  Templates │  Stimulus Controllers           │
│  (Symfony)   │  (Twig)    │  (JavaScript)                   │
├─────────────────────────────────────────────────────────────┤
│                    APPLICATION                              │
├─────────────────────────────────────────────────────────────┤
│  Services    │  Contracts │  Event Listeners               │
│  (Business)  │  (Interfaces) │ (Domain Events)             │
├─────────────────────────────────────────────────────────────┤
│                    DOMAINE                                  │
├─────────────────────────────────────────────────────────────┤
│  Entities    │  Repositories │  Exceptions                 │
│  (Doctrine)  │  (Data Access) │ (Domain Logic)             │
├─────────────────────────────────────────────────────────────┤
│                  INFRASTRUCTURE                            │
├─────────────────────────────────────────────────────────────┤
│  Database    │  Mercure Hub │  File System                │
│  (MySQL)     │  (WebSocket) │  (Uploads)                  │
└─────────────────────────────────────────────────────────────┘
```

### 🔄 Flux de Données

```mermaid
graph TD
    A[Utilisateur] --> B[Controller]
    B --> C[Service Layer]
    C --> D[Repository]
    D --> E[Database]

    C --> F[Mercure Hub]
    F --> G[WebSocket]
    G --> H[Client JavaScript]

    C --> I[Event Listener]
    I --> J[Business Logic]
```

## 🛠️ Technologies

### Backend

-   **PHP 8.4** - Langage principal
-   **Symfony 7.3** - Framework web
-   **Doctrine ORM** - Mapping objet-relationnel
-   **MySQL** - Base de données
-   **Mercure** - Communication temps réel

### Frontend

-   **TailwindCSS** - Framework CSS utilitaire
-   **DaisyUI** - Composants UI
-   **Stimulus** - Framework JavaScript léger
-   **Vite** - Build tool moderne

### DevOps

-   **Docker** - Conteneurisation
-   **Composer** - Gestionnaire de dépendances PHP
-   **NPM** - Gestionnaire de dépendances JavaScript

## 📦 Installation

### Prérequis

-   PHP 8.4+
-   Node.js 18+
-   MySQL 8.0+
-   Docker (optionnel)

### 1. Cloner le Projet

```bash
git clone https://github.com/votre-username/setmatch.git
cd setmatch
```

### 2. Installer les Dépendances

```bash
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
```

### 3. Configuration

```bash
# Copier le fichier d'environnement
cp .env .env.local

# Configurer la base de données dans .env.local
DATABASE_URL="mysql://user:password@127.0.0.1:3306/setmatch"
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET="votre-secret-jwt"
```

### 4. Base de Données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load
```

### 5. Assets

```bash
# Compiler les assets pour le développement
npm run dev

# Ou pour la production
npm run build
```

### 6. Mercure Hub (Chat Temps Réel)

```bash
# Démarrer Mercure avec Docker
docker-compose -f docker-compose.mercure.yml up -d

# Ou utiliser le binaire Mercure directement
./mercure run --config mercure.conf
```

### 7. Lancer le Serveur

```bash
# Serveur de développement Symfony
symfony server:start

# Ou avec PHP built-in server
php -S localhost:8000 -t public/
```

## 🎯 Utilisation

### 🏠 Page d'Accueil

-   Accédez à `http://localhost:8000`
-   Consultez la timeline des posts
-   Utilisez le widget de chat en bas à droite

### 👤 Inscription/Connexion

-   Créez un compte via `/register`
-   Connectez-vous via `/login`
-   Gérez votre profil

### 📝 Création de Posts

-   Cliquez sur "Nouveau Post"
-   Ajoutez du texte et/ou des images
-   Publiez pour partager avec la communauté

### 💬 Chat

-   Cliquez sur l'icône de chat
-   Recherchez un utilisateur
-   Démarrez une conversation
-   Recevez les messages en temps réel

## 🏛️ Principes SOLID

Notre architecture respecte scrupuleusement les principes SOLID :

### 🎯 Single Responsibility Principle (SRP)

Chaque classe a une responsabilité unique :

```php
// ✅ Validation uniquement
class EntityValidationService implements ValidatorInterface
{
    public function validateEntity(object $entity): void { /* ... */ }
}

// ✅ Formatage uniquement
class ApiDataFormatterService implements DataFormatterInterface
{
    public function formatUserForApi(User $user): array { /* ... */ }
}

// ✅ Gestion des chats uniquement
class ChatManagementService implements ChatManagementInterface
{
    public function createOrGetExistingChat(User $initiator, User $participant): Chat { /* ... */ }
}
```

### 🔓 Open/Closed Principle (OCP)

Ouvert à l'extension, fermé à la modification :

```javascript
// Classe de base extensible
export default class BaseChatController extends Controller {
    onChatOpened(chatId) {
        // Hook pour les classes filles
    }
}

// Extension sans modification
export default class MercureChatController extends BaseChatController {
    onChatOpened(chatId) {
        super.onChatOpened(chatId);
        this.connectToMercure(chatId); // Fonctionnalité ajoutée
    }
}
```

### 🔄 Liskov Substitution Principle (LSP)

Les implémentations sont interchangeables :

```php
// Interface respectée par toutes les implémentations
interface DataFormatterInterface
{
    public function formatUserForApi(User $user): array;
}

// Toutes les implémentations respectent le contrat
class ApiDataFormatterService implements DataFormatterInterface { /* ... */ }
class JsonDataFormatterService implements DataFormatterInterface { /* ... */ }
```

### 🎭 Interface Segregation Principle (ISP)

Interfaces spécialisées et cohésives :

```php
// Interfaces spécifiques et focalisées
interface ValidatorInterface
{
    public function validateEntity(object $entity): void;
}

interface PersistenceInterface
{
    public function persist(object $entity): void;
    public function flush(): void;
}

interface ChatManagementInterface
{
    public function createOrGetExistingChat(User $initiator, User $participant): Chat;
}
```

### 🔄 Dependency Inversion Principle (DIP)

Dépendance sur les abstractions :

```php
class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatManagementInterface $chatManagementService, // Interface
        private readonly DataFormatterInterface $dataFormatter,          // Interface
        private readonly ChatService $chatService
    ) {}
}
```

## 📁 Structure du Projet

```
setmatch/
├── 📁 assets/                          # Frontend assets
│   ├── 📁 controllers/                 # Stimulus controllers
│   │   ├── base_chat_controller.js     # Contrôleur de base (SRP)
│   │   ├── mercure_chat_controller.js  # Extension Mercure (OCP)
│   │   └── posts_controller.js         # Gestion des posts
│   ├── app.css                         # Styles principaux
│   └── app.js                          # Point d'entrée JavaScript
│
├── 📁 config/                          # Configuration Symfony
│   ├── 📁 packages/                    # Configuration des bundles
│   ├── routes.yaml                     # Routes principales
│   └── services.yaml                   # Services et DI
│
├── 📁 migrations/                      # Migrations de base de données
│
├── 📁 public/                          # Dossier web public
│   ├── 📁 uploads/                     # Fichiers uploadés
│   └── index.php                       # Point d'entrée
│
├── 📁 src/                             # Code source PHP
│   ├── 📁 Contract/                    # Interfaces (DIP)
│   │   ├── ValidatorInterface.php      # Validation
│   │   ├── DataFormatterInterface.php  # Formatage
│   │   ├── ChatManagementInterface.php # Gestion chat
│   │   └── PersistenceInterface.php    # Persistance
│   │
│   ├── 📁 Controller/                  # Contrôleurs web
│   │   ├── ChatController.php          # API Chat (SOLID)
│   │   ├── HomeController.php          # Page d'accueil
│   │   └── UserPostsController.php     # Gestion posts
│   │
│   ├── 📁 Entity/                      # Entités Doctrine
│   │   ├── User.php                    # Utilisateur
│   │   ├── Post.php                    # Publication
│   │   ├── Chat.php                    # Conversation
│   │   └── Message.php                 # Message
│   │
│   ├── 📁 Service/                     # Couche métier (SRP)
│   │   ├── EntityValidationService.php # Validation (SRP)
│   │   ├── ApiDataFormatterService.php # Formatage (SRP)
│   │   ├── ChatManagementService.php   # Gestion chat (SRP)
│   │   ├── DoctrinePersistenceService.php # Persistance (SRP)
│   │   ├── ChatService.php             # Messages & Mercure
│   │   └── PostService.php             # Logique posts
│   │
│   ├── 📁 Repository/                  # Accès aux données
│   └── 📁 Security/                    # Sécurité
│
├── 📁 templates/                       # Templates Twig
│   ├── 📁 chat/                        # Templates chat
│   │   ├── list.html.twig              # Liste conversations
│   │   ├── show.html.twig              # Vue conversation
│   │   └── mercure_widget.html.twig    # Widget temps réel
│   └── base.html.twig                  # Template de base
│
├── compose.yaml                        # Docker Compose
├── docker-compose.mercure.yml          # Mercure Hub
├── package.json                        # Dépendances NPM
├── composer.json                       # Dépendances PHP
└── vite.config.js                      # Configuration Vite
```

## 🔧 Configuration

### Variables d'Environnement

```bash
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/setmatch"

# Mercure (Chat temps réel)
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET="votre-secret-jwt-tres-long-et-securise"

# Upload de fichiers
UPLOAD_MAX_SIZE="2M"
UPLOAD_ALLOWED_EXTENSIONS="jpg,jpeg,png,webp,avif"

# Environnement
APP_ENV=dev
APP_SECRET=votre-secret-application
```

### Services Configurés

```yaml
# config/services.yaml
services:
    # Validation (SRP)
    App\Contract\ValidatorInterface:
        alias: App\Service\EntityValidationService

    # Formatage (SRP)
    App\Contract\DataFormatterInterface:
        alias: App\Service\ApiDataFormatterService

    # Gestion Chat (SRP)
    App\Contract\ChatManagementInterface:
        alias: App\Service\ChatManagementService

    # Persistance (DIP)
    App\Contract\PersistenceInterface:
        alias: App\Service\DoctrinePersistenceService
```
# Tous les tests
php bin/phpunit
```

## 🚀 Déploiement

### Production

1. **Variables d'environnement** de production
2. **Optimisation des assets** : `npm run build`
3. **Cache Symfony** : `php bin/console cache:clear --env=prod`
4. **Permissions** : Configurer les permissions des dossiers
5. **HTTPS** : Configurer SSL/TLS
6. **Mercure** : Déployer le hub Mercure

### Docker

```bash
# Construction des images
docker-compose build

# Démarrage des services
docker-compose up -d

# Mercure Hub
docker-compose -f docker-compose.mercure.yml up -d
```

## 🤝 Contribution

### Guidelines

1. **Respecter les principes SOLID**
2. **Suivre les conventions Symfony**
3. **Écrire des tests** pour les nouvelles fonctionnalités
4. **Documenter** les changements importants
5. **Utiliser des commits** descriptifs

### Processus

1. Fork le projet
2. Créer une branche feature : `git checkout -b feature/nouvelle-fonctionnalite`
3. Committer les changements : `git commit -m "Ajout nouvelle fonctionnalité"`
4. Pousser la branche : `git push origin feature/nouvelle-fonctionnalite`
5. Ouvrir une Pull Request

---

## 📞 Support

Pour toute question ou problème :

-   📧 **Email** : support@setmatch.com
-   🐛 **Issues** : [GitHub Issues](https://github.com/votre-username/setmatch/issues)
-   📖 **Documentation** : [Wiki du projet](https://github.com/votre-username/setmatch/wiki)

---
