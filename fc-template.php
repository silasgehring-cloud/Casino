<?php
/*
Template Name: Fun Casino Blank
*/
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Fun Casino</title>
</head>
<body style="font-family:sans-serif;text-align:center;margin:40px;">
<?php
while ( have_posts() ) : the_post();
    the_content();
endwhile;
?>
</body>
</html>
