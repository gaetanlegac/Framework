<?php
/* ------------------------------------------------------
> CHEMINS DE BASE
------------------------------------------------------ */
namespace Base {
const
    Noyau       = RACINE . "Noyau",
    Controleurs = RACINE . "Controleurs",
    Objets      = RACINE . "Objets",
    UI          = RACINE . "Interface",
    Res         = "Interface/Ressources";
}

namespace Base\Noyau {
const
    ORM     = \Base\Noyau . "/ORM",
    UI      = \Base\Noyau . "/Interface",
    Boot    = \Base\Noyau . "/Bootloader",
    Plugins = \Base\Noyau . "/Plugins";
}

namespace Base\Noyau\ORM {
const
    BDD     = \Base\Noyau\ORM . "/BDD";
}

namespace Base\UI {
const
    Contenu = \Base\UI . "/Contenu",
    Widgets = \Base\UI . "/Widgets",
    Erreurs = \Base\UI . "/Erreurs";
}

namespace Base\Res {
const
    JS      = \Base\Res . "/JS",
    CSS     = \Base\Res . "/CSS";
}
?>
