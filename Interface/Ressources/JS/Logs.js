var fLogs = null;
var filtres = {

}

$(document).ready(function() {
    $("#ListeFichiers > a").click(function() {
        $(this).siblings(".actuel").removeClass("actuel");
        $(this).addClass("actuel");

        fLogs = $(this).text();
        actualiser();
    });
});

var timer = null;
function actualiser() {
    clearTimeout(timer);
    $("#messages table").load("./Noyau/Logs/"+fLogs, function() {
        timer = setTimeout(actualiser, 3000);
        var date = new Date;
        $("#heureMaj").text(date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds());
    });
}
