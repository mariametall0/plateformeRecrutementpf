# Scripts de Maintenance

Ce dossier contient les scripts utilitaires et de maintenance pour la plateforme de recrutement. Ces scripts ne sont **pas** nécessaires en production.

## Liste des scripts

### Installation & Configuration
- **`install_missing_tables.php`** - Applique les migrations SQL manquantes à la base de données
- **`create_entretiens_table.php`** - Crée la table `entretiens` si elle n'existe pas
- **`create_messages_table.php`** - Crée la table `messages_internes` si elle n'existe pas
- **`create_table_profils_candidats.php`** - Crée la table `profils_candidats` si elle n'existe pas

### Gestion des utilisateurs
- **`create_user.php`** - Crée manuellement un nouvel utilisateur (administrateur, gérant ou candidat)
- **`activate_all_gerants.php`** - Active tous les gérants en une seule opération
- **`ensure_admin.php`** - S'assure qu'au moins un compte administrateur existe

### Réinitialisation & Migration
- **`reset_admin.php`** - Réinitialise les credentials de l'administrateur par défaut
- **`set_admin.php`** - Définit un utilisateur comme administrateur
- **`migrate_utilisateurs_photo.php`** - Migre les photos utilisateur vers la nouvelle structure

### Normalisation
- **`normalize_paths.php`** - Normalise les chemins de fichiers dans la base de données

## Usage

```bash
# Exemples d'utilisation
php install_missing_tables.php
php create_user.php
php reset_admin.php
```

## ⚠️ Avertissement

Ces scripts modifient directement la base de données. **À utiliser avec prudence en développement uniquement.**
