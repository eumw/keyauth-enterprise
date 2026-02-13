#!/bin/bash

# Check if the script is being run as the root user
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root"
    exit 1
fi

# Check if the Ubuntu version is 22.04
ubuntu_version=$(lsb_release -r -s)
if [ "$ubuntu_version" != "22.04" ]; then
    echo "This script is only compatible with Ubuntu 22.04"
    exit 1
fi

# Function to generate a random password of specified length
generate_random_password() {
    local length=$1
    tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c "$length"
}

# Function to get the public IP address of the machine
get_public_ip() {
    curl -s ifconfig.me/ip
}


# Clone the KeyAuth repository
echo "Cloning KeyAuth repository..."
git clone https://github.com/KeyAuth/KeyAuth-Source-Code
echo "KeyAuth repository cloned."

# Prompt the user to paste their SSH public key
echo "Please paste your SSH public key:"
read -r ssh_public_key

# Add the SSH public key to authorized_keys
echo "$ssh_public_key" | sudo tee -a /root/.ssh/authorized_keys

# Ask user whether to generate a random MySQL password or provide their own
echo "Do you want to generate a random MySQL root password? (y/n)"
read -r generate_password

if [ "$generate_password" = "y" ]; then
    # Generate a random password with a length of 42 characters
    mysql_password=$(generate_random_password 42)
    echo "A random password has been generated."
else
    # Prompt the user to enter their own password
    echo "Please enter your desired MySQL root password:"
    read -s mysql_password
fi

# Update and install necessary packages
echo "Installing necessary packages..."
sudo apt update
sudo apt install -y nginx
sudo apt install -y mysql-server
sudo apt install -y php8.1-fpm php-mysql
sudo apt install -y firewalld
sudo apt install -y redis-server
sudo apt-get install -y php-redis
sudo apt-get install -y php-curl

# Configure UFW to allow Nginx HTTP
sudo ufw app list
sudo ufw allow 'Nginx HTTP'

# Configure Firewalld
sudo firewall-cmd --permanent --zone=public --add-port=80/tcp
sudo firewall-cmd --reload

# Secure MySQL installation with the provided/generated password
sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$mysql_password';
EOF

# Move the redis.conf file to /etc/redis
if [ -f "./redis.conf" ]; then
    sudo mv ./redis.conf /etc/redis/
    echo "redis.conf file moved to /etc/redis/"
else
    echo "redis.conf file not found in the current directory."
fi

# Move the php.ini file to /etc/php/8.1/fpm
if [ -f "./setup-script/php.ini" ]; then
    sudo mv ./php.ini /etc/php/8.1/fpm/
    echo "php.ini file moved to /etc/php/8.1/fpm/"
else
    echo "php.ini file not found in the current directory."
fi

# Move the www.conf file to /etc/php/8.1/fpm/pool.d
if [ -f "./setup-script/www.conf" ]; then
    sudo mv ./www.conf /etc/php/8.1/fpm/pool.d/
    echo "www.conf file moved to /etc/php/8.1/fpm/pool.d/"
else
    echo "www.conf file not found in the current directory."
fi

# Move the nginx.conf file to /etc/nginx
if [ -f "./setup-script/nginx.conf" ]; then
    sudo mv ./nginx.conf /etc/nginx/
    echo "nginx.conf file moved to /etc/nginx/"
else
    echo "nginx.conf file not found in the current directory."
fi

# Move the test.php file to /usr/share/nginx/html
if [ -f "./setup-script/test.php" ]; then
    sudo mv ./test.php /usr/share/nginx/html/
    echo "test.php file moved to /usr/share/nginx/html/"
else
    echo "test.php file not found in the current directory."
fi

# Restart services
sudo systemctl restart redis.service
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# Get the public IP address
public_ip=$(get_public_ip)

# Prompt the user to check if the test page has loaded
echo "Please check if the test page has loaded by visiting http://$public_ip/test.php"
echo "Has the test page loaded? (y/n)"
read -r page_loaded

if [ "$page_loaded" != "y" ]; then
    echo "Please ensure the Nginx service is running and accessible."
    exit 1
fi

