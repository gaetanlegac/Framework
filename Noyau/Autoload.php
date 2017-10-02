<?php
namespace {
	spl_autoload_register(function($Chemin) {
		// Détection du type de classe
		$Parties = explode("\\", $Chemin);

		switch ($Parties[0]) {
			// Objets de l'ORM
			case "ORM":
				if ($Parties[1] == "Objets")
					$FichierClasse = RACINE . "/Objets/" . $Parties[2] . ".php";
				break;

			// Controleurs
			case "Controleurs":
				$FichierClasse = RACINE . "/Controleurs/" . $Parties[1] . ".php";
				break;

			// Widgets
			case "Contenu":
				if ($Parties[1] == "Widgets") {
					// Base du widget
					if (array_pop($Parties) === "Base")
						$FichierClasse = \Base\Noyau\UI . "/Widgets/" . $Parties[2] . ".php";
					// Template de widget
					else
						$FichierClasse = \Base\UI\Widgets . "/" . $Parties[2] . ".php";
				}
				break;

			// Plugins / Extensions de fonctions
			case "Plugins":
				$FichierClasse = \Base\Noyau\Plugins . "/" . $Parties[1] . ".php";
				break;
		}

		// Importation
		if (file_exists($FichierClasse))
			require_once($FichierClasse);
		else
			\NOYAU::Erreur("Le fichier de classe ".$FichierClasse. " est introuvable.");
	});
}
?>
