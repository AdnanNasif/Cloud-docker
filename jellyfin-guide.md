# The process of installing and activating jellyfindocker pull jellyfin/jellyfin

```docker pull jellyfin/jellyfin```

```
mkdir /home/adnan/mygithub/Cloud-docker/jellyfin/config
mkdir /home/adnan/mygithub/Cloud-docker/jellyfin/cache
```
```
 docker run -d \
 -p 8011:8096 \
 --name Jellyfin \
 --volume /home/adnan/mygithub/Cloud-docker/jellyfin/config:/config \
 --volume /home/adnan/mygithub/Cloud-docker/jellyfin/cache:/cache \
 --mount type=bind,source=/home/adnan/mygithub/Cloud-docker/jellyfin/media,target=/media \
 --restart=unless-stopped \
 jellyfin/jellyfin
```
