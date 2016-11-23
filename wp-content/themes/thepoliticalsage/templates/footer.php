<!--<footer class="content-info">
  <div class="container">
    <?php dynamic_sidebar('sidebar-footer'); ?>

  </div>
</footer>-->

<footer id="footer">
			<ul class="icons">
				<li><a href="{{ site.twitter_url }}" class="icon alt fa-twitter" target="_blank"><span class="label">Twitter</span></a></li>
				<li><a href="{{ site.facebook_url }}" class="icon alt fa-facebook" target="_blank"><span class="label">Facebook</span></a></li>
				<!-- {% if site.gitlab_url %}
				<li><a href="{{ site.gitlab_url }}" class="icon alt fa-gitlab" target="_blank"><span class="label">GitLab</span></a></li>
				{% endif %} -->
				<li><a href="{{ site.github_url }}" class="icon alt fa-github" target="_blank"><span class="label">GitHub</span></a></li>
				<li><a href="{{ site.medium_url }}" class="icon alt fa-medium" target="_blank"><span class="label">Medium</span></a></li>
				<li><a href="{{ site.slack_url }}" class="icon alt fa-slack" target="_blank"><span class="label">Slack</span></a></li>
				<li><a href="{{ site.subreddit_url }}" class="icon alt fa-reddit-alien" target="_blank"><span class="label">LinkedIn</span></a></li>
			</ul>
			<ul class="copyright">
				<li>&copy; <?= bloginfo('title'); ?></li>
				<!-- <li>Design: <a href="https://html5up.net" target="_blank">HTML5 UP</a></li> -->

			</ul>
			<?php if (is_page() && !is_front_page()) : ?>
				<script>
					jQuery(document).ready(function($){
						$("#responsive_headline").fitText(0.7, { minFontSize: '20px', maxFontSize: '100px' });
					});
				</script>
			<?php endif; ?>
	</footer>