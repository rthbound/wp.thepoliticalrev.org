<!-- Banner -->
<?php $img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full'); ?>

<section id="banner" class="major" style="background-position: center 0px; background-image: url(http://i.huffpost.com/gen/3431754/images/o-BERNIE-SANDERS-facebook.jpg);">
    <div class="inner">
        <!-- <span class="image" style="display: none;">
            <img src="images/pic07.jpg" alt="">
        </span> -->
        <header class="major">
            <?php get_template_part('templates/page', 'header'); ?>
        </header>
        <div class="content">
            <p>Lorem ipsum dolor sit amet nullam consequat<br>
            sed veroeros. tempus adipiscing nulla.</p>
        </div>
    </div>
</section>

<!-- Main -->
<main>

<!-- One -->
<?php get_template_part('templates/tiles'); ?>
