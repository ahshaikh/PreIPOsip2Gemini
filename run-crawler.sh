#!/bin/bash

# Playwright Crawler Runner Script
# This script makes it easy to run the crawler with different configurations

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 PreIPO SIP Playwright Crawler${NC}\n"

# Check if .env.crawler exists
if [ ! -f .env.crawler ]; then
    echo -e "${RED}❌ Error: .env.crawler file not found${NC}"
    echo -e "${YELLOW}Creating .env.crawler from example...${NC}"
    cp .env.crawler.example .env.crawler
    echo -e "${GREEN}✅ Created .env.crawler${NC}"
    echo -e "${YELLOW}⚠️  Please edit .env.crawler with your credentials before running the crawler${NC}"
    exit 1
fi

# Load environment variables
export $(cat .env.crawler | grep -v '^#' | xargs)

# Check if route-map.json exists
if [ ! -f route-map.json ]; then
    echo -e "${RED}❌ Error: route-map.json not found${NC}"
    echo -e "${YELLOW}Please run the route extraction script first${NC}"
    exit 1
fi

# Check if Node dependencies are installed
if [ ! -d node_modules/@playwright ]; then
    echo -e "${YELLOW}📦 Installing dependencies...${NC}"
    npm install @playwright/test playwright json2csv dotenv ts-node typescript @types/node
    echo -e "${GREEN}✅ Dependencies installed${NC}"
fi

# Check if Playwright browsers are installed
if [ ! -d ~/.cache/ms-playwright ] && [ ! -d ~/Library/Caches/ms-playwright ]; then
    echo -e "${YELLOW}🌐 Installing Playwright browsers...${NC}"
    npx playwright install chromium
    echo -e "${GREEN}✅ Browsers installed${NC}"
fi

# Create reports directory if it doesn't exist
mkdir -p reports/screenshots

# Parse command line arguments
MODE="default"
while [[ $# -gt 0 ]]; do
    case $1 in
        --headless)
            export HEADLESS=true
            echo -e "${BLUE}🔇 Headless mode enabled${NC}"
            shift
            ;;
        --headed)
            export HEADLESS=false
            echo -e "${BLUE}🖥️  Headed mode enabled (you'll see the browser)${NC}"
            shift
            ;;
        --screenshots)
            export SCREENSHOTS=true
            echo -e "${BLUE}📸 Screenshots enabled${NC}"
            shift
            ;;
        --no-screenshots)
            export SCREENSHOTS=false
            echo -e "${BLUE}📸 Screenshots disabled${NC}"
            shift
            ;;
        --help)
            echo -e "${GREEN}Usage:${NC}"
            echo -e "  ./run-crawler.sh [options]"
            echo -e ""
            echo -e "${GREEN}Options:${NC}"
            echo -e "  --headless          Run in headless mode (no visible browser)"
            echo -e "  --headed            Run with visible browser"
            echo -e "  --screenshots       Take screenshots of errors"
            echo -e "  --no-screenshots    Don't take screenshots"
            echo -e "  --help              Show this help message"
            echo -e ""
            echo -e "${GREEN}Examples:${NC}"
            echo -e "  ./run-crawler.sh --headless --screenshots"
            echo -e "  ./run-crawler.sh --headed"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo -e "Run with --help for usage information"
            exit 1
            ;;
    esac
done

# Display configuration
echo -e "\n${GREEN}Configuration:${NC}"
echo -e "  Frontend URL: ${FRONTEND_URL}"
echo -e "  Backend URL: ${BACKEND_URL}"
echo -e "  Headless: ${HEADLESS}"
echo -e "  Screenshots: ${SCREENSHOTS}"
echo -e "  User Email: ${USER_EMAIL:-'❌ Not set'}"
echo -e "  Admin Email: ${ADMIN_EMAIL:-'❌ Not set'}"

# Warning if credentials not set
if [ -z "$USER_EMAIL" ] || [ -z "$ADMIN_EMAIL" ]; then
    echo -e "\n${YELLOW}⚠️  Warning: USER or ADMIN credentials not set${NC}"
    echo -e "${YELLOW}   Some routes will be skipped. Edit .env.crawler to add credentials.${NC}"
fi

# Confirm before running
echo -e "\n${BLUE}Press Enter to start crawling, or Ctrl+C to cancel...${NC}"
read

# Run the crawler
echo -e "\n${GREEN}🏃 Starting crawler...${NC}\n"
ts-node playwright-crawler.ts

# Check if crawler succeeded
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✅ Crawler completed successfully!${NC}"
    echo -e "\n${BLUE}📊 Reports saved to: ./reports/${NC}"

    # List generated reports
    echo -e "\n${GREEN}Generated files:${NC}"
    ls -lh reports/*.json reports/*.csv 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'

    # Count screenshots if any
    SCREENSHOT_COUNT=$(ls -1 reports/screenshots/*.png 2>/dev/null | wc -l)
    if [ $SCREENSHOT_COUNT -gt 0 ]; then
        echo -e "\n${BLUE}📸 Screenshots: ${SCREENSHOT_COUNT} files in reports/screenshots/${NC}"
    fi
else
    echo -e "\n${RED}❌ Crawler failed with errors${NC}"
    exit 1
fi
