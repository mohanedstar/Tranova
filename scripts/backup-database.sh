#!/bin/bash

# ============================================
# Trinova Platform - Database Backup Script
# ============================================

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Load .env file
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | xargs)
fi

# Database configuration
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-trinova}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

# Backup configuration
BACKUP_PATH=${BACKUP_PATH:-"$PROJECT_DIR/storage/app/backups"}
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
KEEP_DAYS=${KEEP_DAYS:-7}

# ============================================
# Functions
# ============================================
print_header() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

format_bytes() {
    local bytes=$1
    if [ $bytes -ge 1073741824 ]; then
        echo "$(awk "BEGIN {printf \"%.2f\", $bytes/1073741824}") GB"
    elif [ $bytes -ge 1048576 ]; then
        echo "$(awk "BEGIN {printf \"%.2f\", $bytes/1048576}") MB"
    elif [ $bytes -ge 1024 ]; then
        echo "$(awk "BEGIN {printf \"%.2f\", $bytes/1024}") KB"
    else
        echo "$bytes B"
    fi
}

# ============================================
# Main Script
# ============================================
print_header "🚀 Trinova Database Backup"

print_info "Database: $DB_DATABASE"
print_info "Host: $DB_HOST"
print_info "Driver: $DB_CONNECTION"
print_info "Backup Path: $BACKUP_PATH"
echo ""

# Create backup directory
mkdir -p "$BACKUP_PATH"

# Create backup based on driver
if [ "$DB_CONNECTION" = "mysql" ]; then
    BACKUP_FILE="mysql_backup_${DB_DATABASE}_${TIMESTAMP}.sql"
    BACKUP_FULL_PATH="$BACKUP_PATH/$BACKUP_FILE"

    print_info "Creating MySQL backup..."

    mysqldump \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        "$DB_DATABASE" > "$BACKUP_FULL_PATH"

elif [ "$DB_CONNECTION" = "pgsql" ]; then
    BACKUP_FILE="pgsql_backup_${DB_DATABASE}_${TIMESTAMP}.sql"
    BACKUP_FULL_PATH="$BACKUP_PATH/$BACKUP_FILE"

    print_info "Creating PostgreSQL backup..."

    PGPASSWORD="$DB_PASSWORD" pg_dump \
        -U "$DB_USERNAME" \
        -h "$DB_HOST" \
        -p "$DB_PORT" \
        -F p \
        "$DB_DATABASE" > "$BACKUP_FULL_PATH"

elif [ "$DB_CONNECTION" = "sqlite" ]; then
    BACKUP_FILE="sqlite_backup_${TIMESTAMP}.sql"
    BACKUP_FULL_PATH="$BACKUP_PATH/$BACKUP_FILE"

    print_info "Creating SQLite backup..."

    cp "$DB_DATABASE" "$BACKUP_FULL_PATH"

else
    print_error "Unsupported database driver: $DB_CONNECTION"
    exit 1
fi

# Verify backup
if [ ! -f "$BACKUP_FULL_PATH" ] || [ ! -s "$BACKUP_FULL_PATH" ]; then
    print_error "Backup file was not created or is empty"
    exit 1
fi

FILE_SIZE=$(stat -f%z "$BACKUP_FULL_PATH" 2>/dev/null || stat -c%s "$BACKUP_FULL_PATH")
print_success "Backup created: $BACKUP_FILE"
print_info "File size: $(format_bytes $FILE_SIZE)"

# Compress backup
print_info "Compressing backup..."
gzip "$BACKUP_FULL_PATH"
COMPRESSED_FILE="$BACKUP_FULL_PATH.gz"

if [ -f "$COMPRESSED_FILE" ]; then
    rm -f "$BACKUP_FULL_PATH"
    COMPRESSED_SIZE=$(stat -f%z "$COMPRESSED_FILE" 2>/dev/null || stat -c%s "$COMPRESSED_FILE")
    print_success "Compressed: $(basename $COMPRESSED_FILE)"
    print_info "Compressed size: $(format_bytes $COMPRESSED_SIZE)"
fi

# Clean old backups
print_info "Cleaning backups older than $KEEP_DAYS days..."
DELETED_COUNT=$(find "$BACKUP_PATH" -name "*.sql*" -type f -mtime +$KEEP_DAYS -delete -print | wc -l)
print_success "Cleaned $DELETED_COUNT old backup(s)"

# Summary
print_header "✅ Backup Completed Successfully!"
print_info "Backup file: $(basename $COMPRESSED_FILE)"
print_info "Location: $BACKUP_PATH"
print_info "Total backups: $(ls -1 "$BACKUP_PATH"/*.sql* 2>/dev/null | wc -l)"
echo ""
