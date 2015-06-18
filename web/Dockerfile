FROM million12/typo3-flow-neos-abstract:latest
MAINTAINER Remus Lazar <rl@cron.eu>

# ENV: Install custom Flow version
# Default: master
ENV T3APP_BUILD_BRANCH master

# ENV: Repository for installed TYPO3 app
# Default: git://git.typo3.org/Flow/Distributions/Base.git
ENV T3APP_BUILD_REPO_URL https://github.com/cron-eu/neos-playground.git

# ENV: Custom composer install params
# Default: --dev --prefer-source
ENV T3APP_BUILD_COMPOSER_PARAMS --no-dev --prefer-dist --optimize-autoloader

# ENV: Import following site package
ENV T3APP_NEOS_SITE_PACKAGE CRON.Playground

# change the PHP timezone to Europe/Berlin
RUN echo "date.timezone = Europe/Berlin" >> /etc/php.d/zz-php.ini

# disable the rewrite rules for Neos 2.0 compatibility
RUN sed -i -r 's/include .*flow-rewrites.conf/# &/g' /build-typo3-app/vhost.conf

# hardcode LC_ALL to avoid some german umlaute issues
RUN echo "export LC_ALL=en_US.UTF-8" >> /build-typo3-app/.bash_profile

# add private rsa key to access the private repos and run pre-install
# script
#ADD gh-repo-key /
RUN \
#  chmod 600 /gh-repo-key && \
#  echo "IdentityFile /gh-repo-key" >> /etc/ssh/ssh_config && \
  . /build-typo3-app/pre-install-typo3-app.sh
