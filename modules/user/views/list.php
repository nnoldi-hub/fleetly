<?php
// modules/user/views/list.php
if (!function_exists('ro_no_diacritics_text')) {
    function ro_no_diacritics_text($text) {
        $text = strtr($text ?? '', [
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T',
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t'
        ]);
        return str_replace('?', '', $text);
    }
}
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Utilizatori</h1>
            <div>
                <span class="mr-3 text-muted">Folosiți: <strong><?= (int)($used ?? 0) ?></strong> / Limită: <strong><?= (int)($limit ?? 0) ?></strong></span>
                <a href="<?= BASE_URL ?>users/add" class="btn btn-primary" <?= ($remaining ?? 0) <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-user-plus mr-1"></i> Adaugă utilizator
                </a>
            </div>
        </div>
    </div>

    <?php if (($remaining ?? 0) <= 0): ?>
        <div class="alert alert-warning">Ați atins limita maximă de utilizatori permisă de abonament. Contactați administratorul pentru extindere.</div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Nume</th>
                        <th>Rol</th>
                        <th>Status</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)): $i=1; foreach ($users as $u): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($u->username) ?></td>
                        <td><?= htmlspecialchars($u->email) ?></td>
                        <td><?= htmlspecialchars(trim(($u->first_name ?? '').' '.($u->last_name ?? ''))) ?></td>
                        <td><?= htmlspecialchars(ro_no_diacritics_text($u->role_name ?? $u->role_slug ?? '')) ?></td>
                        <td>
                            <?php if (($u->status ?? '') === 'active'): ?>
                                <span class="badge badge-success">Activ</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactiv</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>users/edit?id=<?= (int)$u->id ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="post" action="<?= BASE_URL ?>users/delete" style="display:inline" onsubmit="return confirm('Sigur ștergeți utilizatorul?');">
                                <input type="hidden" name="id" value="<?= (int)$u->id ?>" />
                                <button class="btn btn-sm btn-danger" type="submit">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted">Nu există utilizatori.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
