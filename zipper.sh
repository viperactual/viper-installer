#!/usr/bin/env bash
wget https://viper-lab.com/viper/framework/-/archive/master/framework-master.zip
unzip framework-master.zip -d working
cd working/viper-master
composer install
zip -ry ../../viper-craft.zip .
cd ../..
mv viper-craft.zip public/viper-craft.zip
rm -rf working
rm framework-master.zip
