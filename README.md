# Viper Installer

---

## Install

### Composer

We recommend this command for linux based web servers.

```bash
composer require viper/installer
```

Recommended that you install it globally for development environments.

```bash
composer global require viper/installer
```

### Authorization

Coming soon. For more information, please visit [Viper Lab](http://www.viper-lab.com).

### New Project

Once installed, the `viper new` command will create a fresh Viper installation 
in the directory you specify. For instance, `viper new api` will create a directory 
named `api` containing a fresh Viper installation with all of Viper's dependencies 
already installed:

#### Linux

```bash
viper new api
```

```bash
composer create-project --prefer-dist viper/viper api
```

#### Windows

If you are planning on installing Viper's dependencies on a Windows OS running locally,
then you may have to provided a few extra steps.

1. Create new `api` project directory.

```bash
viper new api
```

2. Followed by running these commands in order.

```bash
composer install --no-scripts
composer run-script post-root-package-install
composer run-script post-create-project-cmd
composer run-script post-autoload-dump
```

### Docker

Additionally, you can install **Docker** containers for your project.

```bash
viper new docker
```

### Vagrant

We choose to use **Vagrant** for building our virtual machines.

```bash
viper new vagrant
```
