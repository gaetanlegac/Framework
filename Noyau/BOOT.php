<?php
/* ------------------------------------------------------
> COMPOSANTS PRINCIPAUX DU NOYAU
------------------------------------------------------ */
// Configuration du site
require_once(RACINE ."Config/General.php");
require_once(RACINE ."Config/Chemins.php");

// Méthodes utiles
require_once(Base\Noyau ."/Debug.php");
require_once(Base\Noyau ."/Noyau.php");
require_once(Base\Noyau ."/Outils.php");
require_once(Base\Noyau ."/Autoload.php");

/* ------------------------------------------------------
> ORM
------------------------------------------------------ */
// Gestion des données
require_once(Base\Noyau\ORM ."/Donnees.php");
require_once(Base\Noyau\ORM ."/Erreurs.php");
require_once(Base\Noyau\ORM\BDD ."/Connexion.php");
require_once(Base\Noyau\ORM\BDD ."/BaseRequete.php");
require_once(Base\Noyau\ORM\BDD ."/Requetes.php");
// Objets
require_once(Base\Noyau\ORM ."/Objets.php");
require_once(Base\Noyau\ORM ."/Groupe.php");
require_once(Base\Noyau\ORM ."/Utilisateur.php");

/* ------------------------------------------------------
> REQUETES / ROUTAGE / CONTENU
------------------------------------------------------ */
// Outils graphiques
require_once(Base\Noyau\UI ."/UI.php");
require_once(Base\Noyau\UI ."/Widgets.php");
require_once(Base\Noyau\UI ."/Templates.php");

// Gestion du contenu demandé
require_once(Base\Noyau\Boot ."/Session.php");
require_once(Base\Noyau\Boot ."/Requete.php");
require_once(Base\Noyau\Boot ."/Routage.php");
require_once(Base\Noyau\Boot ."/Controleur.php");
require_once(Base\Noyau\Boot ."/Contenu.php");
?>
