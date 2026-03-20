# TSSE8 → UNIT3D Torrent File Migration Guide

## Overview

The TSSE8 database migration **transfers metadata only** (names, descriptions, stats, categories, etc.). The actual `.torrent` files must be migrated separately. This document provides step-by-step instructions.

---

## Problem Statement

When torrents are migrated from TSSE8 without their files:
- The database records exist with `file_name` references
- The actual `.torrent` files don't exist on disk
- Attempting to **edit, re-seed, or download** these torrents fails with "filename already exists" or file-not-found errors
- Users cannot manage or download the torrent files

---

## Solution: Three Migration Approaches

### Overview
This guide covers three ways to migrate torrent **files** and **images** (covers/banners) from TSSE8 to UNIT3D.

### **Option A: Manual File & Image Copy (Simplest)**

Use this if you have direct SSH/filesystem access to both systems.

#### Prerequisites
- SSH access to both old TSSE8 server and new UNIT3D server
- `rsync` or `scp` available on your system

#### Step 1: Locate TSSE8 Torrent Files Directory

In your TSSE8 installation, find where `.torrent` files are stored. Common locations:
```bash
# TSSE8 typical locations
/var/www/vhosts/tracker/httpdocs/files/torrents/      # Plesk
/home/username/public_html/files/torrents/             # cPanel
/opt/tsse8/files/torrents/                             # Custom install
~/public_html/files/torrents/                          # Shared hosting
```

**To find the exact path**, check your TSSE8 configuration file:
```bash
grep -r "torrent.*path\|files.*dir" /path/to/tsse8/config
```

#### Step 1b: Locate TSSE8 Torrent Images Directory

In your TSSE8 installation, torrent cover and banner images are typically stored in:
```bash
# Common image storage locations
/var/www/vhosts/tracker/httpdocs/images/torrents/      # All images
/var/www/vhosts/tracker/httpdocs/images/               # Check subdirectories
```

Look for:
- `torrent-cover_*.jpg` — Torrent cover artwork
- `torrent-banner_*.jpg` — Torrent banner images
- or variations like `cover_*.jpg`, `banner_*.jpg`, etc.

#### Step 2: Copy Files & Images to UNIT3D Server

From your local machine or jump host:
```bash
# Option 1: Using rsync (faster, preserves structure)

# Copy .torrent files
rsync -avz --progress \
  root@old-tsse8.com:/path/to/tsse8/files/torrents/ \
  root@unit3d.com:/var/www/vhosts/betaups.site/httpdocs/storage/app/files/torrents/files/

# Copy images (covers and banners)
rsync -avz --progress \
  root@old-tsse8.com:/path/to/tsse8/images/torrents/ \
  root@unit3d.com:/var/www/vhosts/betaups.site/httpdocs/storage/app/images/torrents/

# Option 2: Using scp + tar (when rsync unavailable)

# Copy .torrent files and images together
ssh root@old-tsse8.com "tar -czf - -C /path/to/tsse8 files/ images/" \
  | ssh root@unit3d.com "tar -xzf - -C /var/www/vhosts/betaups.site/httpdocs/storage/app"
```

#### Step 3: Verify Permissions and Ownership

After copying:
```bash
# On UNIT3D server
cd /var/www/vhosts/betaups.site/httpdocs

# Fix ownership and permissions (adjust username to your web user)
chown -R nobody:nobody storage/app/files storage/app/images
chmod -R 755 storage/app/files
chmod -R 755 storage/app/images

# Verify files and images copied successfully
ls -lh storage/app/files/torrents/files/ | head -20
ls -lh storage/app/images/torrents/covers/ | head -20
ls -lh storage/app/images/torrents/banners/ | head -20

wc -l storage/app/files/torrents/files/*  # Count torrent files
wc -l storage/app/images/torrents/covers/* # Count cover images
```

---

### **Option B: Automated Migration Feature (Recommended)**

Use the enhanced migration command with file and image copying support.

#### Prerequisites
- TSSE8 torrent files directory accessible via SSH/NFS
- TSSE8 torrent images directory accessible via SSH/NFS
- UNIT3D migration service deployed
- Enough disk space: `# of torrents × (avg_file_size + avg_image_size)` (typically 50KB-500KB per torrent + 100KB-1MB per image set)

#### Step 1: Run Migration with File & Image Copying

