<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$errors = [];
$form = ['id' => 0, 'agent_id' => '', 'address' => '', 'type' => '', 'price' => ''];

$agents = db()->query('SELECT id, full_name FROM agents ORDER BY full_name')->fetchAll();
$agentIds = array_map('intval', array_column($agents, 'id'));

if (is_post()) {
    $action = post('action');
    $id = (int) post('id');

    if ($action === 'delete') {
        try {
            $stmt = db()->prepare('DELETE FROM properties WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Об\'єкт видалено.');
        } catch (PDOException $ex) {
            flash('error', 'Об\'єкт не можна видалити: для нього зареєстровані угоди.');
        }
        redirect('properties.php');
    }

    $form = [
        'id' => $id,
        'agent_id' => post('agent_id'),
        'address' => post('address'),
        'type' => post('type'),
        'price' => str_replace(',', '.', post('price')),
    ];

    if ($form['agent_id'] === '' || !in_array((int) $form['agent_id'], $agentIds, true)) {
        $errors[] = 'Оберіть агента зі списку.';
    }
    if ($form['address'] === '') {
        $errors[] = 'Вкажіть адресу.';
    } elseif (mb_strlen($form['address']) > 200) {
        $errors[] = 'Адреса не може перевищувати 200 символів.';
    }
    if (!in_array($form['type'], PROPERTY_TYPES, true)) {
        $errors[] = 'Оберіть тип об\'єкта.';
    }
    if ($form['price'] === '') {
        $errors[] = 'Вкажіть ціну.';
    } elseif (!is_numeric($form['price']) || (float) $form['price'] <= 0 || (float) $form['price'] >= 10000000000) {
        $errors[] = 'Ціна має бути додатним числом.';
    }

    if (!$errors) {
        $values = [(int) $form['agent_id'], $form['address'], $form['type'], round((float) $form['price'], 2)];
        if ($id > 0) {
            $stmt = db()->prepare('UPDATE properties SET agent_id = ?, address = ?, type = ?, price = ? WHERE id = ?');
            $stmt->execute([...$values, $id]);
            flash('success', 'Дані об\'єкта оновлено.');
        } else {
            $stmt = db()->prepare('INSERT INTO properties (agent_id, address, type, price) VALUES (?, ?, ?, ?)');
            $stmt->execute($values);
            flash('success', 'Об\'єкт додано.');
        }
        redirect('properties.php');
    }
} elseif (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT id, agent_id, address, type, price FROM properties WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $form = $row;
    } else {
        flash('error', 'Об\'єкт не знайдено.');
        redirect('properties.php');
    }
}

$filter = (int) ($_GET['agent'] ?? 0);
$sql = 'SELECT p.id, p.address, p.type, p.price, a.full_name
        FROM properties p JOIN agents a ON a.id = p.agent_id';
$params = [];
if ($filter > 0) {
    $sql .= ' WHERE p.agent_id = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$title = 'Об\'єкти нерухомості';
$active = 'properties';
require __DIR__ . '/header.php';
?>
<section class="card">
    <h2><?= $form['id'] ? 'Редагування об\'єкта' : 'Новий об\'єкт' ?></h2>
    <?php if (!$agents): ?>
        <p class="empty">Спочатку додайте агента.</p>
    <?php else: ?>
        <form method="post" action="properties.php" class="form" novalidate>
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label>
                <span>Агент <b>*</b></span>
                <select name="agent_id" required>
                    <option value="">Оберіть</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= (int) $agent['id'] ?>"<?= (string) $form['agent_id'] === (string) $agent['id'] ? ' selected' : '' ?>><?= e($agent['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Адреса <b>*</b></span>
                <input type="text" name="address" value="<?= e($form['address']) ?>" maxlength="200" required>
            </label>
            <label>
                <span>Тип <b>*</b></span>
                <select name="type" required>
                    <option value="">Оберіть</option>
                    <?php foreach (PROPERTY_TYPES as $type): ?>
                        <option value="<?= e($type) ?>"<?= $form['type'] === $type ? ' selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Ціна, $ <b>*</b></span>
                <input type="text" inputmode="decimal" name="price" value="<?= e($form['price']) ?>" required>
            </label>
            <div class="actions">
                <button type="submit" class="btn primary">Зберегти</button>
                <?php if ($form['id']): ?>
                    <a class="btn" href="properties.php">Скасувати</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Список об'єктів</h2>
    <form method="get" action="properties.php" class="search">
        <label class="sr" for="agent">Агент</label>
        <select id="agent" name="agent">
            <option value="0">Усі агенти</option>
            <?php foreach ($agents as $agent): ?>
                <option value="<?= (int) $agent['id'] ?>"<?= $filter === (int) $agent['id'] ? ' selected' : '' ?>><?= e($agent['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Показати</button>
    </form>
    <?php if (!$items): ?>
        <p class="empty">Записів немає.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Адреса</th><th>Тип</th><th class="num">Ціна</th><th>Агент</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= e($row['address']) ?></td>
                        <td><span class="tag"><?= e($row['type']) ?></span></td>
                        <td class="num"><?= e(money($row['price'])) ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td class="row-actions">
                            <a class="btn small" href="properties.php?edit=<?= (int) $row['id'] ?>">Змінити</a>
                            <form method="post" action="properties.php" data-confirm="Видалити об'єкт?">
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
