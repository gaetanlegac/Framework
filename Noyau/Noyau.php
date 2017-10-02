<?php
namespace {
    class NOYAU {
        // ===============> PROPRIETES <===============
        public static   $Messages = [], // Liste des messages associés à leur type (erreur, info, debug)
                        $TempsExec;

        // ==========> GESTION DES MESSAGES <==========
        // Ajoute un message d'erreur si $Message est défini
        // Retourne true si une erreur est survenue sur le noyau
        public static function Erreur($Message = false, $Details = false) {
            if ($Message === false) {
                // Etat des erreurs. Compte le nombre d'occurences de "erreur"
                $GroupesMsg = @array_count_values(array_column(NOYAU::$Messages, 0));
                if (array_key_exists("erreur", $GroupesMsg))
                    return (["erreur"] > 0);
            } else {
                // Vérifie le type d'argument
                if (is_object($Message)) { // Objet Exception
                    // Affiche les details
                    foreach ($Message->getTrace() as $Infos) {
                        // Infos fonction
                        $Details .= "<span class=\"fonction\">". $Infos["function"] ."(". json_encode($Infos["args"]) . ")</span>";
                        // Infos fichier
                        $Details .= "<span class=\"fichier\">". $Infos["file"] .":".$Infos["line"] . "</span><br>";
                    }
                    $Message = $Message->getMessage();
                }
                // Ajout d'une erreur
                array_push(NOYAU::$Messages, ["erreur", $Message, $Details]);
            }
        }

        public static function Debug($Message) {
            /*if (Config\General\Debug)
                array_push(NOYAU::$Messages, ["debug", $Message]);*/
        }

        public static function AffMessages() {
            // Parcours des messages
            foreach (NOYAU::$Messages as $InfosMsg) {
                $Type       = $InfosMsg[0];
                $Message    = $InfosMsg[1];
                $Details    = (count($InfosMsg) == 3) ? $InfosMsg[2] : false;

                echo "<div class=\"". $Type . "-noyau\">". $Message .($Details ? "<div class=\"details\">". $Details ."</div>" : ""). "</div>";
            }

            // Retourne false si aucune erreur n'est trouvée
            return NOYAU::Erreur();
        }
    }
}
?>
