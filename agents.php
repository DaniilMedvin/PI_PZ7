<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$errors = [];
$form = ['id' => 0, 'full_name' => '', 'phone' => ''];

if (is_post()) {
    $action = post('action');
    $id = (int) post('id');

    if ($action === 'delete') {
        try {
            $stmt = db()->prepare('DELETE FROM agents WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Агента видалено.');
        } catch (PDOException $ex) {
            flash('error', 'Агента не можна видалити: до нього прив\'язані об\'єкти.');
        }
        redirect('agents.php');
    }

    $form = ['id' => $id, 'full_name' => post('full_name'), 'phone' => post('phone')];

    if ($form['full_name'] === '') {
        $errors[] = 'Вкажіть ПІБ.';
    } elseif (mb_strlen($form['full_name']) > 120) {
        $errors[] = 'ПІБ не може перевищувати 120 символів.';
    }
    if ($form['phone'] === '') {
        $errors[] = 'Вкажіть телефон.';
    } elseif (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', $form['phone'])) {
        $errors[] = 'Телефон має містити від 7 до 20 цифр та символів + - ( ).';
    }

    if (!$errors) {
        if ($id > 0) {
            $stmt = db()->prepare('UPDATE agents SET full_name = ?, phone = ? WHERE id = ?');
            $stmt->execute([$form['full_name'], $form['phone'], $id]);
            flash('success', 'Дані агента оновлено.');
        } else {
            $stmt = db()->prepare('INSERT INTO agents (full_name, phone) VALUES (?, ?)');
            $stmt->execute([$form['full_name'], $form['phone']]);
            flash('success', 'Агента додано.');
        }
        redirect('agents.php');
    }
} elseif (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT id, full_name, phone FROM agents WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $form = $row;
    } else {
        flash('error', 'Агента не знайдено.');
        redirect('agents.php');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$stmt = db()->prepare(
    'SELECT a.id, a.full_name, a.phone, COUNT(p.id) AS objects
     FROM agents a LEFT JOIN properties p ON p.agent_id = a.id
     WHERE a.full_name ILIKE ?
     GROUP BY a.id, a.full_name, a.phone
     ORDER BY a.full_name'
);
$stmt->execute(['%' . $q . '%']);
$agents = $stmt->fetchAll();

$title = 'Агенти';
$active = 'agents';
require __DIR__ . '/header.php';
?>
<section class="card">
    <h2><?= $form['id'] ? 'Редагування агента' : 'Новий агент' ?></h2>
    <form method="post" action="agents.php" class="form" novalidate>
        <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
        <label>
            <span>ПІБ <b>*</b></span>
            <input type="text" name="full_name" value="<?= e($form['full_name']) ?>" maxlength="120" required>
        </label>
        <label>
            <span>Телефон <b>*</b></span>
            <input type="tel" name="phone" value="<?= e($form['phone']) ?>" maxlength="20" required>
        </label>
        <div class="actions">
            <button type="submit" class="btn primary">Зберегти</button>
            <?php if ($form['id']): ?>
                <a class="btn" href="agents.php">Скасувати</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>Список агентів</h2>
    <form method="get" action="agents.php" class="search">
        <label class="sr" for="q">Пошук</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Пошук за ПІБ">
        <button type="submit" class="btn">Знайти</button>
    </form>
    <?php if (!$agents): ?>
        <p class="empty">Записів немає.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>ПІБ</th><th>Телефон</th><th class="num">Об'єктів</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($agents as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['phone']) ?></td>
                        <td class="num"><?= (int) $row['objects'] ?></td>
                        <td class="row-actions">
                            <a class="btn small" href="agents.php?edit=<?= (int) $row['id'] ?>">Змінити</a>
                            <form method="post" action="agents.php" data-confirm="Видалити агента?">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn small danger">Видалити</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