# Delete the test.php file
sudo rm -f /usr/share/nginx/html/test.php
echo "test.php file deleted."

# Copy the specified folders and files to /usr/share/nginx/html
sudo cp -r ./KeyAuth-Source-Code/* /usr/share/nginx/html/

# Copy the specified folders and files to /usr/share/nginx/html
sudo cp -r ./app /usr/share/nginx/html/
sudo cp -r ./auth /usr/share/nginx/html/
sudo cp -r ./includes /usr/share/nginx/html/
sudo cp -r ./login /usr/share/nginx/html/
sudo cp -r ./panel /usr/share/nginx/html/
sudo cp ./favicon.ico /usr/share/nginx/html/
sudo cp ./safe-harbot.txt /usr/share/nginx/html/
sudo cp ./index.html /usr/share/nginx/html/

echo "Folders and files copied to /usr/share/nginx/html/"

# Remove non-required files
sudo rm /usr/share/nginx/html/db_structure.sql
sudo rm /usr/share/nginx/html/LICENSE
sudo rm /usr/share/nginx/html/README.md

# Create the db in MySQL
mysql -u root -p"$mysql_password" <<EOF
CREATE DATABASE main;
EOF

echo "Database 'main' created in MySQL."

# Import the structure from db_structure.sql
mysql -u root -p"$mysql_password" main < db_structure.sql

echo "Database structure imported into 'main' database."


# Prompt the user for inputs for connection.php file
echo "Please enter the webhook URL for login logs and keys created (leave blank for none):"
read -r log_webhook

echo "Please enter the webhook URL for admin actions (leave blank for none):"
read -r admin_webhook

echo "Please enter the KeyAuth Stats token (leave blank for none):"
read -r keyauth_stats_token

# Create the connection.php file with user input
cat <<EOF | sudo tee /usr/share/nginx/html/includes/credentials.php > /dev/null
<?php
error_reporting(0);

\$databaseHost = "localhost";
\$databaseUsername = "root";
\$databasePassword = "$mysql_password";
\$databaseName = "main";

\$mysqlRequireSSL = false; // in case the MySQL server requires SSL

\$logwebhook = "$log_webhook"; // discord webhook which receives login logs and keys created
\$adminwebhook = "$admin_webhook"; // discord webhook which receives admin actions

\$redisServers = []; // URLs to purge redis keys from each server (used on live KeyAuth website only)
\$redisPass = "";

\$keyauthStatsToken = "$keyauth_stats_token"; // discord bot token for KeyAuth Stats
\$webhookun = "KeyAuth Logs"; // webhook username
\$adminwebhookun = "KeyAuth Admin Logs"; // admin webhook's username

\$awsAccessKey = ""; // used for AWS SES to send emails
\$awsSecretKey = ""; // used for AWS SES to send emails
EOF

echo "connection.php file created in /usr/share/nginx/html/includes/"

# Restart PHP
sudo systemctl restart php8.1-fpm

# Prompt user to create account for reseller
echo "Head over to http://$public_ip/register/ and make your account"

# Prompt the user to enter the username
echo "Please enter your username:"
read -r username

# Update the 'accounts' table in the 'main' database
mysql -u root -p"$mysql_password" main <<EOF
UPDATE accounts SET role = 'seller', expires = 2224663363 WHERE username = '$username';
EOF

echo "Row updated in 'accounts' table."

# Prompt user for optional Cloudflare IP script execution
echo "Do you want to run the Cloudflare IPs only script? (y/n)"
read -r run_cloudflare_script

if [ "$run_cloudflare_script" = "y" ]; then
    git clone https://github.com/kingcc/cloudflare-ips-only.git
    cd cloudflare-ips-only
    sudo bash ./host.sh
fi

# Display generated MySQL password if it was generated
if [ "$generate_password" = "y" ]; then
    echo "MySQL root password: $mysql_password"
fi

echo "Do you want to reboot the system now? (y/n)"
read -r reboot_option

if [ "$reboot_option" = "y" ]; then
    echo "Rebooting the system..."
    sudo reboot
else
    echo "Skipping system reboot."
fi

echo "Setup complete."
