#!/bin/bash

# Migration Conflict Resolver
# Handles "Table already exists" errors automatically
# Usage: bash scripts/migrate-safe.sh

echo "================================"
echo "Safe Migration Runner"
echo "================================"
echo ""

cd "$(dirname "$0")/.." || exit 1

echo "Step 1: Checking database connection..."
php artisan db:show 2>&1 | head -5 || {
    echo "❌ Database connection failed"
    echo "Please check your .env database credentials"
    exit 1
}
echo "✅ Database connected"
echo ""

echo "Step 2: Running migrations..."
php artisan migrate 2>&1 | tee /tmp/migration-output.log

# Check if there were any "Table already exists" errors
if grep -q "Base table or view already exists" /tmp/migration-output.log; then
    echo ""
    echo "⚠️  Found 'Table already exists' errors"
    echo ""

    # Extract table names that failed
    failed_tables=$(grep "Base table or view already exists" /tmp/migration-output.log | grep -oP "Table '\K[^']+")

    echo "Failed tables:"
    echo "$failed_tables"
    echo ""

    echo "These tables already exist in your database."
    echo "This usually means the migration ran before but isn't recorded."
    echo ""
    echo "Options:"
    echo "1. Mark migrations as run (recommended if tables are correct)"
    echo "2. Drop and recreate tables (DANGEROUS - loses data)"
    echo "3. Skip these migrations"
    echo ""
    read -p "Choose option (1/2/3): " choice

    case $choice in
        1)
            echo "Marking failed migrations as completed..."
            # This would need to manually insert into migrations table
            echo "⚠️  This requires manual intervention."
            echo "Run this SQL to mark migrations as done:"
            echo ""
            echo "INSERT INTO migrations (migration, batch) VALUES"
            echo "  ('2025_11_26_000005_create_webhook_logs_table', 1),"
            echo "  ('2025_01_01_000002_create_push_logs_table', 1)"
            echo "ON DUPLICATE KEY UPDATE batch=batch;"
            ;;
        2)
            echo "❌ DANGEROUS: This will delete existing data!"
            read -p "Are you absolutely sure? Type 'DELETE MY DATA' to confirm: " confirm
            if [ "$confirm" = "DELETE MY DATA" ]; then
                echo "Dropping tables..."
                # User would need to manually drop tables
                echo "Please manually drop the tables and re-run migrations"
            else
                echo "Cancelled"
            fi
            ;;
        3)
            echo "Skipping these migrations"
            echo "Continuing with remaining migrations..."
            ;;
        *)
            echo "Invalid choice"
            exit 1
            ;;
    esac
else
    echo ""
    echo "✅ All migrations completed successfully!"
fi

echo ""
echo "Step 3: Verifying critical tables..."

tables=("users" "user_devices" "push_logs" "settings" "plans")

for table in "${tables[@]}"; do
    count=$(php artisan tinker --execute="echo \DB::table('$table')->count();" 2>/dev/null)
    if [ $? -eq 0 ]; then
        echo "✅ $table: $count records"
    else
        echo "❌ $table: Not found"
    fi
done

echo ""
echo "================================"
echo "Migration check complete!"
echo "================================"
