# Диски

### Посмотреть список дисков

1. `fdisk -l`

### Форматирование и монтирование ext4

1. `mkfs -t ext4 /dev/vda`
2. `cp /etc/fstab /etc/fstab.bak`
3. `echo UUID=$(lsblk --noheadings --output UUID /dev/vda) /datadrive ext4 defaults,nofail,noexec,nosuid,noatime 0 0 >> /etc/fstab`
4. `mount -a`

### Форматирование и монтирование xfs

1. `mkfs.xfs -f /dev/vda`
2. `cp /etc/fstab /etc/fstab.bak`
3. `echo UUID=$(lsblk --noheadings --output UUID /dev/vda) /datadrive xfs defaults 0 0 >> /etc/fstab`
4. `mount -a`

### Монтирование бакетов из infra
1. Установить https://github.com/s3fs-fuse/s3fs-fuse/wiki/Fuse-Over-Amazon в соответствии с инструкцией.
2. `echo s3fs#<bucket> /mnt fuse _netdev,allow_other,use_path_request_style,url=http://<url> 0 0 >> /etc/fstab`