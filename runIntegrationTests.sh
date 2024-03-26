#!/bin/bash

# Which container to choose: podman by default, else docker
CTN="docker"
PWD=$( pwd )


ID=$( id -u )
# fusioniam default user id in container
MYUID=1000
# fusioniam default group id in container
MYGID=1000
# uidmap format: rootless user: container_uid:intermediate_uid:amount
#                rootful user: container_uid:host_uid:amount
# rootless user:
# map podman user uid (0) to fusioniam container uid (1000) + map lowest user uid in /etc/subuid (1) to root container uid (0)
# rootful user:
# map root (0) to fusioniam container uid (1000) + map user uid 100000 to root container uid (0)
UIDMAP=$( [ "$CTN" = "podman" ] && if [ $(ID) -eq 0 ]; then echo "--uidmap $MYUID:0:1 --uidmap 0:100000:1"; else echo "--uidmap $MYUID:0:1 --uidmap 0:1:1"; fi || echo "" )
GIDMAP=$( [ "$CTN" = "podman" ] && if [ $(ID) -eq 0 ]; then echo "--gidmap $MYGID:0:1 --gidmap 0:100000:1"; else echo "--gidmap $MYGID:0:1 --gidmap 0:1:1"; fi || echo "" )



# Get OpenLDAP-LTB docker image before running tests
$CTN pull gitlab.ow2.org:4567/fusioniam/fusioniam/fusioniam-openldap-ltb:snapshot

# run docker image
mkdir -p run/volumes/ldap-data run/volumes/ldap-config
$CTN run \
        --env-file=./run/ENVVAR.example \
        -v $PWD/run/volumes/ldap-data:/usr/local/openldap/var/openldap-data \
        -v $PWD/run/volumes/ldap-config:/usr/local/openldap/etc/openldap/slapd.d \
        -v $PWD/run/volumes/ldap-tls:/usr/local/openldap/etc/openldap/tls \
        --rm=true \
        --network-alias=ltb-directory-server \
        -p 127.0.0.1:33389:33389 \
        --name=ltb-directory-server \
        --detach=true \
        $UIDMAP \
        $GIDMAP \
        gitlab.ow2.org:4567/fusioniam/fusioniam/fusioniam-openldap-ltb:snapshot

# Check when started
while ! $CTN logs ltb-directory-server 2>&1 | grep -q "slapd starting";
do
    # Wait for docker container to be up and running
    echo "slapd starting, please wait"
    sleep 1
done


# Run tests
echo "Starting tests"
vendor/bin/phpunit tests/IntegrationTests

# Stop and remove openldap container and volumes
$CTN stop ltb-directory-server
rm -rf run/volumes

