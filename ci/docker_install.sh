#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && [[ ! -e /.dockerinit ]] && exit 0

set -xe

# Install git (the php image doesn't have it) which is required by composer
apt-get update -yqq
apt-get install php-zip git -yqq

cd /opt/
git clone https://git.versat.azcuba.cu/backend/composer.git
composer/install.sh
composer/install.sh