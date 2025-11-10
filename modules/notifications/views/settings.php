<?php
// View simplificat: sesiunea & layout sunt gestionate de Controller::render
// $prefs, $smtp, $sms sunt furnizate de controller
$categories = [
    'insurance_expiry' => 'Asigurări în expirare',
    'maintenance_due' => 'Mentenanță scadentă',
    'document_expiry' => 'Documente în expirare',
    'mileage_alert' => 'Alerte kilometraj',
    'cost_alert' => 'Alerte costuri',
    'general' => 'Generale',
];
$priorities = [
    'low' => 'Scăzută',
    'medium' => 'Mediu',
    'high' => 'Ridicată',
    'critical' => 'Critică',
];
?>
<div class="container-fluid py-4">
  <?php include 'includes/breadcrumb.php'; ?>
  <div class="row g-4">
    <div class="col-12 col-lg-9">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Setări notificări</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert">
              <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger" role="alert">
              <?php 
                $errs = $_SESSION['errors']; unset($_SESSION['errors']); 
                if (is_array($errs)) { echo '<ul class="mb-0 ps-3">'; foreach($errs as $e){ echo '<li>'.htmlspecialchars($e).'</li>'; } echo '</ul>'; }
                else { echo htmlspecialchars($errs); }
              ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?php echo ROUTE_BASE; ?>notifications/settings">
            <input type="hidden" name="section" value="prefs" />
            
            <?php 
            // Verificăm dacă utilizatorul are rol de admin/manager pentru opțiuni avansate
            $userRole = $_SESSION['user_role'] ?? 'user';
            $isAdminOrManager = in_array($userRole, ['admin', 'manager', 'superadmin']);
            ?>
            
            <?php if ($isAdminOrManager): ?>
            <div class="mb-4">
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Rol de administrator:</strong> Puteți configura notificările să fie trimise tuturor utilizatorilor din companie.
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="broadcast_to_company" name="broadcast_to_company" <?php echo !empty($prefs['broadcastToCompany']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="broadcast_to_company">
                  <strong>Trimite notificările automate către toți utilizatorii companiei</strong>
                  <br><small class="text-muted">Când este activat, notificările despre asigurări/mentenanță/documente vor fi trimise tuturor utilizatorilor activi, nu doar vouă.</small>
                </label>
              </div>
            </div>
            <?php endif; ?>
            
            <div class="mb-4">
              <h6 class="mb-2">Categorii active</h6>
              <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-2">
                <?php foreach ($categories as $key => $label): ?>
                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="cat_<?php echo $key; ?>" name="categories[]" value="<?php echo $key; ?>"
                        <?php echo in_array($key, $prefs['enabledCategories'] ?? []) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="cat_<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="mb-4">
              <h6 class="mb-2">Metode de notificare</h6>
              <div class="d-flex gap-4 flex-wrap">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="method_in_app" name="method_in_app" <?php echo !empty($prefs['methods']['in_app']) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="method_in_app">In-app</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="method_email" name="method_email" <?php echo !empty($prefs['methods']['email']) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="method_email">Email</label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="method_sms" name="method_sms" <?php echo !empty($prefs['methods']['sms']) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="method_sms">SMS</label>
                </div>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-6">
                <label for="days_before" class="form-label">Cu câte zile înainte să apară notificările</label>
                <input type="number" min="0" class="form-control" id="days_before" name="days_before" value="<?php echo (int)($prefs['daysBefore'] ?? 30); ?>">
              </div>
              <div class="col-12 col-md-6">
                <label for="min_priority" class="form-label">Prioritate minimă afișată</label>
                <select class="form-select" id="min_priority" name="min_priority">
                  <?php foreach ($priorities as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo (($prefs['minPriority'] ?? 'low') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Salvează preferințele
              </button>
              <a href="<?php echo ROUTE_BASE; ?>notifications" class="btn btn-outline-secondary">Înapoi la notificări</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Configurare Email (SMTP) -->
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0">Configurare Email (SMTP)</h5>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo ROUTE_BASE; ?>notifications/settings">
            <input type="hidden" name="section" value="smtp" />
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Transport</label>
                <select class="form-select" name="smtp_transport">
                  <option value="smtp" <?php echo (($smtp['transport'] ?? 'smtp')==='smtp')?'selected':''; ?>>SMTP (recomandat)</option>
                  <option value="php_mail" <?php echo (($smtp['transport'] ?? '')==='php_mail')?'selected':''; ?>>PHP mail()</option>
                </select>
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label">Host</label>
                <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($smtp['host'] ?? ''); ?>" placeholder="smtp.exemplu.ro" />
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label">Port</label>
                <input type="number" class="form-control" name="smtp_port" value="<?php echo (int)($smtp['port'] ?? 587); ?>" />
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label">Criptare</label>
                <select class="form-select" name="smtp_encryption">
                  <option value="none" <?php echo (($smtp['encryption'] ?? '')==='none')?'selected':''; ?>>None</option>
                  <option value="ssl" <?php echo (($smtp['encryption'] ?? '')==='ssl')?'selected':''; ?>>SSL</option>
                  <option value="tls" <?php echo (($smtp['encryption'] ?? 'tls')==='tls')?'selected':''; ?>>TLS</option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Utilizator</label>
                <input type="text" class="form-control" name="smtp_username" value="<?php echo htmlspecialchars($smtp['username'] ?? ''); ?>" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Parolă</label>
                <input type="password" class="form-control" name="smtp_password" value="<?php echo htmlspecialchars($smtp['password'] ?? ''); ?>" autocomplete="new-password" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">From Email</label>
                <input type="email" class="form-control" name="smtp_from_email" value="<?php echo htmlspecialchars($smtp['from_email'] ?? ''); ?>" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">From Nume</label>
                <input type="text" class="form-control" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp['from_name'] ?? ''); ?>" />
              </div>
            </div>

            <div class="d-flex gap-2 mt-3 align-items-end flex-wrap">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Salvează SMTP</button>
              <div class="ms-auto"></div>
              <input type="hidden" name="action" value="test_email" />
              <div class="input-group" style="max-width:420px;">
                <span class="input-group-text">Trimite test la</span>
                <input type="email" class="form-control" name="test_email_to" placeholder="destinatar@exemplu.ro" />
                <button class="btn btn-outline-secondary" type="submit">Trimite test</button>
              </div>
            </div>
            <small class="text-secondary d-block mt-2">Dacă serverul nu permite mail(), alegeți SMTP. Pentru furnizori ca Gmail, folosiți parolă de aplicație.</small>
          </form>
        </div>
      </div>

      <!-- Configurare SMS -->
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0">Configurare SMS</h5>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo ROUTE_BASE; ?>notifications/settings">
            <input type="hidden" name="section" value="sms" />
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Provider</label>
                <select class="form-select" name="sms_provider" id="sms_provider">
                  <option value="twilio" <?php echo (($sms['provider'] ?? 'twilio')==='twilio')?'selected':''; ?>>Twilio</option>
                  <option value="http" <?php echo (($sms['provider'] ?? '')==='http')?'selected':''; ?>>Gateway HTTP</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">From (număr)</label>
                <input type="text" class="form-control" name="sms_from" value="<?php echo htmlspecialchars($sms['from'] ?? ''); ?>" placeholder="+407xxxxxxxx" />
              </div>
              <div class="col-12 col-md-8">
                <label class="form-label">Destinatar implicit (opțional)</label>
                <input type="text" class="form-control" name="sms_default_to" value="<?php echo htmlspecialchars($sms['sms_default_to'] ?? ''); ?>" placeholder="+407xxxxxxxx" />
                <small class="text-secondary">Dacă nu există număr per utilizator (system_settings: user_{id}_sms_to), se va folosi acesta.</small>
              </div>
              <div class="w-100"></div>
              <div class="col-12 col-md-6">
                <label class="form-label">Account SID (Twilio)</label>
                <input type="text" class="form-control" name="sms_account_sid" value="<?php echo htmlspecialchars($sms['account_sid'] ?? ''); ?>" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Auth Token (Twilio)</label>
                <input type="password" class="form-control" name="sms_auth_token" value="<?php echo htmlspecialchars($sms['auth_token'] ?? ''); ?>" />
              </div>
              <div class="w-100"></div>
              <div class="col-12 col-md-6">
                <label class="form-label">HTTP URL (Gateway)</label>
                <input type="text" class="form-control" name="sms_http_url" value="<?php echo htmlspecialchars($sms['http_url'] ?? ''); ?>" placeholder="https://api.exemplu.ro/sms" />
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label">Metodă</label>
                <select class="form-select" name="sms_http_method">
                  <option value="GET" <?php echo (($sms['http_method'] ?? 'GET')==='GET')?'selected':''; ?>>GET</option>
                  <option value="POST" <?php echo (($sms['http_method'] ?? '')==='POST')?'selected':''; ?>>POST</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Parametri (folosiți {to}, {message})</label>
                <input type="text" class="form-control" name="sms_http_params" value="<?php echo htmlspecialchars($sms['http_params'] ?? ''); ?>" placeholder="to={to}&msg={message}&key=XXX" />
              </div>
            </div>

            <div class="d-flex gap-2 mt-3 align-items-end flex-wrap">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Salvează SMS</button>
              <div class="ms-auto"></div>
              <input type="hidden" name="action" value="test_sms" />
              <div class="input-group" style="max-width:520px;">
                <span class="input-group-text">Trimite test la</span>
                <input type="text" class="form-control" name="test_sms_to" placeholder="+407xxxxxxxx" />
                <input type="text" class="form-control" name="test_sms_message" value="Test SMS Fleet" />
                <button class="btn btn-outline-secondary" type="submit">Trimite test</button>
              </div>
            </div>
            <small class="text-secondary d-block mt-2">Pentru Gateway HTTP, înlocuiți {to} și {message} în șablonul de parametri.</small>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-3">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Sfaturi</h6>
        </div>
        <div class="card-body small text-secondary">
          <ul class="mb-0 ps-3">
            <li>Puteți dezactiva categoriile care nu vă interesează.</li>
            <li>Completați SMTP și SMS pentru trimitere externă. Butoanele de test vă verifică configurarea.</li>
            <li>Prioritatea minimă afectează ce alerte sunt listate în pagină.</li>
            <li>Pentru trimiterea în lot a notificărilor “pending”, rulați <a href="<?php echo BASE_URL; ?>scripts/process_notifications.php">procesorul de notificări</a> (se poate programa în Task Scheduler/cron).</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
