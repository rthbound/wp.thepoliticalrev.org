<section id="one" class="tiles">
	<!-- <div class="inner"> -->
		<header class="major">
			<h2>Latest Posts</h2>
			<p>Updates on candidates and issues you care about.</p>
		</header>

	<!-- </div> -->
	<?php $query = new WP_Query( [ 'post_type' => ['post'] ] );
         if ( $query->have_posts() ) : ?>
    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
        <?php $img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full'); ?>
          <article>
                  <span class="image">
                          <img src="<?= $img[0] ?>" alt="" />
                  </span>
                  <header class="major">
                          <h3><a href="{{ site.baseurl }}{{ post.url }}" class="link"><?php the_title(); ?></a></h3>
                          <p><?php the_excerpt(); ?></p>
                  </header>
          </article>
    <?php endwhile; ?>
<?php endif; ?>