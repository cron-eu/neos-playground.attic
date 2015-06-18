TYPO3 Neos Docker Environment for DAV DAZ
=========================================

Abstract
--------

This is a docker based dev environment for:

https://github.com/cron-eu/daz-neos-base/tree/develop

The web-container is using the boilerplate from million12

https://github.com/million12/docker-typo3-flow-neos-abstract

Usage
-----

### First run

The needed container will be build using the latest codebase from the develop branch.

#### Setup Docker env

```
DIR=$HOME/Developer/Docker
mkdir -p $DIR && cd $DIR || exit 1
git clone --depth=1 git@github.com:cron-eu/daz-neos-base.git -b docker dav-daz
cd dav-daz
vi docker-compose.yml # put you github user name and other tweaks (ports, vhost names, branch etc.)
docker-compose up -d
```

### Upgrade Instructions

You can upgrade your existing Docker env using this steps:

```
cd $HOME/Developer/Docker/dav-daz
git pull origin docker
docker-compose stop
docker-compose rm -v
docker rmi davdaz_db davdaz_elasticsearch davdaz_web
docker-compose build
docker-compose up -d
```

### Use you own Github Fork

```
ssh-keygen -q -t rsa -N '' -f web/gh-repo-key
cat web/gh-repo-key.pub | pbcopy
vi web/Dockerfile # change repo url to be your fork
```

Setup the pub key from clipboard as a SSH Deployment Key for your private fork,
then proceed with the regular setup.

### Stop the dev env

```
cd $HOME/Developer/Docker/dav-daz
docker-compose stop
```

### Start the dev env

```
cd $HOME/Developer/Docker/dav-daz
docker-compose up -d
```

### Remove the dev env

.. and purge all data

```
cd $HOME/Developer/Docker/dav-daz
docker-compose stop
docker-compose rm -v
```

PHPStorm Development
--------------------

Copy the app dir incl. webroot locally

```
DIR=$HOME/Developer/cron/PhpStormProjects
mkdir -p "$DIR" && cd "$DIR"

rsync -av --exclude /Packages/Sites/CRON.DazSite/Resources/Public/ \
  --exclude /Packages/Sites/CRON.DazSite/layout/ \
  --exclude /Data \
  --exclude /Web/_Resources \
  -e "ssh -p 1122" www@$(boot2docker ip):typo3-app/ dav-daz
```

You will end up having a shadow copy of your app directory locally.

Now open PhpStorm and configure the docker-dev as an SFTP deployment server. Call it "daz-neos-docker". Use this Settings:

CONNECTION

* Type: SFTP
* SFTP Host: 192.168.59.103
* Port: 1122
* Root path: /data/www
* User name: www
* Auth-Type: Key-Pair
* Private-Key file: [your RSA key to access github]
* Passphrase: [your RSA key Passphrase]

Mappings

* Deployment path: /typo3-app

Check that "Test SFTP Connection" is OK.

Then open the project from the path `~/Developer/cron/PhpStormProjects/dav-daz` and you're ready to go.

Caveats
-------

### doctrine:migrate fails

Make sure that you're using a recent `million12/typo3-flow-neos-abstract` image:

```
docker pull million12/typo3-flow-neos-abstract
```
