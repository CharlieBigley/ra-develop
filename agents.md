# Agent Environment

## Platform
**MAMP 5.7** (all-in-one development stack)
- Deployment: `/Applications/MAMP`
- Default root password: `root`
- phpMyAdmin included: `/Applications/MAMP/bin/phpMyAdmin`
- CLI: Prefix commands with `/Applications/MAMP/Library/bin/` (e.g., `mysql`, `mysqldump`)

## System
1. **Operating System:** macOS 12.3.1
2. **Xcode:** Command Line Tools only (full Xcode not installed)

## Stack Components
3. **Webserver:** Apache 2.4.46 (managed by MAMP)
4. **PHP Version:** 8.3.28 (managed by MAMP)
5. **Database:** MySQL 5.7.32 (managed by MAMP)
   - Note: MySQL 8.x requires MAMP upgrade or Homebrew; current version is stable and Joomla-compatible
   - Backup location: `/tmp/mamp_mysql57_backup_20260222_194204.sql` (19MB)
6. **Joomla Caching:** Off

## Database Details
- Primary databases: `joomla4`, `CSV_DB`, `lanzarote`, `vietnam`
- Backup strategy: Use `/Applications/MAMP/Library/bin/mysqldump -u root -proot --all-databases`
- Restore: Use `/Applications/MAMP/Library/bin/mysql -u root -proot < backup.sql`

## Known Limitations
- macOS 12.3.1 prevents Homebrew package compilation (Xcode full install required)
- MAMP services are coupled; upgrading one component requires MAMP version update
- To upgrade MySQL beyond 5.7: Download MAMP 7.4+ and migrate databases
