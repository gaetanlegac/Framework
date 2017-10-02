<?php
namespace Erreur\Objet {
    class Permission extends \Exception {
        public $Action;
        public function __construct($action) {
            $this->Action = $action;
        }
    }
    class DejaExistant extends \Exception {
        public $Commun;
        public function __construct( $DonneesCommunes ) {
            $this->Commun = $DonneesCommunes;
        }
    }
    class Introuvable extends \Exception {
        public function __construct() {}
    }
}
?>
