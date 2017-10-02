/**
 * Interaction avec les objets de l'orm
 * @param       {[type]} type      [description]
 * @param       {[type]} [id=null] [description]
 * @constructor
 */
function Objet (type, id = null) {
    var self = this;
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    // Propriétés
    this.Type           = type;     // Type de l'objet utlisé
    this.ID             = id;       // ID de l'objet
    this.Formulaire     = false;    // Formulaire rattaché
    this.ZonesDonnees   = {};       // Autres zones de données rattachées
    this.Proprietes     = [];       // Liste du nom des propriétés
    this.aMaj            = [];       // Nom des propriétés concernées pour l'enregistrement
    // Actions supplémentaires
    this.Actions = {
        Avant: {},
        Apres: {}
    }

    /* -----------------------------------------------------------------
    > METHODES D'INITIALISATION
    ----------------------------------------------------------------- */
    // Charge les données de l'objet via son ID
    // Si un formulaire est défini, on y met à jour les champs
    this.Charger = function(id, actionEnsuite = false) {
        // Indicateur de chargement sur le formulaire rattaché
        if (this.Formulaire)
            $(this.Formulaire).addClass("chargement");

        // Vérifie si des données ont été modifées
        this.VerifChangementsEt(function() {
            // Efface les données actuelles
            self.Nouveau();
            // Requete au serveur
            self.ID = id;
            Route("/"+ self.Type +"/"+ id, null, function(Donnees) {
                // Importe les données
                self.ImporterDonnees(Donnees);

                // Action supplémentaire permanente
                if (self.Actions.Apres.Chargement)
                    self.Actions.Apres.Chargement();
                // Action supplémentaire ephémère
                if (actionEnsuite)
                    actionEnsuite();
                // Indicateur de chargement sur le formulaire s'il est défini
                if (self.Formulaire)
                    $(self.Formulaire).removeClass("chargement");
            });
        });
    }

    // Importe les propriétés via un objet javascript
    this.ImporterDonnees = function(Donnees, ViaFormulaire = false) {
        // Si l'objet retourné n'est pas vide
        if ($.isPlainObject(Donnees) && !$.isEmptyObject(Donnees)) {
            // Allocation des proprietés
            $.each(Donnees, function(Propriete, Valeur) {
                // Assignation de la valeur
                self[Propriete] = Valeur;
                // Référencement si ce n'estpas déjà fait
                if ($.inArray(Propriete, self.Proprietes) == -1)
                    self.Proprietes.push(Propriete);
            });

            // Màj du formulaire si défini
            if (self.Formulaire && !ViaFormulaire)
                // Exporte les propriétés en tableau et défini les valeurs dans le formulaire
                $(self.Formulaire).deserialize( self.ExporterDonnees() );

            // Remplissage de la z zone de données
            self.MajZonesDonnees();
        }
    }

    /* -----------------------------------------------------------------
    > METHODES SUR LES ZONE DE DONNÉES RATTACHÉES
    ----------------------------------------------------------------- */
    // Rattache un formulaire aux données de l'objet
    this.SetFormulaire = function(Form) {
        // Mémorise l'objet
        self.Formulaire = Form;
        // Importe les données par défaut du formulaire
        self.ImporterDonnees($(self.Formulaire).ExporterDonnees(), true);

        // Evenement d'envoi
        $(self.Formulaire).off("submit").on("submit", function(e) {
            // Annulation de l'évenement initial
            e.preventDefault();
            // Enregistrement avec la fonction suplémentaire en callback
            self.Enregistrer();
        });

        // Evenement de réinitialisation
        $(self.Formulaire).find("input[type='reset']").off("click").on("click", function(e) {
            // Annulation de l'évenement initial
            e.preventDefault();
            // Nouvel article
            self.Nouveau();
        });
    }

    // Rattache une zone de données à l'objet
    this.SetZoneDonnees = function(Zone, Regles = {}) {
        this.ZonesDonnees[Zone] = Regles;
    }

    this.MajZonesDonnees = function() {
        $.each(this.ZonesDonnees, function (Zone, Regles) {
            // Parcours des données de l'element selectionne
            $.each(self.Proprietes, function(Index, Propriete) {
                // Détermine l'element concerné
                var ElemDonnee = $(Zone).find("."+Propriete);
                // Valorisation de / des élement(s) concerne(s)
                if ($(ElemDonnee).length > 0) {
                    // Détermine la valeur brute
                    var Valeur = self[Propriete];
                    // Si une règle est définie pour la propriété
                    if (Propriete in Regles)
                        Valeur = Regles[Propriete](Valeur);
                    // Affichage de la valeur
                    $(ElemDonnee).text(Valeur);
                }
            });
        });
    }

    // Initialise un nouvel article
    this.Nouveau = function(ActionEnsuite = false) {
        // Vérification si des changements ont été apportés
        self.VerifChangementsEt(function() {
            // Réinit du formulaire
            $(self.Formulaire)[0].reset();
            // Reinitialisation des prorpiétés
            $.each(self.Proprietes, function(Index, Propriete) {
                delete self[Propriete];
            });
            // Mise à jour des données de l'objet
            self.ImporterDonnees($(self.Formulaire).ExporterDonnees(), true);
            // Lancement de la fonction supplémentaire
            self.Actions.Apres.Nouveau();
            if (ActionEnsuite)
                ActionEnsuite();
        });
    }

    // Vérifie si des changements ont été faits sur les données du formulaire
    this.VerifChangementsEt = function(ActionEnsuite) {
        // Liste des propriétés changées
        var Changements = [];

        // Si un formulaire a bien été rattaché
        if (this.Formulaire) {
            // Parcours de la valeur de chaque champ du formulaire
            var DonneesForm = $(this.Formulaire).ExporterDonnees();
            var DonneesObjet = self.ExporterDonnees(); // Filtrage des objets etc ... en ID

            $.each(DonneesForm, function(Propriete, Valeur) {
                // Vérif existance du parametre
                if (Propriete in DonneesObjet) {
                    // Comparaison
                    if (DonneesObjet[Propriete] !== Valeur)
                        // Référencement du nom de la propriété changée
                        Changements.push(Propriete);
                } else
                    console.log("Attention: Le champ "+ Propriete +" du formulaire ne correspond à aucune propriété de l'objet "+ self.Type);
            });
        }

        // Message d'avertissement s'il y a eu des changements
        if (Changements.length > 0)
            Dialogue("Sauvegarder ?", "Des données ont été modifiées ("+ Changements.join(", ") +").<br>Faut-il les enregistrer avant de continuer ?", {
                Oui : function() {
                    self.Enregistrer();
                },
                Non : false
            }, "avertissement", ActionEnsuite);
        // Sinon on passe directement à la suite
        else ActionEnsuite();
    }

    // Retourne le champ du formulaire correspondant à la propriété donnée
    // False si aucun champ correspondant
    this.GetChamp = function(Propriete) {
        return $(this.Formulaire).find("[name='"+ Propriete +"']");
    }

    /* -----------------------------------------------------------------
    > METHODES DE PERSISTANCE
    ----------------------------------------------------------------- */
    // Exporte les propriétés de l'objet sous forme de tableau associatif
    this.ExporterDonnees = function() {
        var Donnees = {};
        // Ajout de l'id si défini
        if (this.ID)
            Donnees.ID = this.ID;
        // Parcours de chaque propriété
        for (var i in this.Proprietes) {
            var Propriete = this.Proprietes[i];
            var Valeur = this[Propriete];
            // Si la valeur est un objet, on récupère son ID
            if ($.isPlainObject(Valeur))
                Valeur = Valeur.ID;
            // Référencement
            Donnees[Propriete] = Valeur;
        }
        // Retour
        return Donnees;
    }

    this.Enregistrer = function(actionEnsuite = false) {
        // Si un formulaire a été rattaché
        if (this.Formulaire) {
            var donneesForm = $(this.Formulaire).ExporterDonnees();
            // Charge les données des champs dans l'objet
            this.ImporterDonnees(donneesForm, true);
            // Définition des données à mettre à jour
            this.aMaj = Object.keys(donneesForm);
        }

        // Récupèration des données à màj
        if (self.aMaj.length > 0) {
            var donneesAmaj = {};
            $.each(this.aMaj, function(i, Propriete) {
                donneesAmaj[Propriete] = self[Propriete];
            });

            // Requete sur le serveur
            Route("/"+ self.Type +"/Enregistrer", donneesAmaj, function(Donnees) {
                // Si les nouvelles données sont bien retournées
                if ($.isPlainObject(Donnees)) {
                    // Reinit des données à màj
                    self.aMaj = [];
                    // Importation
                    self.ImporterDonnees(Donnees);
                    // Action supplémentaire permanente
                    if (self.Actions.Apres.Enregistrement)
                        self.Actions.Apres.Enregistrement();
                    // Action supplémentaire ephémère
                    if (actionEnsuite)
                        actionEnsuite();
                }
            }, this.Formulaire);
        }
    }

    /* -----------------------------------------------------------------
    > METHODES DE GESTION DES EVENEMENTS
    ----------------------------------------------------------------- */
    this.SetActionsApres = function(Actions) {
        // Correction du tableau d'actions
        Actions = Actions || {};
        if (!$.isPlainObject(Actions))
            return;

        // Parcours de chaque action
        $.each(Actions, function(NomAction, Action) {
            // Assignation
            self.Actions.Apres[NomAction] = Action;
        });
    }


    /* -----------------------------------------------------------------
    > ACCESSEURS
    ----------------------------------------------------------------- */
    this.Set = function(Propriete, Valeur) {
        if (Propriete in this.Proprietes) {
            this[Propriete] = Valeur;
            this.aMaj.push(Propriete);
        } else
            console.log("La propriété " + Propriete + " est incorrecte pour un objet de type " + this.Type);
    }

    /* -----------------------------------------------------------------
    > INITIALISATION
    ----------------------------------------------------------------- */
    // Si un id a été défini
    if (id)
        this.Charger(id);
}
