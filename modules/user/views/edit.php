<?php
// modules/user/views/edit.php
if (!function_exists('ro_no_diacritics_role_label')) {
    function ro_no_diacritics_role_label($role) {
        $slug = strtolower($role->slug ?? '');
        $map = [
            'superadmin' => 'SuperAdmin',
            'admin' => 'Administrator Firma',
            'company_admin' => 'Administrator Firma',
            'fleet_manager' => 'Manager Flota',
            'manager' => 'Manager',
            'driver' => 'Sofer',
            'fleet_operator' => 'Operator Flota',
            'operator' => 'Operator Flota',
        ];
        if (isset($map[$slug])) return $map[$slug];
        $name = $role->name ?? ($role->slug ?? 'Rol');
        $name = strtr($name, [
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T',
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t'
        ]);
        $name = str_replace('?', '', $name);
        return $name;
    }
}
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Editează utilizator</h1>
            <a href="<?= BASE_URL ?>users" class="btn btn-light">Înapoi</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>users/edit?id=<?= (int)($user->id ?? 0) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username ?? '') ?>" required />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Parolă nouă (opțional)</label>
                        <input type="text" name="password" class="form-control" placeholder="lăsați gol pentru a nu schimba" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Rol</label>
                        <select name="role_id" class="form-control">
                            <?php if (!empty($roles)) foreach ($roles as $r): if (($r->slug ?? '') === 'superadmin') continue; $label = ro_no_diacritics_role_label($r); ?>
                                <option value="<?= (int)$r->id ?>" <?= ((int)$r->id === (int)($user->role_id ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Prenume</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user->first_name ?? '') ?>" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Nume</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user->last_name ?? '') ?>" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Telefon</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user->phone ?? '') ?>" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= (($user->status ?? '') === 'active') ? 'selected' : '' ?>>Activ</option>
                            <option value="inactive" <?= (($user->status ?? '') === 'inactive') ? 'selected' : '' ?>>Inactiv</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Salvează</button>
            </form>
        </div>
    </div>
</div>
