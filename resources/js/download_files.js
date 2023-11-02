function get_csv(route = "load_highscores") {
    $.ajax({
        type: "GET",
        url: route,
        data: {flag_download: true}
    }).done(function( msg ) {
        // alert( "Data Saved: " + msg );
        let yourDate = new Date().toISOString().split('T')[0];

        var contentType = "text/csv";
        var filename = "highscore_export_" + yourDate + ".csv";
        if (!contentType)
             contentType = 'application/octet-stream';
        var a = document.createElement('a');
        var blob = new Blob([msg], {'type':contentType});
        a.href = window.URL.createObjectURL(blob);
        a.download = filename;
        a.click();
    });
}