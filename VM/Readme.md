# VM with puphet

Creación de un entorno virtual para la simulación de servidores linux (Todo en local).

## Requirements
* Virtual Box
* Windows | Linux
* File Puphpet
* Bash Git - https://git-scm.com/downloads
## File Puphpet
* Go to : <a href="https://puphpet.com/" target="__black">https://puphpet.com/</a> 
* Select Distro:CentOS 7 x64
* Internal Identifier: debe ser diferente de los que ya tienes, si sale error se cambia en `D:\\LOCALDEV\\vagrantup\\TestVM\\puphpet\\config.yaml`
* IP Address: (Default, cambiar si es necesario) `192.168.56.101`
* Folder Source: similar to `D:\\LOCALDEV\\servers\\TestVM`  |  Linux  copy the path default (`/usr/serverdev/example`)
* Next - Next - Download
* Descomprimir y mover la carpeta a `D:\\LOCALDEV\\VagrantUp\\TestVM`
* Create folder `D:\\LOCALDEV\\servers\\TestVM`
* Open bash-git in `D:\\LOCALDEV\\VagrantUp\\TestVM`

## Vagrant up
* Para evitar el error `/sbin/mount.vboxsf: mounting failed with the error: No such device::`
  ```    
    vagrant plugin install vagrant-vbguest
    vagrant plugin install vagrant-winnfsd  
    vagrant plugin update
   ```
* Run  `vagrant up `
* Run `vagrant vbguest`
* Run  `vagrant ssh ` 

## In inside of server
* Run `sudo yum update`
* (Optional) List paquetes `sudo yum list installed`
* Run `sudo yum install php`
* Run `sudo yum install httpd`
* Run `sudo service httpd start`
* (Optional, si ocurre problemas con session) Run `chmod 477 /var/lib/php/session/*`  
* (Optional, si ocurre problemas con MysqlAdmin) Run `sudo yum install php-mbstring.x86_64` 


## Extras
* Windows hots virtual : `C:\WINDOWS\system32\drivers\etc\hosts`
* Linux `sudo vim /etc/hosts`
* Para evitar problemas de dominio (Intentar llmar aun virtual hosts de mismo server-local pero da error) Añadir el hots virtual también en el server (here: `sudo vim /etc/hosts`)
* (?) Problemas con laravel de permisos: `sudo vim  /etc/httpd/conf/httpd.conf`  ( se necesita cambiar el User 'www-data'  por 'vagrant', Group queda como 'www-data') 
* (?) Problema de sync con el box:  config.vm.synced_folder ".", "/vagrant", type: "virtualbox"
* Para ver el problema más a detalles: ` **sudo** systemctl status httpd.service`
* Al cambiar vagrant user, se tiene que eliminar los archivos de la carpeta session. rm y listo

## Install Node in server
* curl --silent --location https://rpm.nodesource.com/setup_8.x | sudo bash -
* sudo yum -y install nodejs
* (Activar puertos - Optional) `sudo iptables -I INPUT -p tcp --dport 3030 -j ACCEPT`
* (Activar puertos - Optional) `sudo service iptables save`
