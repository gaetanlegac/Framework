<?php
namespace {
    // Gestion des erreurs fatales
    /*register_shutdown_function("fatal_handler");
    function fatal_handler() {
        // Données par défaut
        $Fichier    = "Inconnu";
        $Message    = "Erreur";
        $Numero     = E_CORE_ERROR;
        $Ligne      = 0;
        // Récupère la dernière erreur
        $Erreur = error_get_last();
        if($Erreur !== NULL) {
            // Extrait les données de l'erreur
            $Numero     = $Erreur["type"];
            $Fichier    = $Erreur["file"];
            $Ligne      = $Erreur["line"];
            $Message    = $Erreur["message"];
            // Envoi d'un mail
            // A faire

            //var_dump($Erreur);
        }
    }*/

    class Debug {
        const DossierLogs = \Base\Noyau . "/Logs";
        public static function GetFichier() {
            return Debug::DossierLogs . "/" . date("d.m.Y");
        }

        public static function ListeLogs() {
            foreach ( glob(Debug::DossierLogs."/*.*.*") as $Fichier )
                echo '<a>'. basename($Fichier) .'</a>';
        }

        public static function Tracer() {
            ob_start();
            debug_print_backtrace();
            $out = ob_get_clean();
            echo str_replace("#", "<br><br>#", $out);
            die;
        }
    }

    function dbg($Txt, $Type = "info", $Dump = false) {
        if (Config\General\Debug) {
            /* -------- Récupération des détails -------- */
            // Date
            $date = date("H:i:s");

            // Fichier actuel
            if (class_exists("\Requete", false))
                $fScript = str_replace(RACINE, "", \Requete\Infos::$Route->Adresse);
            else
                $fScript = "-";



            /* -------- Formatage texte -------- */
            if (is_array($Txt) || is_object($Txt) || $Dump) {
                ob_start();
                var_dump($Txt);
                $Txt = ob_get_clean();
            }

            /* -------- Enregistrement -------- */
            $Fichier = fopen(Debug::GetFichier(), "c");
            $Impression = <<<EOT
<tr class="log {$Type}">
    <td class="date">{$date}</td>
    <td class="emplacement">{$fScript}</td>
    <td class="contenu">{$Txt}</td>
</tr>
EOT;
            fwrite($Fichier, $Impression . file_get_contents(Debug::GetFichier()));
            fclose($Fichier);
        }
    }

    class Chrono {
        public static   $Temps = 0,
                        $Memoire = 0;

        public static function Debut() {
            Chrono::$Temps = microtime(true);
            Chrono::$Memoire = memory_get_usage();
        }

        public static function Fin() {
            $FinExec = microtime(true);
            Chrono::$Temps = $FinExec - Chrono::$Temps;
            Chrono::$Memoire = (memory_get_usage() - Chrono::$Memoire) / (1024 * 1024);
        }
    }
}
?>
