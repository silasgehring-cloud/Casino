<?php
/*
Template Name: Fun Casino Blank
*/
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8" />
<title>Fun Casino</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet" />
<style>
    body {
        margin: 0;
        font-family: 'Source Sans Pro', sans-serif;
        background: linear-gradient(135deg, #f5f5f0, #e8e1d5);
        color: #333;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
    }
    .fc-container {
        max-width: 800px;
        width: 100%;
        padding: 40px;
        margin: 40px;
        background: #fff;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }
    h1, h2, h3, h4 {
        font-family: 'Playfair Display', serif;
        color: #2c2c2c;
        margin-top: 0;
    }
    .fc-nav {
        text-align: center;
        margin-bottom: 30px;
    }
    .fc-nav a {
        text-decoration: none;
        color: #2c2c2c;
        padding: 10px 15px;
        margin: 0 5px;
        border-radius: 6px;
        transition: background 0.3s, color 0.3s;
    }
    .fc-nav a:hover {
        background: rgba(212, 175, 55, 0.1);
        color: #d4af37;
    }
    .fc-btn {
        background: #d4af37;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        transition: background 0.3s, box-shadow 0.3s;
    }
    .fc-btn:hover {
        background: #b9972b;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .fc-balance {
        margin-top: 10px;
        font-weight: 600;
    }
    .fc-result {
        margin-top: 20px;
        font-weight: 600;
    }
    .fc-result.win {
        color: #3b7d3e;
    }
    .fc-result.lose {
        color: #b23e3e;
    }
</style>
</head>
<body>
<div class="fc-container">
<?php
while ( have_posts() ) : the_post();
    the_content();
endwhile;
?>
</div>
</body>
</html>
