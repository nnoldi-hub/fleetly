#!/bin/bash
# Hostico Deployment Script - SMS Feature
# Usage: ./deploy_hostico.sh

set -e  # Exit on error

echo "======================================"
echo "  Hostico Deployment - SMS Feature"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/home/username/public_html"  # SCHIMBĂ CU CALEA TA
BACKUP_DIR="/home/username/backups"
DB_NAME="your_database"                    # SCHIMBĂ CU NUMELE BAZEI
DB_USER="your_user"                        # SCHIMBĂ CU USERUL
DB_PASS="your_password"                    # SCHIMBĂ CU PAROLA

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Step 1: Backup
log_info "Step 1: Creating backup..."
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
if [ $? -eq 0 ]; then
    log_info "Database backup created: $BACKUP_FILE"
else
    log_error "Backup failed! Aborting."
    exit 1
fi

# Step 2: Navigate to project
log_info "Step 2: Navigating to project directory..."
cd "$PROJECT_DIR" || exit 1

# Step 3: Stash local changes (if any)
log_info "Step 3: Stashing local changes..."
git stash push -m "Pre-deployment stash $(date +%Y%m%d_%H%M%S)"

# Step 4: Pull latest changes
log_info "Step 4: Pulling latest changes from Git..."
git pull origin main
if [ $? -eq 0 ]; then
    log_info "Git pull successful"
else
    log_error "Git pull failed!"
    exit 1
fi

# Step 5: Install Composer dependencies
log_info "Step 5: Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
elif [ -f /usr/local/bin/composer ]; then
    /usr/local/bin/composer install --no-dev --optimize-autoloader
else
    log_warn "Composer not found! Please install manually."
fi

# Step 6: Verify Twilio SDK
log_info "Step 6: Verifying Twilio SDK installation..."
if [ -d "vendor/twilio/sdk" ]; then
    log_info "✓ Twilio SDK installed"
else
    log_error "✗ Twilio SDK NOT found! Run: composer install"
    exit 1
fi

# Step 7: Set permissions
log_info "Step 7: Setting file permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 755 vendor/
chmod -R 775 logs/ uploads/

# Step 8: Test installation
log_info "Step 8: Testing SMS service..."
php -r "
require 'vendor/autoload.php';
require 'core/SmsService.php';
try {
    \$sms = new SmsService();
    echo 'SmsService: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'SmsService: FAIL - ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# Step 9: Clear cache (if applicable)
log_info "Step 9: Clearing cache..."
rm -rf logs/*.log.old
# Add more cache clearing as needed

echo ""
echo "======================================"
log_info "Deployment completed successfully!"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Configure Twilio credentials in UI: Notificări > Setări > SMS"
echo "2. Test SMS: Use 'Trimite SMS de test' button"
echo "3. Configure cron job (if not already):"
echo "   */5 * * * * cd $PROJECT_DIR && php scripts/process_notifications_queue.php"
echo "4. Monitor logs: tail -f logs/notifications.log"
echo ""
echo "Backup saved to: $BACKUP_FILE"
echo ""
