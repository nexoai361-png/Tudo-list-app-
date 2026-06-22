#!/data/data/com.termux/files/usr/bin/bash

# =================================================================
# TASKMASTER PRO - ENTERPRISE SETUP SCRIPT (TERMUX)
# =================================================================
# This script automates the deployment of the TaskMaster Pro suite
# directly onto a Termux environment.
# =================================================================

# Color Definitions
PRIMARY='\033[0;34m'
SUCCESS='\033[0;32m'
WARNING='\033[0;33m'
DANGER='\033[0;31m'
NC='\033[0m' # No Color

clear
echo -e "${PRIMARY}====================================================${NC}"
echo -e "${SUCCESS}    TASKMASTER PRO - AUTONOMOUS TERMUX DEPLOYMENT    ${NC}"
echo -e "${PRIMARY}====================================================${NC}"

# Step 1: System Update
echo -e "\n${PRIMARY}[1/4] Updating system packages...${NC}"
pkg update -y && pkg upgrade -y

# Step 2: Dependency Installation
echo -e "\n${PRIMARY}[2/4] Installing PHP Runtime Environment...${NC}"
if ! command -v php &> /dev/null
then
    pkg install php -y
    echo -e "${SUCCESS}✓ PHP installed successfully.${NC}"
else
    echo -e "${SUCCESS}✓ PHP is already installed.${NC}"
fi

# Step 3: File System Configuration
echo -e "\n${PRIMARY}[3/4] Configuring professional environment...${NC}"

# Check for index.php
if [ ! -f "index.php" ]; then
    echo -e "${DANGER}Critical Error: index.php not found.${NC}"
    echo -e "${WARNING}Please ensure you are running this script in the project root.${NC}"
    exit 1
fi

# Initialize Data Storage
if [ ! -f "tasks.json" ]; then
    echo "[]" > tasks.json
    echo -e "${SUCCESS}✓ Data storage initialized (tasks.json).${NC}"
fi

# Set Permissions
chmod 644 index.php
chmod 666 tasks.json
echo -e "${SUCCESS}✓ Security permissions applied.${NC}"

# Step 4: Finalization
echo -e "\n${SUCCESS}==============================================${NC}"
echo -e "${SUCCESS}    SETUP COMPLETE: SYSTEM IS READY           ${NC}"
echo -e "${SUCCESS}==============================================${NC}"
echo -e "\n${PRIMARY}Server Details:${NC}"
echo -e "Host: ${WARNING}localhost${NC}"
echo -e "Port: ${WARNING}8080${NC}"
echo -e "Entry: ${WARNING}index.php${NC}"
echo -e "\n${PRIMARY}Action:${NC} Your mobile browser will now track your tasks."
echo -e "${SUCCESS}Starting Professional PHP Server...${NC}"
echo -e "${DANGER}Note: Press Ctrl+C to shutdown server.${NC}\n"

# Execution
php -S localhost:8080 index.php
