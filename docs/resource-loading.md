# Resource Loading in Bausystem Provisioner

## Overview

When building a Composer package that includes bundled resources (templates, configuration files, etc.), you need to locate these resources relative to the **package itself**, not relative to the **script that uses the package**.

This document explains how Bausystem Provisioner handles resource loading and why it's designed this way.

## The Problem

### Scenario

1. **Package structure**: `provisioner/src/resources/nginx.conf`
2. **Package location**: Installed via Composer in `vendor/bausystem/provisioner/`
3. **Running script**: `/tmp/deploy-12345/setup.php`
4. **Challenge**: How does the package code find `nginx.conf`?

### Why `SCRIPT_ROOT` Doesn't Work

The `Bausystem::init()` method defines `SCRIPT_ROOT` as the root directory of the **running script**:

```php
// In Bausystem.php
$script_root = substr(__DIR__, 0, $vendor_string_pos);
define('SCRIPT_ROOT', $script_root);
// Result: /tmp/deploy-12345/
```

If you try to load a resource like this:
```php
// In Server.php (WRONG APPROACH)
$nginx_conf = file_get_contents(SCRIPT_ROOT . '/provisioner/src/resources/nginx.conf');
// Tries to read: /tmp/deploy-12345/provisioner/src/resources/nginx.conf
// But actual location: /tmp/deploy-12345/vendor/bausystem/provisioner/src/resources/nginx.conf
```

**This fails** because:
- ✗ `SCRIPT_ROOT` points to where the script is running from
- ✗ The provisioner package is in `vendor/bausystem/provisioner/`
- ✗ The path doesn't exist

## The Solution: Use `__DIR__`

### Correct Approach

Use `__DIR__` to locate resources relative to the **package file itself**:

```php
// In Server.php (CORRECT APPROACH)
class Server {
    public static function writeNginxConf() {
        // Server.php is at: provisioner/src/class/Nginx/Server.php
        // nginx.conf is at: provisioner/src/resources/nginx.conf
        $nginx_conf_path = dirname(__DIR__, 2) . '/resources/nginx.conf';
        
        $nginx_conf = file_get_contents($nginx_conf_path);
        // ...
    }
}
```

### Path Resolution

```
__DIR__                           → vendor/bausystem/provisioner/src/class/Nginx/
dirname(__DIR__)                  → vendor/bausystem/provisioner/src/class/
dirname(__DIR__, 2)               → vendor/bausystem/provisioner/src/
dirname(__DIR__, 2) . '/resources/' → vendor/bausystem/provisioner/src/resources/
```

## When to Use Each Approach

### Use `SCRIPT_ROOT` for:
- ✅ Application-level configuration files
- ✅ User data directories
- ✅ Log files
- ✅ Deployment-specific paths
- ✅ Files that the **script creates or manages**

Example:
```php
$log_file = SCRIPT_ROOT . '/logs/deployment.log';
$config_file = SCRIPT_ROOT . '/config.yaml';
```

### Use `__DIR__` with `dirname(__DIR__, 2)` for:
- ✅ Package-bundled resources (templates, configs)
- ✅ Files shipped with the package
- ✅ Resources that are **part of the package code**

Example:
```php
$template = dirname(__DIR__, 2) . '/templates/nginx.conf';
$default_config = dirname(__DIR__, 2) . '/resources/defaults.yaml';
```

## Common Pitfalls

### ❌ **Don't use relative paths without context**
```php
// BAD - Depends on current working directory
$config = file_get_contents('../../resources/nginx.conf');
```

### ❌ **Don't assume package location**
```php
// BAD - Assumes specific vendor structure
$config = file_get_contents('/vendor/bausystem/provisioner/src/resources/nginx.conf');
```

### ✅ **Always use `__DIR__` for package resources**
```php
// GOOD - Works regardless of installation location
$config = file_get_contents(dirname(__DIR__, 2) . '/resources/nginx.conf');
```

## Deployment Context

When using the Bausystem CLI `deploy.sh`:

1. **Local development**: Package is in `~/Projects/.../vendor/bausystem/provisioner/`
2. **Remote deployment**: Package is in `/tmp/bausystem-uuid/vendor/bausystem/provisioner/`
3. **Using `__DIR__`**: Works in both contexts ✅
4. **Using `SCRIPT_ROOT`**: Only works if you manually construct vendor paths ❌

## Summary

| Constant | Points To | Use For |
|----------|-----------|---------|
| `SCRIPT_ROOT` | Running script's root directory | Application files, logs, user data |
| `__DIR__` | Current PHP file's directory | Package resources, templates, bundled configs |

**Key principle**: Package code should be **self-contained** and use `__DIR__` to locate its own resources, making it portable and independent of where it's installed.
