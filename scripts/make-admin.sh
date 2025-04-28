#!/bin/bash
set -e

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Function to display script usage
display_usage() {
    echo -e "\n${YELLOW}Usage:${NC} $0 <email_address>"
    echo -e "  Example: $0 admin@example.com\n"
    echo -e "This script grants Orchid admin permissions to a user with the specified email address."
    echo -e "The user must already exist in the database.\n"
}

# Check if an email address was provided
if [ $# -eq 0 ]; then
    echo -e "${RED}Error:${NC} No email address provided."
    display_usage
    exit 1
fi

# Validate email format (basic validation)
EMAIL=$1
if [[ ! "$EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    echo -e "${RED}Error:${NC} Invalid email format: $EMAIL"
    display_usage
    exit 1
fi

echo -e "${YELLOW}Granting Orchid admin permissions to:${NC} $EMAIL"

# Run the artisan command through Laravel Sail
if ./vendor/bin/sail artisan orchid:grant-admin "$EMAIL"; then
    echo -e "\n${GREEN}Success!${NC} Admin permissions have been granted to $EMAIL"
    echo -e "You can now log in to the admin dashboard at http://localhost/admin"
else
    echo -e "\n${RED}Failed to grant admin permissions.${NC} Please check if the user exists."
    exit 1
fi

# Make the script executable
chmod +x "$0"