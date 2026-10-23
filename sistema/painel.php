<?php
/* Employee home. Stage 1: welcome only — clocking in arrives in stage 2. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$user = require_user();

page_head(t('home'), $user);
?>
  <section class="card welcome">
    <h1><?= e(t('hello', ['name' => explode(' ', $user['full_name'])[0]])) ?></h1>
    <p class="muted"><?= e(t('punch_soon')) ?></p>
    <p><code class="user"><?= e($user['username']) ?></code></p>
  </section>
<?php page_foot();
