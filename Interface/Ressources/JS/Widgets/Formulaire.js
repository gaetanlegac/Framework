/**
 * Détermine différente actions sur les widgets de formulaire
 * @param       JqueryObject Element [description]
 * @constructor
 */
function Formulaire(Element) {
    var self = this;
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    // Obligatoires
    this.Element        = Element;
    this.Nom            = $(Element).attr("id");
    this.Route          = $(Element).attr("action").replace("./?", "");

    // Optionnel
    this.Options        = {};       // Options qui seront passées dans les éventuelles requetes ajax

    // Evenements permanents
    this.Actions = {
        Apres: {
            Validation: false
        }
    }

    /* -----------------------------------------------------------------
    > EVENEMENTS
    ----------------------------------------------------------------- */
    this.SetActions = function(Actions) {
        // Correction du tableau d'actions
        Actions = Actions || {};
        if (!$.isPlainObject(Actions))
            return;

        // Parcours de chaque moment d'action
        $.each(Actions, function(NomMoment, ActionsMoment) {
            // Parcours de chaque action
            $.each(ActionsMoment, function(NomAction, Action) {
                // Mémorisation
                self.Actions[NomMoment][NomAction] = Action;
            });
        });

        // Initialisation des events
        this.InitActions();
    }

    // Enregistrement des actions auprès des évenements DOM
    this.InitActions = function() {
        // Selection
        $(this.Element).off("submit").on("submit", function(e) {
            // Annulation de l'évenement initial
            e.preventDefault();

            // Envoi des données au serveur
            Route(self.Route, $(self.Element).ExporterDonnees(), function(Retour) {
                if (Retour) {
                    // Action permanente
                    if (self.Actions.Apres.Validation)
                        self.Actions.Apres.Validation();

                    // Si le formulaire est contenu dans une boite de dialogue, un retour positif fermera cette dernière
                    var DialCont = $(self.Element).parents(".Dialogue");
                    if ($(DialCont).length)
                        Dialogues[ $(DialCont).attr("id") ].Fermer(true, Retour);
                }
            });
        });
    }

    /* -----------------------------------------------------------------
    > INITIALISATION DE L'OBJET
    ----------------------------------------------------------------- */
    // Initialise les évenements
    this.InitActions();
}
