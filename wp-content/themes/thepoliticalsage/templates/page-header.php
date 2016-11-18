<?php use Roots\Sage\Titles; 

    if ( is_front_page() && is_home() ) {
        // Home page only
?>
Test
<?php } else { ?>
    <h1 id="responsive_headline" style="font-size: 100px;">
        <?= Titles\title(); ?>
    </h1>
<?php } ?>