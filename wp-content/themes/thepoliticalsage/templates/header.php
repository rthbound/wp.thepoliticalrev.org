<!-- <header class="banner">
  <div class="container">
    <a class="brand" href="<?= esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
    <nav class="nav-primary">
      <?php
      if (has_nav_menu('primary_navigation')) :
        wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']);
      endif;
      ?>
    </nav>
  </div>
</header> -->
<!-- Wrapper -->
<div id="wrapper">

	<!-- Header -->
	<header id="header"{% if page.layout == "landing" %} class="alt"{% endif %}{% if page.layout == "home" %} class="alt"{% endif %}>
		<a href="<?= home_url(); ?>" class="logo"><strong><?php bloginfo('name'); ?></strong> <span><?php bloginfo('description'); ?></span></a>
		<nav>
			<a href="#menu">Menu</a>
		</nav>
	</header>

	<!-- Menu -->
	<nav id="menu">
		<ul class="links">
			{% if page.layout != "home" %}
					<li><a href="{{ site.baseurl }}/">Home</a></li>
			{% endif %}
	    {% for link in site.pages %}
					{% if link.nav-menu %}
							<li><a href="{{ site.baseurl }}{{ link.url }}">{{ link.title }}</a></li>
					{% endif %}
			{% endfor %}
			        <!-- <li><a href="{{ site.baseurl }}about/">About</a></li>
			        <li><a href="{{ site.baseurl }}candidates/">Candidates</a></li>
							<li><a href="{{ site.baseurl }}/contact.html">Contact Us</a></li> -->
			        <!-- <li><a href="{{ site.baseurl }}events.html">Events</a></li> -->
		</ul>
		<ul class="actions vertical">
			<!-- <li><a href="{{ site.url }}{{ site.baseurl }}/vote.html" class="button special fit">Register to Vote</a></li> -->
			<!-- <li><a href="{{ site.url }}{{ site.baseurl }}/contact.html" class="button fit">Contact Us</a></li> -->
		</ul>
	</nav>
</div>