```bash
# SSH to your UNIT3D server
ssh root@unit3d.com

# Navigate to application
cd /var/www/vhosts/betaups.site/httpdocs

# Run migration with both files and images
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --host=your-tsse8-db-host \
  --database=tsse8_db \
  --username=tsse8_user \
  --password='your-password' \
  --tables=torrents \
  --source-torrent-path=/var/mounted/tsse8/files/torrents \
  --source-images-path=/var/mounted/tsse8/images/torrents \
  --copy-files \
  --copy-images \
  --page-size=500 \
  --dry-run
```

#### Step 2: Verify Dry Run Output

Review the output:
```
[20:45:23] Starting torrent migration with file and image copying...
[20:45:23] Batch 1: Processing 500 torrents (0-499)
[20:45:24]   ✓ Imported: 498 torrents
[20:45:24]   ✓ Files: copied 487, missing 11
[20:45:24]   ✓ Images: copied 856 (covers+banners), missing 140
[20:45:24]   ⚠ Skipped: 2 torrents (missing info_hash)

[20:45:25] Batch complete: 498 torrent records + files + images imported
[20:45:25] Total across all batches:
  - Imported: 498 torrents
  - Files copied: 487, missing: 11
  - Images copied: 856, missing: 140
  - Total disk space used: 256 MB (files + images)
```

#### Step 3: Run With Actual Migration

Once verified, remove `--dry-run`:
```bash
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --host=your-tsse8-db-host \
  --database=tsse8_db \
  --username=tsse8_user \
  --password='your-password' \
  --tables=torrents \
  --source-torrent-path=/var/mounted/tsse8/files/torrents \
  --source-images-path=/var/mounted/tsse8/images/torrents \
  --copy-files \
  --copy-images \
  --page-size=500
```

**Command Options:**
- `--source-torrent-path` — Path to TSSE8 torrent files directory (required if using `--copy-files`)
- `--source-images-path` — Path to TSSE8 images directory (required if using `--copy-images`)
- `--copy-files` — Enable automatic `.torrent` file copying
- `--copy-images` — Enable automatic image (cover/banner) copying
- `--page-size=500` — Process in batches of 500 torrents (adjust for your server)
- `--dry-run` — Test without making changes

---

### **Option C: Network Mount (For Remote TSSE8)**

If TSSE8 is on a different server with network access, mount and copy both files and images.

#### Step 1: Mount TSSE8 Storage

```bash
# On UNIT3D server
mkdir -p /mnt/tsse8-storage

# Using NFS (if available)
mount -t nfs tsse8-server.com:/path/to/storage /mnt/tsse8-storage

# Or using SMB (if Windows-based TSSE8)
mount -t cifs //tsse8-server.com/storage /mnt/tsse8-storage \
  -o username=admin,password=secret,uid=nobody,gid=nobody

# Verify mount contains both files and images
ls -lh /mnt/tsse8-storage/
ls -lh /mnt/tsse8-storage/files/torrents/
ls -lh /mnt/tsse8-storage/images/torrents/
```

#### Step 2: Run Migration

```bash
cd /var/www/vhosts/betaups.site/httpdocs

/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --host=your-tsse8-db-host \
  --database=tsse8_db \
  --username=tsse8_user \
  --password='your-password' \
  --tables=torrents \
  --source-torrent-path=/mnt/tsse8-storage/files/torrents \
  --source-images-path=/mnt/tsse8-storage/images/torrents \
  --copy-files \
  --copy-images \
  --page-size=500
```

---

## Critical Requirement: File Consistency for Continuous Seeding

### The Problem

Users currently seeding torrents on TSSE8 have torrent files in their torrent clients. When they migrate to UNIT3D:

- Torrent clients identify torrents by **info_hash** (SHA1 of the "info" dictionary)
- If the .torrent **file content changes**, the info_hash changes
- Users' clients won't recognize the torrent as the same one they're seeding
- **Seeding will stop** and they'll have to add the torrent again

### The Solution

**Preserve the exact .torrent files from TSSE8** so the info_hash remains identical:

```
TSSE8 file:      movie.torrent → info_hash = abc123...
Migration:       Copy movie.torrent as-is
UNIT3D file:     movie.torrent → info_hash = abc123... ✓ MATCHES!
Result:          User's client recognizes it as the same torrent ✓
```

### Verification

After migration, verify that all torrent files are consistent:

```bash
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --tables=verify_torrent_files \
  --page-size=500
```

