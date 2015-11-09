#!/bin/sh
sudo echo "127.0.0.1  friendship-api.dev" >> /etc/hosts
sudo cp nginx/sites/friendship-api.dev /etc/nginx/sites-enabled/