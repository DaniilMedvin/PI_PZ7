<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$errors = [];
$form = ['id' => 0, 'property_id' => '', 'client_name' => '', 'deal_date' => date('Y-m-d')];

$properties = db()->query('SELECT id, address, type FROM properties ORDER BY address')->fetchAll();
$propertyIds = array_map('intval', array_column($properties, 'id'));

if (is_post()) {
    $action = post('action');
    $id = (int) post('id');

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM deals WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Угоду видалено.');
        redirect('deals.php');
    }

    $form = [
        'id' => $id,
        'property_id' => post('property_id'),
        'client_name' => post('client_name'),
        'deal_date' => post('deal_date'),
    ];

    if ($form['property_id'] === '' || !in_array((int) $form['property_id'], $propertyIds, true)) {
        $errors[] = 'Оберіть об\'єкт зі списку.';
    }
    if ($form['client_name'] === '') {
        $errors[] = 'Вкажіть ПІБ клієнта.';
    } elseif (mb_strlen($form['client_name']) > 120) {
        $errors[] = 'ПІБ клієнта не може перевищувати 120 символів.';
    }
    if ($form['deal_date'] === '') {
        $errors[] = 'Вкажіть дату угоди.';
    } elseif (!valid_date($form['deal_date'])) {
        $errors[] = 'Дата угоди має некоректний формат.';
    } elseif ($form['deal_date'] > date('Y-m-d')) {
        $errors[] = 'Дата угоди не може бути в майбутньому.';
    }

    if (!$errors) {
        $values = [(int) $form['property_id'], $form['client_name'], $form['deal_date']];
        if ($id > 0) {
            $stmt = db()->prepare('UPDATE deals SET property_id = ?, client_name = ?, deal_date = ? WHERE id = ?');
            $stmt->execute([...$values, $id]);
            flash('success', 'Угоду оновлено.');
        } else {
            $stmt = db()->prepare('INSERT INTO deals (property_id, client_name, deal_date) VALUES (?, ?, ?)');
            $stmt->execute($values);
            flash('success', 'Угоду оформлено.');
        }
        redirect('deals.php');
    }
} elseif (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT id, property_id, client_name, deal_date FROM deals WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $form = $row;
    } else {
        flash('error', 'Угоду не знайдено.');
        redirect('deals.php');
    }
}

$deals = db()->query(
    'SELECT d.id, d.client_name, d.deal_date, p.address, p.type, p.price, a.full_name AS agent
     FROM deals d
     JOIN properties p ON p.id = d.property_id
     JOIN agents a ON a.id = p.agent_id
     ORDER BY d.deal_date DESC, d.id DESC'
)->fetchAll();

$title = 'Журнал угод';
$active = 'deals';
require __DIR__ . '/header.php';
?>
<section class="card">
    <h2><?= $form['id'] ? 'Редагування угоди' : 'Нова угода' ?></h2>
    <?php if (!$properties): ?>
        <p class="empty">Спочатку додайте об'єкт.</p>
    <?php else: ?>
        <form method="post" action="deals.php" class="form" novalidate>
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label>
                <span>Об'єкт <b>*</b></span>
                <select name="property_id" required>
                    <option value="">Оберіть</option>
                    <?php foreach ($properties as $property): ?>
                        <option value="<?= (int) $property['id'] ?>"<?= (string) $form['property_id'] === (string) $property['id'] ? ' selected' : '' ?>><?= e($property['address'] . ', ' . $property['type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>ПІБ клієнта <b>*</b></span>
                <input type="text" name="client_name" value="<?= e($form['client_name']) ?>" maxlength="120" required>
            </label>
            <label>
                <span>Дата угоди <b>*</b></span>
                <input type="date" name="deal_date" value="<?= e($form['deal_date']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
            </label>
            <div class="actions">
                <button type="submit" class="btn primary">Зберегти</button>
                <?php if ($form['id']): ?>
                    <a class="btn" href="deals.php">Скасувати</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Журнал угод</h2>
    <?php if (!$deals): ?>
        <p class="empty">Записів немає.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Дата</th><th>Клієнт</th><th>Об'єкт</th><th class="num">Ціна</th><th>Агент</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($deals as $row): ?>
                    <tr>
                        <td><?= e(date('d.m.Y', strtotime($row['deal_date']))) ?></td>
                        <td><?= e($row['client_name']) ?></td>
                        <td><?= e($row['address']) ?> <span class="tag"><?= e($row['type']) ?></span></td>
                        <td class="num"><?= e(money($row['price'])) ?></td>
                        <td><?= e($row['agent']) ?></td>
                        <td class="row-actions">
                            <a class="btn small" href="deals.php?edit=<?= (int) $row['id'] ?>">Змінити</a>
                            <form method="post" action="deals.php" data-confirm="Видалити угоду?">
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
