#!/bin/bash

# Molunzaka Streaming Platform - Quick Setup Script
# This script automates the initial setup of the Laravel project

set -e

echo "🚀 Molunzaka Streaming Platform - Setup Script"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Copy .env file
echo -e "${BLUE}[1/6]${NC} Setting up environment configuration..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✓${NC} .env file created"
else
    echo -e "${YELLOW}ℹ${NC} .env file already exists"
fi

# Step 2: Build containers
echo ""
echo -e "${BLUE}[2/6]${NC} Building Docker containers..."
docker compose build

# Step 3: Start containers
echo ""
echo -e "${BLUE}[3/6]${NC} Starting containers..."
docker compose up -d

# Wait for services to be ready
echo -e "${YELLOW}ℹ${NC} Waiting for services to be ready..."
sleep 10

# Step 4: Install dependencies
echo ""
echo -e "${BLUE}[4/6]${NC} Installing Composer dependencies..."
docker compose exec -T app composer install

# Step 5: Generate APP_KEY
echo ""
echo -e "${BLUE}[5/6]${NC} Generating application key..."
docker compose exec -T app php artisan key:generate

# Step 6: Database setup
echo ""
echo -e "${BLUE}[6/6]${NC} Setting up database..."
docker compose exec -T app php artisan migrate --force

echo ""
echo -e "${GREEN}✓ Setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "  • API:        ${BLUE}http://localhost:8000${NC}"
echo "  • Admin:      ${BLUE}http://localhost:8000/admin${NC}"
echo "  • Docs:       See ${BLUE}README.md${NC}"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  • View logs:      ${BLUE}docker compose logs -f app${NC}"
echo "  • Run tests:      ${BLUE}docker compose exec app php artisan test${NC}"
echo "  • Database seed:  ${BLUE}docker compose exec app php artisan db:seed${NC}"
echo "  • Tinker REPL:    ${BLUE}docker compose exec app php artisan tinker${NC}"
echo ""
echo "Happy streaming! 🎬"
