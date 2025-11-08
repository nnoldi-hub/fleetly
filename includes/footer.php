     </main>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <small class="text-muted">
                        Created by <a href="https://conectica-it.ro" target="_blank" class="text-decoration-none">conectica-it.ro</a>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        Ultima actualizare: <?= date('d.m.Y H:i') ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS (CDN 5.3.x) with local fallback -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" onerror="(function(){var s=document.createElement('script');s.src='<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js';document.head.appendChild(s);})();"></script>
    
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript principal -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
    
    <!-- JavaScript modular -->
    <?php if (isset($jsFiles)): ?>
        <?php foreach ($jsFiles as $jsFile): ?>
            <script src="<?= BASE_URL ?>assets/js/modules/<?= $jsFile ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Fix pentru dropdown-uri -->
    <script>
        // Forțează reinițializarea dropdown-urilor Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Delay pentru a asigura că toate script-urile s-au încărcat
            setTimeout(function() {
                // Reinițializare dropdowns
                const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
                const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                    // Elimină instanța existentă dacă există
                    const existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                    if (existingDropdown) {
                        existingDropdown.dispose();
                    }
                    // Creează o instanță nouă
                    return new bootstrap.Dropdown(dropdownToggleEl);
                });
                
                console.log('Dropdown-uri reinițializate:', dropdownList.length);
            }, 500);
        });
        
        // Alternativă: Click manual pentru dropdown-uri care nu funcționează
        document.addEventListener('click', function(e) {
            const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) {
                const dropdown = dropdownToggle.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    // Toggle manual
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                    } else {
                        // Închide alte dropdown-uri
                        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                            menu.classList.remove('show');
                            menu.previousElementSibling?.setAttribute('aria-expanded', 'false');
                        });
                        // Deschide dropdown-ul curent
                        dropdown.classList.add('show');
                        dropdownToggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        });
        
        // Închide dropdown-urile când se face click în afara lor
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                    menu.previousElementSibling?.setAttribute('aria-expanded', 'false');
                });
            }
        });
    </script>
    
    <!-- Încărcare notificări -->
    <script>
        // Bootstrap 5.3 color mode (light/dark) toggle
        (function() {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const initial = stored || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', initial);
            const btn = document.getElementById('themeToggle');
            const syncIcon = () => {
                if (!btn) return;
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                btn.querySelector('i')?.classList.toggle('fa-moon', !isDark);
                btn.querySelector('i')?.classList.toggle('fa-sun', isDark);
                btn.setAttribute('aria-pressed', String(isDark));
                btn.title = isDark ? 'Comută pe luminoasă' : 'Comută pe întunecată';
            };
            syncIcon();
            // Notify listeners (e.g., charts) about the initial theme
            try { window.dispatchEvent(new CustomEvent('theme:change', { detail: { theme: initial } })); } catch (e) {}
            btn?.addEventListener('click', function() {
                const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
                syncIcon();
                // Broadcast theme change so UI components can react (e.g., Chart.js)
                try { window.dispatchEvent(new CustomEvent('theme:change', { detail: { theme: next } })); } catch (e) {}
            });
        })();

        document.addEventListener('DOMContentLoaded', function() {
            // Temporar dezactivat pentru debugging
            // loadNotifications();
            
            // Reinițializare componente Bootstrap după încărcarea paginii
            if (window.FleetManagement && window.FleetManagement.reinitialize) {
                setTimeout(function() {
                    window.FleetManagement.reinitialize();
                }, 100);
            }
            
            // Reîncarcă notificările la fiecare 5 minute (temporar dezactivat)
            // setInterval(loadNotifications, 300000);
        });
        
        // Reinițializare componente când se navighează prin aplicație
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                // Pagina a fost încărcată din cache
                if (window.FleetManagement && window.FleetManagement.reinitialize) {
                    setTimeout(function() {
                        window.FleetManagement.reinitialize();
                    }, 100);
                }
            }
        });
        
        function loadNotifications() {
            fetch('<?= ROUTE_BASE ?>api/notifications')
                .then(response => response.json())
                .then(data => {
                    const notificationCount = document.getElementById('notificationCount');
                    const notificationList = document.getElementById('notificationList');
                    
                    if (notificationCount) {
                        notificationCount.textContent = data.count || 0;
                        notificationCount.style.display = data.count > 0 ? 'inline' : 'none';
                    }
                    
                    if (notificationList) {
                        if (data.notifications && data.notifications.length > 0) {
                            notificationList.innerHTML = data.notifications.map(notification => `
                                <li><a class="dropdown-item notification-item ${notification.priority}" href="#">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1">${notification.title}</h6>
                                            <p class="mb-1 small">${notification.message}</p>
                                            <small class="text-muted">${notification.created_at}</small>
                                        </div>
                                    </div>
                                </a></li>
                            `).join('');
                        } else {
                            notificationList.innerHTML = '<li><span class="dropdown-item-text text-muted">Nu există notificări</span></li>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Eroare la încărcarea notificărilor:', error);
                    // Fallback pentru notificări
                    const notificationList = document.getElementById('notificationList');
                    if (notificationList) {
                        notificationList.innerHTML = '<li><span class="dropdown-item-text text-muted">Eroare la încărcarea notificărilor</span></li>';
                    }
                });
        }
        
        function getNotificationIcon(type) {
            const icons = {
                'document_expiry': 'file-contract',
                'maintenance_due': 'tools',
                'inspection_due': 'clipboard-check',
                'license_expiry': 'id-card',
                'mileage_alert': 'tachometer-alt',
                'cost_alert': 'dollar-sign',
                'general': 'info-circle'
            };
            return icons[type] || 'bell';
        }
    </script>
</body>
</html>
