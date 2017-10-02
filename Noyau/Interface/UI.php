<?php
namespace Dialogue {
    class Infos {
        protected
            $Titre, $Message, $Type, $Boutons
        ;

        function __construct(string $titre, $message, $boutons = false) {
            // Titre
            $this->Titre = $titre;
            // Message
            $this->Message = strval($message); // Force le toString s'il s'agit d'un objet (ex: widget)
            // Boutons
            $this->Boutons = $boutons;
        }

        public function Rendre($Format = \Contenu\Format\Brut) {
            // Rendu final
            switch ( $Format ) {
                case \Contenu\Format\Page:
                case \Contenu\Format\Brut:
                    return <<<EOT
<div class="msg-{$this->Type}">
    {$this->Message}
</div>
EOT;
                    break;
                case \Contenu\Format\Json:
                    return [
                        "titre"     => $this->Titre,
                        "contenu"   => $this->Message,
                        "type"      => $this->Type,
                        "boutons"   => $this->Boutons
                    ];
                    break;
            }
        }
    }

    class Erreur extends \Dialogue\Infos {
        protected $Type = \Dialogue\Type\Erreur;

        function __construct(string $titre, $message, $boutons = false) {
            // Conversion du message s'il s'agit d'une Exception
            if ( is_object($message) )
                $message = $message->getMessage();

            // Constructeur original
            parent::__construct($titre, $message, $boutons);
        }
    }
}

namespace Dialogue\Type {
const
    Info    = "info",
    Erreur  = "erreur";
}

// Commandes Javascript
namespace Javascript {
    class Code {
        private
            $Code;

        function __construct($code) {
            $this->Code = $code;
        }

        public function Rendre($Type) {
            return $this->Code;
        }
    }

    function Redirection($adresse) {
        return new Code('window.location = "'. $adresse .'";');
    }
}
?>
