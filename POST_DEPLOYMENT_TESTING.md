# Post-Deployment Testing Guide - v1.0.11

## Quick Verification Checklist

### After Installing 1.0.11 Locally

#### 1. Component Installation Check
```bash
# Verify files are in correct locations
ls -la /joomla4/components/com_ra_develop/api/src/Controller/BuildsController.php
ls -la /joomla4/components/com_ra_develop/api/src/Model/BuildsModel.php
ls -la /joomla4/components/com_ra_develop/site/src/Model/BuildsModel.php
```
✅ All files should exist with correct namespace declarations

#### 2. Manifest Verification
```bash
# Check manifest has dual namespace declarations
cat /joomla4/components/com_ra_develop/administrator/ra_develop.xml | grep -A2 "<namespace"
```
✅ Should show:
```xml
<namespace path="src">Ramblers\Component\Ra_develop</namespace>
<namespace path="api/src">Ramblers\Component\Ra_develop\Api</namespace>
```

#### 3. API Routing Test (Local Site)
```bash
# Test that API endpoint is registered
curl -s -X GET \
  "http://joomla4.local/api/index.php/v1/ra_develop/builds" \
  -H "X-Joomla-Token: YOUR_TOKEN_HERE" \
  -H "Accept: application/vnd.api+json" | jq .
```
✅ Should return JSON:API response with build records (not 404)

#### 4. Local Mode Test
1. Go to Frontend → Builds menu item
2. Verify Menu Settings:
   - `data_source` = "local"
   - `remote_site_id` = (empty/not set)
3. ✅ Should display records from #__ra_builds
4. Check log file:
   ```bash
   tail -f /joomla4/administrator/logs/builds_model_debug.log
   ```
   ✅ Should show lines with "[LOCAL]" prefix

#### 5. Remote Mode Test (If Remote Site Available)
1. Frontend → Builds menu item
2. Verify Menu Settings:
   - `data_source` = "remote"
   - `remote_site_id` = (select remote site)
3. ✅ Should display records from remote API
4. Check logs on LOCAL site:
   ```bash
   tail -f /joomla4/administrator/logs/builds_model_debug.log
   ```
   ✅ Should show lines with "[REMOTE]" prefix
5. Check logs on REMOTE site:
   ```bash
   tail -f /remote/administrator/logs/api_builds_debug.log
   ```
   ✅ Should show lines with "[API SERVER]" prefix showing when controller was invoked

#### 6. Pagination & Sorting Test
- Local mode: Change page, try sorting by column, search ✅
- Remote mode: Change page, try sorting by column, search ✅

### Log File Locations

**Client (Local Joomla):**
```
/joomla4/administrator/logs/builds_model_debug.log
```
Look for:
- `[LOCAL]` entries when local mode
- `[REMOTE]` entries when remote mode
- Record counts
- API URLs being called

**Server (Remote Joomla):**
```
/remote/administrator/logs/api_builds_debug.log
```
Look for:
- `[API SERVER] BuildsController::display() invoked`
- HTTP method (GET)
- Pagination parameters (start, limit)
- Request URI and query string

### Expected Log Output Examples

**Local Mode Log (builds_model_debug.log):**
```
2026-01-31 15:32:45 [LOCAL] BuildsModel::getItems() - local database query
2026-01-31 15:32:45 [LOCAL] Query returned 12 records
```

**Remote Mode Log (builds_model_debug.log):**
```
2026-01-31 15:33:12 [REMOTE] BuildsModel::getRemoteItems() invoked
2026-01-31 15:33:12 [REMOTE] Remote site URL: http://remote-site.com
2026-01-31 15:33:12 [REMOTE] Constructed API URL: http://remote-site.com/api/index.php/v1/ra_develop/builds?limit=25&start=0
2026-01-31 15:33:12 [REMOTE] X-Joomla-Token header included
2026-01-31 15:33:12 [REMOTE] HTTP response received: 200 OK
2026-01-31 15:33:12 [REMOTE] Parsed JSON response - 15 records
```

**Remote API Log (api_builds_debug.log):**
```
2026-01-31 15:33:12 [API SERVER] BuildsController::display() invoked
  Method: GET
  Start: 0
  Limit: 25
  Request URI: /api/index.php/v1/ra_develop/builds?limit=25&start=0
  Query String: limit=25&start=0
2026-01-31 15:33:12 [API SERVER] BuildsModel::getItems() invoked - building query
2026-01-31 15:33:12 [API SERVER] Query executed - retrieved 15 records from database
2026-01-31 15:33:12 [API SERVER] Returning 15 items to API client
```

### Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| API returns 404 | Manifest namespace missing | Verify dual namespace declarations in xml |
| API returns 404 | Files not in /api/src/ | Check file locations match manifest paths |
| Remote API not invoked | Old /api/components/ folder present | Delete /api/components/ folder on remote |
| Empty results | #__ra_builds table is empty | Add test data to table |
| Connection refused | Wrong remote URL | Verify remote_site_id URL in #__ra_api_sites |
| 403 Forbidden | Invalid X-Joomla-Token | Verify token matches site's API token |

### Rollback Procedure
If v1.0.11 has issues:
```bash
# 1. Uninstall from Joomla admin
# 2. Restore database
mysql -u root -p joomla4 < backup_before_1.0.11.sql
# 3. Clear Joomla cache
rm -rf /joomla4/cache/*
# 4. Re-install v1.0.10
# Upload and install previous version
```

### Success Criteria ✅
- [  ] API endpoint responds (not 404)
- [  ] Local mode displays builds
- [  ] Remote mode displays builds from API
- [  ] Pagination works in both modes
- [  ] Sorting works in both modes
- [  ] Logging shows [LOCAL] and [REMOTE] prefixes
- [  ] Remote API logs show [API SERVER] invocations
- [  ] No PHP errors in /joomla4/logs/ directory

### Contact/Support
If issues persist after testing:
1. Check /joomla4/error.log for PHP errors
2. Enable Joomla debug mode: configuration.php
   - `$debug = true;`
   - `$log_path = '/joomla4/logs';`
3. Review logs in both locations
4. Verify component version shows 1.0.11 in Extensions → Manage

---
**Testing Date:** [Fill in]
**Tester Name:** [Fill in]
**Status:** [ ] PASS [ ] FAIL
**Notes:** [Any issues found]
