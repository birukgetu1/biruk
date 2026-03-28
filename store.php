<?php

Smyfile fopen("location.txt", "w");

Stxt "lat:" $_GET["lat"] "\nlong: $_GET["long"];

fwrite($myfile, Stxt);

fclose($myfile);
?>
