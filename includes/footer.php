<?php if (strpos($bodyClass ?? '', 'public-site') !== false): ?>
<footer class="public-footer">
	<div class="container public-footer-main">
		<div class="public-footer-brand">
			<a class="navbar-brand" href="<?= SITE_URL ?>/">
				<span class="brand-mark" aria-hidden="true">P</span>
				<span><?= e(SITE_NAME) ?><small>PHARMA LEARNING</small></span>
			</a>
			<p>Structured learning. Personal guidance. A stronger start in pharma.</p>
		</div>
		<div class="public-footer-links">
			<div><span class="public-footer-label">Explore</span><a href="<?= SITE_URL ?>/public/courses.php">Courses</a><a href="<?= SITE_URL ?>/mentors.php">Mentors</a></div>
			<div><span class="public-footer-label">Pharma Academy</span><a href="<?= SITE_URL ?>/about.php">About us</a><a href="<?= SITE_URL ?>/login.php">Student login</a></div>
			<a class="public-footer-cta" href="<?= SITE_URL ?>/public/courses.php">Find your course <span aria-hidden="true">↗</span></a>
		</div>
	</div>
	<div class="container public-footer-bottom">
		<span>© <?= date('Y') ?> <?= e(SITE_NAME) ?></span>
		<span>Learn with purpose.</span>
	</div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/app.js"></script>
</body></html>