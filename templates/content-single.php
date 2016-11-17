
<?php while (have_posts()) : the_post(); ?>
  <article <?php post_class(); ?>>
    <div class="post-banner" style="background-image: url('<?php
                                   if ( has_post_thumbnail() ) { 
                                       the_post_thumbnail_url();
                                   } else {
                                       echo get_template_directory_uri(), '/assets/images/rally-compressed.jpg';
                                   }
                                   ?>')">>
      <header class="major text-center">
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php get_template_part('templates/entry-meta'); ?>
    </header>
    </div>
    <div class="entry-content">
    <div class="container-fluid">
      <div class="col-md-8 text-wrapper">
      <?php the_content(); ?>
      <hr />
      <p>You can comment, follow, and read more from the Political Revolution on <a href="http://www.medium.com/@OurPoliticaRev" target="_blank">Medium</a>. 

      <p>
        <a class="btn-tweet" target="_blank" href="https://twitter.com/intent/tweet?text=<?php the_title(); ?>&amp;url=<?php echo get_permalink(); ?>&amp;via=OurPoliticalRev" target="_blank">
          Tweet
        </a>
      </p>
      </div>
    </div>
    <!--<footer>
      <?php //wp_link_pages(['before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']); ?>
    </footer>
    <?php //comments_template('/templates/comments.php'); ?>-->
  </article>
  
<?php endwhile; ?>

