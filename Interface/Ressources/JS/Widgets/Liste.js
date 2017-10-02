/**
 * Détermine différente actions sur les widgets de liste
 * telles que la synchronisation des données avec la base de données
 * @param       JqueryObject Element [description]
 * @constructor
 */
function Liste(Element) {
    var self = this;
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    // Obligatoires
    this.Element        = Element;
    this.nom            = $(Element).attr("id");

    // Optionnel
    this.Selection      = {};
    this.ajax           = false;
    this.Options        = { // Options qui seront passées dans les éventuelles requetes ajax
        criteres:   {},
        conteneur:  null
    };

    // Evenements permanents
    this.Actions = {
        Apres: {
            Chargement: false,
            Selection: false
        },
        Elements: {}
    }

    /* -----------------------------------------------------------------
    > METHODES PRINCIPALES
    ----------------------------------------------------------------- */
    /**
     * Initialise des propriétés à partir de l'état des élements html
     */
    this.RecupInfos = function() {
        // Element sélectionné
        this.Selection = this.GetSelection();

        // Données via attribut data
        $.each(this.Element.data(), function(Propriete, Valeur) {
            // Option temporaire
            if ( Propriete in self.Options )
                self.Options[Propriete] = Valeur;
            // Propriété définitive
            else if (Propriete in self)
                self[Propriete] = Valeur;
            // Non-reconnu
            else
                console.log("Oups .. Une option non-reconnue ("+ Propriete +") a été passée dans un widget Liste");
        });
    }

    /**
     * Recharge la liste des élement via AJAX
     * @param  {int}        ID de l'élement sélectionné par défaut
     * @param  {Boolean}    Fonction à éxecuter après le rechargement
     * @param  {Array}      Tableau associatif des options supplémentaires à passer
     */
    this.Actualiser = function ( options = {}, actionEnsuite   = false ) {
        // Si une opération n'est pas déjà en cours sur la liste
        // Et que les fonctionnalités ajax sont activées
        if ( ! this.Element.hasClass("chargement") && self.ajax ) {
            // Mémorisation des propriétés
            if ( ! $.isEmptyObject( options ) )
                this.Options = $.extend(this.Options, options);

            // Chargement de la liste
            Route(self.ajax, this.Options, function(Reponse) {
                // Affichage des élements
                $(self.Element).html(Reponse);

                // Action permanente après chargement
                if (self.Actions.Apres.Chargement)
                    self.Actions.Apres.Chargement( self.Element );
                // Action ponctuelle
                if (actionEnsuite)
                    actionEnsuite();
                // Init des actions
                self.InitActions();
            }, this.Element);
        }
    }

    // Sélectionne un élement de la liste via son ID
    // Si id = false, réinitialise la sélection
    this.Selectionner = function(Type, Id, ActionEnsuite = false, PasDaction = false) {
        // On arrête si aucun type n'est spécifié
        if (!Type) return;
        // Déselectionne l'ancien élement
        $("#"+this.nom+" ."+ Type +".actuel").removeClass("actuel");

        // Si un Id a été défini
        Id = Id || null;
        if (Id) {
            // Détermine le nouvel élement
            var Element = $("#"+this.nom + " #"+Type+Id);
            if (Element.length == 0)
                return;
            // ... Et le sélectionne s'il existe bien
            Element.addClass("actuel");

            // Remplissage des zones de données
            var ZoneDonnees = $(".Donnees_"+ self.nom);
            if (ZoneDonnees.length > 0) {
                // Récupèration des données de l'élement
                var DonneesElem = $(Element).data();

                // Parcours des données de l'element selectionne
                $.each(DonneesElem, function(Propriete, Valeur) {
                    // Détermine l'element correspondant à la donnée du type de l'élement actuel
                    var ElemDonnee = ZoneDonnees.find("."+Propriete+"_"+Type);
                    // Valorisation de / des élement(s) concerne(s)
                    if ($(ElemDonnee).is("input"))
                        $(ElemDonnee).val(Valeur);
                    else
                        $(ElemDonnee).text(Valeur);
                });
            }

            // Remplissage de la barre d'actions sur l'élement
            var BarreActions = $(".Actions_"+ self.nom);
            if ($(BarreActions).length > 0)
                self.AffActionselements(BarreActions);

            // Lancement des actions si la sélection est non-nulle
            if (Id !== null && !PasDaction) {
                // Lance l'action supplémentaire permanente
                if (this.Actions.Apres.Selection)
                    this.Actions.Apres.Selection(Type, Id);
                // Lance l'action supplémentaire temporaire
                if (ActionEnsuite)
                    ActionEnsuite(Id);
            }
        }

        // Mémorise l'élement
        self.Selection[Type] = Id;
    }

    /**
     * Retourne l'ID de l'element actuellement sélectionné
     * @return int|bool ID de l'élement sélectionne. False si aucun élement
     */
    this.GetSelection = function(etat = "actuel") {
        var Element = $(self.Element).find("." + etat);
        if ($(Element).length > 0)
            return $(Element).data();
        else
            return false;
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

        // Si une action de chargement vient d'être définie, on la lance
        if ("Chargement" in Actions.Apres)
            this.Actions.Apres.Chargement(self.Element);

        // Initialisation des events
        this.InitActions();
    }

    // Enregistrement des actions auprès des évenements DOM
    this.InitActions = function() {
        // Selection
        $("#"+self.nom+" > li").off("click").on("click", function() {
            // Récupère les données de l'objet pour déterminer le type et l'id
            var DonneesObjet = $(this).data();
            // Désélectionne l'ancienne sélection, sélectionne l'actuel
            self.Selectionner(DonneesObjet.type, DonneesObjet.id);
        });
    }

    /* -----------------------------------------------------------------
    > INITIALISATION DE L'OBJET
    ----------------------------------------------------------------- */
    // Récupèration des informations à partir de l'élement html
    this.RecupInfos();
    // Suppression de l'objet actuel en même temps que l'élement dom
    $(this.Element).bind("destroyed", function() {
        delete Widgets[self.nom];
    });
}