**Output will show:**
- ✓ Valid torrents (files exist AND info_hash matches database)
- ✗ Missing files (database record exists but .torrent file not on disk)  
- ✗ Hash mismatches (file exists but info_hash doesn't match - FILE WAS MODIFIED!)

**If all results show "✓ Valid":** Migration is complete and users can continue seeding ✓

**If you see "✗ Missing files":** Users cannot download/seed those torrents. Copy them manually (see Step 2 above).

**If you see "✗ Hash mismatches":** The .torrent files were modified or new ones were created. This is a data integrity problem - restore originals from TSSE8 backup.

---

### How Files Are Matched

The migration matches files by **filename** from the TSSE8 database:

```
TSSE8 database:  filename = "MyMovie.2024.S01E01.torrent"
                 ↓
UNIT3D target:   storage/app/files/torrents/files/MyMovie.2024.S01E01.torrent
```

If filename is NULL in TSSE8, a fallback name is generated:
```
Torrent name: "The Quick Brown Fox"
              ↓ (slugified)
Fallback:     storage/app/files/torrents/files/the-quick-brown-fox.torrent
```

### How Images Are Matched

The migration looks for images using the **source torrent ID**:

```
TSSE8 source:    torrent ID = 42
                 ↓ searches for images matching:
                 - torrent-cover_42.jpg
                 - cover_42.jpg
                 - torrent-banner_42.jpg
                 - banner_42.jpg
                 ↓
UNIT3D target:   storage/app/images/torrents/covers/torrent-cover_{NEW_ID}.jpg
                 storage/app/images/torrents/banners/torrent-banner_{NEW_ID}.jpg
```

Where `{NEW_ID}` is the newly assigned UNIT3D torrent ID after import.

### Naming Conflicts

If two torrents would receive the same filename (rare):
```
Torrent 1: "Movie"  → fallback: movie.torrent
Torrent 2: "Movie"  → fallback: movie.torrent (CONFLICT!)
```

**Resolution during `--copy-files`:**
- First torrent gets: `movie.torrent`
- Second torrent gets: `movie-2.torrent` (auto-numbered suffix)

The database is updated automatically to reflect the new filename.

---

## Troubleshooting

### Issue: "File not found in source directory"

**Cause:** TSSE8 filename doesn't match actual file on disk, or source path is incorrect.

**Solution:**
1. Verify source path is correct:
   ```bash
   ls -lh /var/mounted/tsse8/files/torrents/ | head -20
   ls -lh /var/mounted/tsse8/images/torrents/ | head -20
   ```

2. Check TSSE8 database for stored filenames:
   ```bash
   mysql -u tsse8_user -p tsse8_db -e \
     "SELECT id, name, filename FROM torrents LIMIT 20;"
   ```

3. If filenames don't match filesystem, manually verify a few files:
   ```bash
   # Check if files exist
   ls /var/mounted/tsse8/files/torrents/movie.torrent
   ls /var/mounted/tsse8/files/torrents/movie-2.torrent
   ```

4. Re-run migration after correcting paths

### Issue: "Image files not found"

**Cause:** Image files are not stored in the source directory, or naming pattern doesn't match.

**Resolution:**
- Check that cover/banner images exist in source:
  ```bash
  ls -lh /var/mounted/tsse8/images/torrents/torrent-cover_*.jpg | wc -l
  ls -lh /var/mounted/tsse8/images/torrents/torrent-banner_*.jpg | wc -l
  ```

- Verify TSSE8 torrent IDs match image filenames:
  ```bash
  mysql -u tsse8_user -p tsse8_db -e \
    "SELECT id FROM torrents WHERE id IN (42, 100, 200) LIMIT 5;"
  ls /var/mounted/tsse8/images/torrents/ | grep -E "(cover|banner)_(42|100|200)"
  ```

- If images use different naming patterns, you may need to manually copy them or rename them to match the expected pattern

### Issue: "Permission denied" or "No space left"

**Solution:**
```bash
# Check destination space
df -h /var/www/vhosts/betaups.site/httpdocs/storage/

# Check permissions
ls -ld /var/www/vhosts/betaups.site/httpdocs/storage/app/files/torrents/files/
ls -ld /var/www/vhosts/betaups.site/httpdocs/storage/app/images/torrents/

# Fix if needed
chown -R nobody:nobody /var/www/vhosts/betaups.site/httpdocs/storage
chmod -R 755 /var/www/vhosts/betaups.site/httpdocs/storage/app/files
chmod -R 755 /var/www/vhosts/betaups.site/httpdocs/storage/app/images
```

### Issue: Disk Space Insufficient

**Estimate space needed:**
```bash
# In TSSE8
du -sh /var/mounted/tsse8/files/torrents/
du -sh /var/mounted/tsse8/images/torrents/
# Sum both for total needed

# Or more precisely:
du -c /var/mounted/tsse8/files/torrents/*.torrent | tail -1
du -c /var/mounted/tsse8/images/torrents/*.jpg | tail -1
```

**Free up space:**

Option 1: Increase partition
```bash
# Contact hosting provider or resize VM
```

Option 2: Migrate in batches (smaller disk footprint)
```bash
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --tables=torrents \
  --source-torrent-path=/mnt/tsse8 \
  --source-images-path=/mnt/tsse8/images \
  --copy-files \
  --copy-images \
  --page-size=100          # Smaller batches (default 500)
  --offset=0
```

Option 3: Copy images separately after files
```bash
# First pass: migrate with --copy-files only
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --tables=torrents \
  --source-torrent-path=/mnt/tsse8 \
  --copy-files \
  --page-size=500 \
  --offset=0

# Wait for completion, verify disk space, then copy images
# (requires separate manual image copy or future enhancement)
```

### Issue: After Migration, Still Can't Edit Torrents

**Verify files exist:**
```bash
# Count migrated torrents in database
mysql -u unit3d_user -p unit3d_db -e \
  "SELECT COUNT(*) FROM torrents;"

# Count actual files
ls storage/app/files/torrents/files/ | wc -l

# If counts don't match, use recovery command
/opt/plesk/php/8.4/bin/php artisan migrate:verify-torrent-files --fix
```

---

## Post-Migration Verification

### Quick Checks

```bash
# 1. Count files match database
DB_COUNT=$(mysql -u unit3d_user -p unit3d_db -e "SELECT COUNT(*) FROM torrents;" | tail -1)
FILE_COUNT=$(ls storage/app/files/torrents/files/ | wc -l)
echo "Database torrents: $DB_COUNT"
echo "Files on disk: $FILE_COUNT"

# 2. Sample file check (verify files are valid .torrent)
file storage/app/files/torrents/files/$(ls storage/app/files/torrents/files/ | head -1)
# Output should show: "data" (binary bencoded torrent)

# 3. Test edit functionality (as admin)
# - Navigate to a migrated torrent's edit page in admin panel
# - Try to modify title or description
# - Should succeed without errors
```

### Full Validation Command (When Available)

```bash
/opt/plesk/php/8.4/bin/php artisan migrate:verify-torrent-files \
  --verbose \
  --fix-missing            # Auto-generate placeholder files if needed
```

---

## Complete Migration Workflow

### For Clean Migration (Starting Fresh)

```bash
#!/bin/bash
# Step 1: Database migration
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --host=tsse8-db-host \
  --database=tsse8_db \
  --username=tsse8_user \
  --password='password' \
  --tables=users,torrents,peers,snatched,comments \
  --page-size=500

# Step 2: Copy torrent files
rsync -avz \
  root@tsse8-server:/path/to/files/torrents/ \
  /var/www/vhosts/betaups.site/httpdocs/storage/app/files/torrents/files/

# Step 3: Fix permissions
chown -R nobody:nobody /var/www/vhosts/betaups.site/httpdocs/storage
chmod -R 755 /var/www/vhosts/betaups.site/httpdocs/storage

# Step 4: Verify
/opt/plesk/php/8.4/bin/php artisan migrate:verify-torrent-files --verbose
```

### For Incremental Migration (Preserving Existing)

```bash
# Only migrate torrents newer than cutoff date
/opt/plesk/php/8.4/bin/php artisan migrate:tsse8 \
  --tables=torrents \
  --where="added > '2024-01-01'" \
  --copy-files \
  --source-torrent-path=/mnt/tsse8/torrents
```

---

## Key Takeaways

✅ **Database** migration handles metadata automatically  
✅ **Files** (`.torrent`) migration requires manual steps or automated feature  
✅ **Images** (covers/banners) migration included in automated feature  
✅ **Naming conflicts** resolved with auto-numbering during migration  
✅ **Permissions** must be set correctly (web user ownership)  
✅ **Verification** should follow every migration  
⚠️ **Disk space** must be sufficient for both files AND images  
⚠️ **Backups** recommended before large migrations  

---

## Support

For issues or questions about torrent file migration:

1. Check this document's **Troubleshooting** section
2. Verify TSSE8 source directory structure
3. Ensure adequate disk space and permissions
4. Contact support with migration command output and error logs
