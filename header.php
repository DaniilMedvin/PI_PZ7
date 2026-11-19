<?php
$pages = [
    'index' => ['index.php', 'Огляд'],
    'agents' => ['agents.php', 'Агенти'],
    'properties' => ['properties.php', 'Об\'єкти'],
    'deals' => ['deals.php', 'Угоди'],
];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="scene" aria-hidden="true"></div>
<a class="skip" href="#content">До змісту</a>
<header class="top">
    <div class="top-inner">
        <span class="brand">Нерухомість</span>
        <nav aria-label="Навігація">
            <?php foreach ($pages as $key => [$href, $label]): ?>
                <a href="<?= $href ?>"<?= $key === $active ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main id="content">
    <h1><?= e($title) ?></h1>
    <?php if ($flash): ?>
        <div class="alert <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
