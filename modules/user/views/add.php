<?php
// modules/user/views/add.php
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
        // Transliterate common ro diacritics (both correct and legacy variants)
        $name = strtr($name, [
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T',
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t'
        ]);
        // Remove stray question marks from mojibake
        $name = str_replace('?', '', $name);
        return $name;
    }
}
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Adaugă utilizator</h1>
            <a href="<?= BASE_URL ?>users" class="btn btn-light">Înapoi</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>users/add">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Parolă (opțional)</label>
                        <input type="text" name="password" class="form-control" placeholder="lăsați gol pentru generare automată" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Rol</label>
                        <select name="role_id" class="form-control" required>
                            <?php if (!empty($roles)) foreach ($roles as $r): if (($r->slug ?? '') === 'superadmin') continue; $label = ro_no_diacritics_role_label($r); ?>
                                <option value="<?= (int)$r->id ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Prenume</label>
                        <input type="text" name="first_name" class="form-control" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Nume</label>
                        <input type="text" name="last_name" class="form-control" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Telefon</label>
                        <input type="text" name="phone" class="form-control" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Activ</option>
                            <option value="inactive">Inactiv</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Salvează</button>
            </form>
        </div>
    </div>
</div>
