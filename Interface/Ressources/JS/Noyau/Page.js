/* -----------------------------------------------------------------
> GLOBALES
----------------------------------------------------------------- */
// Stocke la position actuelle du curseur
var Curseur = { x: 0, y: 0 }

/* -----------------------------------------------------------------
> INITIALISATION
----------------------------------------------------------------- */
// Action au chargement de chaque page
$(document).ready(function() {
    // Enregistrement de la position du curseur si on a défini un menu contextuel
    if ($("#MenuContextuel").length == 1)
        $(document).mousemove(function(e) {
            Curseur.x = e.pageX;
            Curseur.y = e.pageY;
        }).mouseup(function(e) {
            $("#MenuContextuel").hide();
            $(".context").removeClass("context");
        });

    // Initialisation du contenu
    $("body").Initialiser();
});

/* -----------------------------------------------------------------
> BOITE DE DIALOGUE
----------------------------------------------------------------- */
// Affiche une fenetre popup de dialogue.
// Titre et message sont des chaines obligatoires
// Si message est préfixé de "url:"" , le contenu du message sera le contenu du fichier indiqué
// boutons est un tableau associant:
//  - le nom du bouton à son action en javascript si message est un message brut
//  - les données POST à envoyer à l'url précisée dans message si ce sernier est préfixé de "url:"
function InfosDialogue(Id, titre, contenu, boutons = false, type = false, actionFin = false) {
    var self = this;

    /* -------- Infos principales -------- */
    self.ID = Id;
    // Correction du type
    if (type)   type = " " + type;
    else        type = "";
    // Action lorsque l'instance se termine
    this.ActionFin = actionFin;

    /* -------- Element DOM -------- */
    // Création de l'élement
    $("body").append('<div class="Dialogue'+ type +'" id="'+ this.ID +'"><div class="titre">'+ titre +'<a class="bouton fermer">x</a></div><div class="contenu">'+ contenu +'</div><div class="boutons"></div></div>');
    this.Element = $("#"+this.ID);

    /* -------- Boutons -------- */
    // S'il n'y a aucun bouton, on en créé un par défaut (ok)
    if (!boutons)
        boutons = {"OK": null};

    // Création des boutons
    var idBtn = 0;
    $.each(boutons, function(nom, action) { // action est une fonction
        // Ajout
        $(self.Element).children(".boutons").append('<a class="bouton btn'+idBtn+'">'+nom+'</a>');
        // Evenement de clic
        $(self.Element).find(".boutons .btn" + idBtn).on("click", function() {
            // Si une action a été définie pour ce bouton
            if (action) action(); // Execution
            // Ferme la boite de dialogue
            self.Fermer();
        });
        idBtn++;
    });
    $(self.Element).find(".titre .bouton.fermer").on("click", function() {
        Dialogues[self.ID].Fermer(false);
    });

    /* -------- Contenu -------- */
    // Initialisation du contenu
    $(this.Element).children(".contenu").Initialiser();
    // Focus sur le premier champ de texte
    $(this.Element).children(".contenu").find("input[type='text'], input[type='password'], textarea").focus();
    // Affichage
    $("#Focus").addClass("dialogue");

    /* -------- Actions -------- */
    // Fermeture
    this.Fermer = function(lancerAction = true, donneesRetour = null) {
        // Suppression de l'element DOM
        $(this.Element).remove();
        // Lancement de l'action de fin
        if (this.ActionFin && lancerAction)
            this.ActionFin(donneesRetour); // Execution
        // Suppression de l'instance
        delete Dialogues[this.ID];
        // Cache le focus si plus aucune boite de dialogue active
        if (Object.keys(Dialogues).length == 0)
            $("#Focus").removeClass("dialogue");
    }
}

var IdDialogue = 0;
function Dialogue(titre, contenu, boutons = false, type = false, actionFin = false) {
    // Création d'un ID
    IdDialogue++;
    var ID = 'Dial'+ IdDialogue;
    // Instanciation
    Dialogues[ID] = new InfosDialogue(ID, titre, contenu, boutons, type, actionFin);
}
