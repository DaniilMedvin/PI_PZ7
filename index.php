<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$counts = db()->query(
    'SELECT
        (SELECT COUNT(*) FROM agents) AS agents,
        (SELECT COUNT(*) FROM properties) AS properties,
        (SELECT COUNT(*) FROM deals) AS deals'
)->fetch();

$latest = db()->query(
    'SELECT d.deal_date, d.client_name, p.address
     FROM deals d JOIN properties p ON p.id = d.property_id
     ORDER BY d.deal_date DESC, d.id DESC LIMIT 5'
)->fetchAll();

$title = 'Агентство нерухомості';
$active = 'index';
$errors = [];
require __DIR__ . '/header.php';
?>
<div class="stats">
    <a class="card stat" href="agents.php">
        <span class="stat-label">Агенти</span>
        <span class="stat-value"><?= (int) $counts['agents'] ?></span>
    </a>
    <a class="card stat" href="properties.php">
        <span class="stat-label">Об'єкти</span>
        <span class="stat-value"><?= (int) $counts['properties'] ?></span>
    </a>
    <a class="card stat" href="deals.php">
        <span class="stat-label">Угоди</span>
        <span class="stat-value"><?= (int) $counts['deals'] ?></span>
    </a>
</div>

<section class="card">
    <h2>Останні угоди</h2>
    <?php if (!$latest): ?>
        <p class="empty">Записів немає.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Дата</th><th>Клієнт</th><th>Об'єкт</th></tr>
                </thead>
                <tbody>
                <?php foreach ($latest as $row): ?>
                    <tr>
                        <td><?= e(date('d.m.Y', strtotime($row['deal_date']))) ?></td>
                        <td><?= e($row['client_name']) ?></td>
                        <td><?= e($row['address']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
