# Admissio - Plateforme de Recrutement Sécurisée 🚀

Bienvenue sur Admissio, une solution complète pour la gestion des concours et des candidatures.

## 🔒 Sécurité et Qualité technique

Plusieurs mécanismes de sécurité avancés ont été intégrés afin de protéger les données des utilisateurs :
- **Hachage des mots de passe** : Utilisation de `password_hash()` (Argon2 ou Bcrypt) et vérification sécurisée par `password_verify()`.
- **Protection XSS** : Toutes les données affichées sont nettoyées via `htmlspecialchars()` pour prévenir l'injection de scripts.
- **CSRF Protection** : Utilisation de tokens de session pour valider l'origine des soumissions de formulaires.
- **Réinitialisation de mot de passe** : Système sécurisé par email avec tokens uniques, temporaires et protection contre le flood.

## 📁 Gestion des Fichiers

Le système de téléchargement des pièces jointes (CV, Lettre de motivation, Diplômes) respecte des normes de sécurité strictes :
- **Vérification du format** : Limitation aux extensions autorisées (PDF, DOC, DOCX, JPG, PNG).
- **Contrôle de taille** : Limitation à 5 MB par fichier.
- **Renommage sécurisé** : Les fichiers sont renommés selon un pattern `timestamp_uniqueid` pour éviter les collisions et les informations sensibles dans les noms de fichiers.
- **Accès sécurisé** : Les téléchargements sont gérés via des scripts PHP vérifiant les autorisations de l'utilisateur.

## 📊 Gestion des Candidatures

Suivi intuitif via trois statuts majeurs :
1.  **En attente** : Candidature déposée, en cours de revue par le gérant.
2.  **Validée** : Dossier retenu pour la phase suivante.
3.  **Rejetée** : Dossier non retenu.

## 🗄️ Architecture de la Base de Données

Modèle relationnel optimisé avec intégrité référentielle (Clés étrangères) :
- `utilisateurs` : Gestion multi-rôles (Admin, Gérant, Candidat).
- `concours` : Informations détaillées sur les recrutements.
- `candidatures` : Pivot entre candidats et concours.
- `dossiers` : Métadonnées des documents envoyés.
- `champs_formulaire` & `reponses_candidature` : Système dynamique de formulaires personnalisés.
- `sessions_concours` & `demandes_session` : Planification et validation des périodes de candidature.

## 🌐 Interface Utilisateur (UX)

- **Dashboards Rôle-spécifiques** : Vues sur-mesure pour chaque acteur.
- **Feedback Interactif** : Utilisation de messages de confirmation/erreur (Alerts) clairs.
- **Design System** : Cohérence graphique via `style.css` (Premium et Moderne).

## 📧 Système d'Email

Support intégré via PHPMailer pour :
- La réinitialisation sécurisée des accès.
- La génération de tokens à durée de validité limitée (30 minutes).
