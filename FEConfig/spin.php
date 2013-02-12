<?php
echo "<div id='spinner' style='position:absolute;
    left:400px;
    top:25px;'>
    <font color = '#00ff00'><b>
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    Drawing Plots...
    </font></b>
    </div>";
echo "<script type = 'text/javascript'>
    var opts = {
        lines: 12,        // The number of lines to draw
        length: 10,       // The length of each line
        width: 3,         // The line thickness
        radius: 10,       // The radius of the inner circle
        color: '#00ff00', // #rgb or #rrggbb
        speed: 1,         // Rounds per second
        trail: 60,        // Afterglow percentage
        shadow: false,    // Whether to render a shadow
    };
    var target = document.getElementById('spinner');
    var spinner = new Spinner(opts).spin(target);
    </script>";
?>
