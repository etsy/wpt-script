# Run this script to install Webpagetest server on an EC2 instance
# Tested with image ami-82fa58eb (Ubuntu 12.04, 64 bit)

echo "Starting to install + configure WPT server. Just say yes to prompts, but read this script. There are manual steps."

# Update aptitude
sudo apt-get update

# We'll need unzip later
sudo apt-get install unzip

# Install php5 with GD
sudo apt-get install php5
sudo apt-get install php5-gd

# Install apache and enable the required mods
sudo apt-get install apache2
sudo a2enmod rewrite
sudo a2enmod expires
sudo a2enmod headers

# Install ffmpeg
sudo apt-get install ffmpeg

# Download webapgetest and copy to root doc
mkdir webpagetest
wget http://webpagetest.googlecode.com/files/webpagetest_2.7.2.zip
sudo unzip webpagetest_2.7.2.zip -d webpagetest/
sudo cp -r webpagetest/www/* /var/www/

# Remove the default apache index page so index.php loads
sudo rm /var/www/index.html

# Make sure apache owns a few key direcotries
sudo chown www-data /var/www/tmp/
sudo chown www-data /var/www/results/
sudo chown www-data /var/www/work/jobs/
sudo chown www-data /var/www/work/video/
sudo chown www-data /var/www/logs/

# Use the sample version of these settings files (you can poke around and configure further if you want)
sudo cp /var/www/settings/settings.ini.sample /var/www/settings/settings.ini
sudo cp /var/www/settings/connectivity.ini.sample /var/www/settings/connectivity.ini

# We need to set up our location settings file. At the time of the writing, the settings file locations.ini
# in this repository can be copied into /var/www/settings/locations.ini
# but this is dependent on the agents we have running and their settings.


sudo service apache2 restart

# You can add a cron tab to delete old results (at least for this year :))
# 0 0 * * * find /var/www/results/13/* -type d -mtime +7 -exec rm -rf '{}' \;
