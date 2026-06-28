# RS Digital Hub - Live Deployment Guide

## Problem: "Directory creation is only allowed in htdocs folders"
Ye error free hosting providers (InfinityFree, 000webhost) ka security restriction hai.

## Solution: Upload directories manually create karo

### Step 1: FTP/File Manager se directories banao
1. Hosting ke File Manager ya FileZilla se login karo
2. `htdocs/RS_Digital_Hub/` me jao
3. Ye folders manually banao:
   - `uploads/`
   - `uploads/products/`
   - `uploads/screenshots/`
4. Permissions `755` set karo

### Step 2: Database import karo
1. phpMyAdmin open karo
2. Naya database banao
3. `database.sql` import karo
4. `config.php` me DB credentials update karo

### Step 3: config.php update karo
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
```

### Step 4: Files upload karo
- FileZilla ya File Manager se saari files `htdocs/RS_Digital_Hub/` me upload karo

## Best Free Hosting Options

| Platform | PHP | MySQL | Upload Limit | Notes |
|----------|-----|-------|--------------|-------|
| **InfinityFree** | ✅ | ✅ | 10GB | Best free option |
| **000webhost** | ✅ | ✅ | 300MB | Good for small sites |
| **AwardSpace** | ✅ | ✅ | 1GB | Reliable |
| **Freehosting.com** | ✅ | ✅ | 10GB | Unlimited bandwidth |

## InfinityFree Setup (Recommended)
1. `infinityfree.net` pe account banao
2. "Create Account" → Subdomain choose karo (e.g., `rsdigitalhub.rf.gd`)
3. File Manager open karo → `htdocs` me jao
4. `RS_Digital_Hub` folder banao
5. Saari files upload karo
6. `uploads/`, `uploads/products/`, `uploads/screenshots/` folders banao (755 permissions)
7. phpMyAdmin se database banao aur `database.sql` import karo
8. `config.php` me DB details update karo

## Admin Login
- URL: `https://yoursite.rf.gd/RS_Digital_Hub/admin/`
- Email: `dellofficial795@gmail.com`
- Password: `Rohit@14`
