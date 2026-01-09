#!/bin/bash

# Script to start Laravel backend server
# Run this from anywhere in the project

BACKEND_DIR="/home/user/PreIPOsip2Gemini/backend"

echo "🚀 Starting Laravel Backend Server..."
echo ""

cd "$BACKEND_DIR" || exit 1

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "❌ ERROR: Composer dependencies not installed!"
    echo ""
    echo "Please run:"
    echo "  cd $BACKEND_DIR"
    echo "  composer install"
    echo ""
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️  WARNING: .env file not found!"
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo "✅ Created .env file"
    echo ""
    echo "Generating application key..."
    php artisan key:generate
    echo "✅ Application key generated"
    echo ""
fi

# Check if port 8000 is already in use
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "⚠️  Port 8000 is already in use!"
    echo ""
    echo "Existing process:"
    lsof -Pi :8000 -sTCP:LISTEN
    echo ""
    read -p "Kill existing process and restart? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        lsof -Pi :8000 -sTCP:LISTEN -t | xargs kill -9
        echo "✅ Killed existing process"
    else
        echo "❌ Aborting"
        exit 1
    fi
fi

echo "🔧 Starting Laravel development server..."
echo "   URL: http://localhost:8000"
echo "   API Base: http://localhost:8000/api/v1"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php artisan serve --host=0.0.0.0 --port=8000
