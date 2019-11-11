# Viper Installer

---

## Install

### Composer

We recommend this command for linux based web servers.

```
composer require viper/installer
```

Recommended that you install it globally for development environments.

```
composer global require viper/installer
```

### Authorization

@todo Implement Viper Lab credentials...

### New Project

Once installed, the `viper new` command will create a fresh Viper installation 
in the directory you specify. For instance, `viper new blog` will create a directory 
named `blog` containing a fresh Viper installation with all of Viper's dependencies 
already installed:

```
viper new blog
```

```
composer create-project --prefer-dist viper/viper blog
```
