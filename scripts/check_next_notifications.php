<?php
/**
 * Verifică când se vor genera următoarele notificări
 * Arată vehicule cu asigurări/documente ce expiră în următoarele 30 zile
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Încarcă configurația
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<html><head><meta charset='utf-8'><style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th { background: #007bff; color: white; padding: 12px; text-align: left; }
td { padding: 10px; border-bottom: 1px solid #ddd; }
tr:hover { background: #f8f9fa; }
.urgent { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; }
.count { font-size: 24px; font-weight: bold; color: #007bff; }
.section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style></head><body>";

echo "<h1>🔔 Verificare Notificări Viitoare</h1>";
echo "<p><strong>Data verificare:</strong> " . date('Y-m-d H:i:s') . "</p>";

$dbConfig = getDatabaseConfig();

try {
    // Conectare la Core DB
    $corePdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Găsește toate companiile active
    $stmt = $corePdo->query("SELECT id, name, tenant_db FROM companies WHERE is_active = 1");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($companies)) {
        echo "<div class='section'><p style='color: #dc3545;'>⚠️ Nu există companii active în sistem!</p></div>";
        echo "</body></html>";
        exit;
    }

    echo "<div class='section'>";
    echo "<p>📊 <strong>Companii active:</strong> " . count($companies) . "</p>";
    echo "</div>";

    $totalNotificationsToGenerate = 0;

    foreach ($companies as $company) {
        echo "<h2>🏢 " . htmlspecialchars($company['name']) . " (ID: {$company['id']})</h2>";

        // Conectare la tenant DB
        try {
            $tenantPdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$company['tenant_db']};charset=utf8mb4",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            echo "<div class='section'><p style='color: #dc3545;'>❌ Eroare conexiune DB: " . $e->getMessage() . "</p></div>";
            continue;
        }

        $companyNotifications = 0;

        // 1. Verifică asigurări RCA ce expiră
        echo "<div class='section'>";
        echo "<h3>🚗 Asigurări RCA ce expiră în următoarele 30 zile</h3>";
        
        $stmt = $tenantPdo->query("
            SELECT 
                v.plate_number,
                v.brand,
                v.model,
                i.expiration_date,
                DATEDIFF(i.expiration_date, CURDATE()) as days_until_expiry
            FROM vehicles v
            INNER JOIN insurance i ON v.id = i.vehicle_id
            WHERE i.type = 'RCA'
                AND i.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND i.expiration_date >= CURDATE()
            ORDER BY i.expiration_date ASC
        ");
        
        $rcaInsurance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rcaInsurance) > 0) {
            echo "<p class='count'>" . count($rcaInsurance) . " vehicule</p>";
            echo "<table>";
            echo "<tr><th>Vehicul</th><th>Data expirare</th><th>Zile rămase</th><th>Urgență</th></tr>";
            foreach ($rcaInsurance as $ins) {
                $days = $ins['days_until_expiry'];
                $urgency = $days <= 7 ? 'urgent' : ($days <= 14 ? 'warning' : 'info');
                echo "<tr>";
                echo "<td><strong>{$ins['plate_number']}</strong> - {$ins['brand']} {$ins['model']}</td>";
                echo "<td>{$ins['expiration_date']}</td>";
                echo "<td class='$urgency'>$days zile</td>";
                echo "<td class='$urgency'>" . ($days <= 7 ? '🔴 URGENT' : ($days <= 14 ? '🟡 Atenție' : '🔵 Info')) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $companyNotifications += count($rcaInsurance);
        } else {
            echo "<p>✅ Nu există asigurări RCA ce expiră în următoarele 30 zile</p>";
        }
        echo "</div>";

        // 2. Verifică asigurări CASCO ce expiră
        echo "<div class='section'>";
        echo "<h3>🛡️ Asigurări CASCO ce expiră în următoarele 30 zile</h3>";
        
        $stmt = $tenantPdo->query("
            SELECT 
                v.plate_number,
                v.brand,
                v.model,
                i.expiration_date,
                DATEDIFF(i.expiration_date, CURDATE()) as days_until_expiry
            FROM vehicles v
            INNER JOIN insurance i ON v.id = i.vehicle_id
            WHERE i.type = 'CASCO'
                AND i.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND i.expiration_date >= CURDATE()
            ORDER BY i.expiration_date ASC
        ");
        
        $cascoInsurance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($cascoInsurance) > 0) {
            echo "<p class='count'>" . count($cascoInsurance) . " vehicule</p>";
            echo "<table>";
            echo "<tr><th>Vehicul</th><th>Data expirare</th><th>Zile rămase</th><th>Urgență</th></tr>";
            foreach ($cascoInsurance as $ins) {
                $days = $ins['days_until_expiry'];
                $urgency = $days <= 7 ? 'urgent' : ($days <= 14 ? 'warning' : 'info');
                echo "<tr>";
                echo "<td><strong>{$ins['plate_number']}</strong> - {$ins['brand']} {$ins['model']}</td>";
                echo "<td>{$ins['expiration_date']}</td>";
                echo "<td class='$urgency'>$days zile</td>";
                echo "<td class='$urgency'>" . ($days <= 7 ? '🔴 URGENT' : ($days <= 14 ? '🟡 Atenție' : '🔵 Info')) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $companyNotifications += count($cascoInsurance);
        } else {
            echo "<p>✅ Nu există asigurări CASCO ce expiră în următoarele 30 zile</p>";
        }
        echo "</div>";

        // 3. Verifică ITP ce expiră
        echo "<div class='section'>";
        echo "<h3>🔧 ITP ce expiră în următoarele 30 zile</h3>";
        
        $stmt = $tenantPdo->query("
            SELECT 
                v.plate_number,
                v.brand,
                v.model,
                d.expiration_date,
                DATEDIFF(d.expiration_date, CURDATE()) as days_until_expiry
            FROM vehicles v
            INNER JOIN documents d ON v.id = d.vehicle_id
            WHERE d.type = 'ITP'
                AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND d.expiration_date >= CURDATE()
            ORDER BY d.expiration_date ASC
        ");
        
        $itpDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($itpDocs) > 0) {
            echo "<p class='count'>" . count($itpDocs) . " vehicule</p>";
            echo "<table>";
            echo "<tr><th>Vehicul</th><th>Data expirare</th><th>Zile rămase</th><th>Urgență</th></tr>";
            foreach ($itpDocs as $doc) {
                $days = $doc['days_until_expiry'];
                $urgency = $days <= 7 ? 'urgent' : ($days <= 14 ? 'warning' : 'info');
                echo "<tr>";
                echo "<td><strong>{$doc['plate_number']}</strong> - {$doc['brand']} {$doc['model']}</td>";
                echo "<td>{$doc['expiration_date']}</td>";
                echo "<td class='$urgency'>$days zile</td>";
                echo "<td class='$urgency'>" . ($days <= 7 ? '🔴 URGENT' : ($days <= 14 ? '🟡 Atenție' : '🔵 Info')) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $companyNotifications += count($itpDocs);
        } else {
            echo "<p>✅ Nu există ITP ce expiră în următoarele 30 zile</p>";
        }
        echo "</div>";

        // 4. Verifică Roviniete ce expiră
        echo "<div class='section'>";
        echo "<h3>🎫 Roviniete ce expiră în următoarele 30 zile</h3>";
        
        $stmt = $tenantPdo->query("
            SELECT 
                v.plate_number,
                v.brand,
                v.model,
                d.expiration_date,
                DATEDIFF(d.expiration_date, CURDATE()) as days_until_expiry
            FROM vehicles v
            INNER JOIN documents d ON v.id = d.vehicle_id
            WHERE d.type = 'Rovinieta'
                AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND d.expiration_date >= CURDATE()
            ORDER BY d.expiration_date ASC
        ");
        
        $rovDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rovDocs) > 0) {
            echo "<p class='count'>" . count($rovDocs) . " vehicule</p>";
            echo "<table>";
            echo "<tr><th>Vehicul</th><th>Data expirare</th><th>Zile rămase</th><th>Urgență</th></tr>";
            foreach ($rovDocs as $doc) {
                $days = $doc['days_until_expiry'];
                $urgency = $days <= 7 ? 'urgent' : ($days <= 14 ? 'warning' : 'info');
                echo "<tr>";
                echo "<td><strong>{$doc['plate_number']}</strong> - {$doc['brand']} {$doc['model']}</td>";
                echo "<td>{$doc['expiration_date']}</td>";
                echo "<td class='$urgency'>$days zile</td>";
                echo "<td class='$urgency'>" . ($days <= 7 ? '🔴 URGENT' : ($days <= 14 ? '🟡 Atenție' : '🔵 Info')) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $companyNotifications += count($rovDocs);
        } else {
            echo "<p>✅ Nu există roviniete ce expiră în următoarele 30 zile</p>";
        }
        echo "</div>";

        echo "<div class='section' style='background: #e3f2fd;'>";
        echo "<p><strong>📊 Total notificări ce vor fi generate pentru această companie:</strong> <span class='count' style='color: #1976d2;'>$companyNotifications</span></p>";
        echo "</div>";

        $totalNotificationsToGenerate += $companyNotifications;
    }

    // Summary final
    echo "<hr>";
    echo "<div class='section' style='background: #c8e6c9; border: 2px solid #4caf50;'>";
    echo "<h2>📈 SUMAR FINAL</h2>";
    echo "<p><strong>Total companii procesate:</strong> " . count($companies) . "</p>";
    echo "<p><strong>Total notificări ce vor fi generate:</strong> <span class='count' style='color: #2e7d32;'>$totalNotificationsToGenerate</span></p>";
    
    if ($totalNotificationsToGenerate > 0) {
        echo "<p style='color: #2e7d32; font-size: 18px;'>✅ <strong>Cron job-ul va genera notificări mâine dimineață la 06:00!</strong></p>";
    } else {
        echo "<p style='color: #f57c00; font-size: 18px;'>ℹ️ <strong>Nu există notificări de generat momentan.</strong></p>";
        echo "<p>Asigură-te că ai vehicule cu asigurări/documente ce expiră în următoarele 30 zile.</p>";
    }
    echo "</div>";

    // Verifică ultimele notificări generate
    echo "<div class='section'>";
    echo "<h3>📜 Ultimele notificări generate (ultimele 10)</h3>";
    
    foreach ($companies as $company) {
        $tenantPdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$company['tenant_db']};charset=utf8mb4",
            $dbConfig['username'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $tenantPdo->query("
            SELECT type, message, created_at 
            FROM notifications 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentNotifications) > 0) {
            echo "<h4>{$company['name']}</h4>";
            echo "<table>";
            echo "<tr><th>Tip</th><th>Mesaj</th><th>Data creării</th></tr>";
            foreach ($recentNotifications as $notif) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($notif['type']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>" . $notif['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='section' style='background: #ffebee; border: 2px solid #f44336;'>";
    echo "<p style='color: #c62828;'>❌ <strong>Eroare:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
