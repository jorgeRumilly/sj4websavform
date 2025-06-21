# SJ4WEB - SAV Request Form

**Module PrestaShop 1.7.8+ / PHP >= 7.3**  
Permet aux clients d’envoyer une demande de SAV depuis une page dédiée avec pièces jointes.

---

## Fonctionnalités

- Formulaire SAV accessible via `/sav-formulaire`
- Compatible clients connectés ou non
- Champs personnalisés :
  - Nom, prénom, email, téléphone
  - Adresse d’intervention
  - Commande associée (liste ou champ libre)
  - Type de produit concerné (checkbox stylisées)
  - Objet, message
  - Pièces jointes (1 à 5 images)
- Stockage en base des demandes
- Upload sécurisé des fichiers (chemin horodaté, nom md5)
- Anti-spam : token, honeypot, protection à venir
- Traduction XLF (`en-US`, `fr-FR`) avec domaines :
  - `Modules.Sj4websavform.Admin`
  - `Modules.Sj4websavform.Shop`

---

## Arborescence

sj4web_savform/
├── controllers/front/SavRequestController.php
├── views/templates/front/sav_request.tpl
├── translations/
│ ├── en-US/ModulesSj4websavformAdmin.en-US.xlf
│ ├── en-US/ModulesSj4websavformShop.en-US.xlf
│ ├── fr-FR/ModulesSj4websavformAdmin.fr-FR.xlf
│ └── fr-FR/ModulesSj4websavformShop.fr-FR.xlf
├── uploads/ (fichiers clients)
├── sj4web_savform.php

---

## Installation

1. Copier le dossier `sj4web_savform` dans `modules/`
2. Installer via le back-office
3. Ajouter la page `/sav-formulaire` au menu si souhaité

---

## À venir

- Validation et enregistrement BDD complet
- Affichage BO des demandes
- Notifications par mail
- Thématisation des checkboxes par picto

---

© SJ4WEB.FR
