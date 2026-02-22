# Dual-Mode Builds Architecture - Complete Reference

## System Overview

The RA Develop component implements a **dual-mode Builds view** that can display builds either from:
1. **Local Database** - Query local #__ra_builds table
2. **Remote API** - HTTP GET to remote Joomla instance's REST API

Both modes present identical UI/columns, unified interface, with transparent switching via menu configuration.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     Frontend: Builds View                        │
│                (site/tmpl/builds/default.php)                   │
│  Shows: id | component_name | version | build_date | type...  │
│  Styling: Background color from remote site (if remote mode)   │
└──────────────────────┬──────────────────────────────────────────┘
                       │ (Unified Data Access)
                       ↓
┌─────────────────────────────────────────────────────────────────┐
│              Site View Controller & Model Layer                  │
│  (site/src/Model/BuildsModel.php)                              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ getItems()                                              │   │
│  │  ├─ Reads menu param: data_source ("local" or "remote")│   │
│  │  ├─ If "local"  → parent::getItems()                   │   │
│  │  └─ If "remote" → getRemoteItems()                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                       ↙        ↖                                │
│           (LOCAL MODE)         (REMOTE MODE)                    │
└──────────────┬──────────────────────────┬──────────────────────┘
               │                          │
          ┌────↓────┐               ┌─────↓──────┐
          │          │               │             │
          ↓          │               │             ↓
    ┌──────────┐     │               │      ┌──────────────┐
    │ Local DB │     │               │      │ HTTP Client  │
    │ Query    │     │               │      │ (HttpFactory)│
    └──────────┘     │               │      └──────┬───────┘
       #__ra_builds  │               │             │
          Query:     │               │      GET /api/index.php/
    SELECT DISTINCT  │               │          v1/ra_develop/builds
    a.id,            │               │      Headers:
    component_name,  │               │      - X-Joomla-Token
    version,         │               │      - Accept: application/
    build_date...    │               │        vnd.api+json
                     │               │      Params:
                     │               │      - limit, start
                     │               │      - filter_order
                     │               │      - filter_search
                     │               │             │
                     │               │             ↓
                     │               │      ┌──────────────────┐
                     │               │      │   Remote Site    │
                     │               │      │   /joomla4       │
                     │               │      │                  │
                     │               │      │ API Routing:     │
                     │               │      │ /api/index.php/  │
                     │               │      │   v1/ra_develop/ │
                     │               │      │   builds         │
                     │               │      │                  │
                     │               │      │ Maps to:         │
                     │               │      │ api/src/         │
                     │               │      │ Controller/      │
                     │               │      │ BuildsController │
                     │               │      └────────┬─────────┘
                     │               │               │
                     │               │       ┌───────↓────────┐
                     │               │       │ BuildsController│
                     │               │       │ ::display()    │
                     │               │       │ [API SERVER    │
                     │               │       │  logs here]    │
                     │               │       └────────┬────────┘
                     │               │               │
                     │               │       ┌───────↓────────┐
                     │               │       │ Api\Model\    │
                     │               │       │ BuildsModel   │
                     │               │       │ ::getItems()  │
                     │               │       │               │
                     │               │       │ Local Query:  │
                     │               │       │ #__ra_builds  │
                     │               │       └────────┬────────┘
                     │               │               │
                     │               │        JSON:API Response
                     │               │        {data: [...]}
                     │               │               │
                     └───────────────┴───────────────↓────────────┘
                                     │
                            ┌────────↓────────┐
                            │  Returns Array  │
                            │   to Template   │
                            └────────┬────────┘
                                     │
                            ┌────────↓────────┐
                            │  Display Table  │
                            │ (Both modes use │
                            │  identical UI)  │
                            └─────────────────┘
```

## Code Organization

### Site Layer (What users see)

**Controller:** `site/src/Controller/BuildsController.php`
- Extends `ListController`
- Receives user actions (pagination, sorting, search)
- Passes to model via getModel()

**Model:** `site/src/Model/BuildsModel.php` (Dual-Mode Logic)
```
- Reads menu config (data_source, remote_site_id)
- Route logic in getItems():
  - If "local" → SQL query (#__ra_builds)
  - If "remote" → HTTP GET to remote API
- Both return same data structure → Template renders same HTML
```

**Template:** `site/tmpl/builds/default.php`
- Receives $this->items from model
- Identical columns regardless of source
- Optional background-color from remote site (#__ra_api_sites table)

### API Layer (What remote requests receive)

**Route Registration:** Handled by webservices plugin
- Endpoint: `/api/index.php/v1/ra_develop/builds`
- Maps HTTP request → `api/src/Controller/BuildsController`

**Controller:** `api/src/Controller/BuildsController.php`
- Extends `ApiController`
- Logs request details to `/logs/api_builds_debug.log`
- Invokes `Api\Model\BuildsModel`

**Model:** `api/src/Model/BuildsModel.php`
- Extends `ListModel`
- Queries local #__ra_builds
- Returns standardized data for JSON:API response
- Logs query results to `/logs/api_builds_debug.log`

## Namespace Mapping (Critical)

The manifest defines TWO namespace paths:

```xml
<!-- Tells Joomla where to find site/admin code -->
<namespace path="src">Ramblers\Component\Ra_develop</namespace>

<!-- Tells Joomla where to find API code -->
<namespace path="api/src">Ramblers\Component\Ra_develop\Api</namespace>
```

This allows:
- `site/src/` files → `namespace Ramblers\Component\Ra_develop\Site\...`
- `administrator/src/` files → `namespace Ramblers\Component\Ra_develop\Admin\...`
- `api/src/` files → `namespace Ramblers\Component\Ra_develop\Api\...`

Joomla autoloader finds each file using this mapping.

## Data Flow: Local Mode

```
1. User visits frontend Builds page (data_source="local")
2. BuildsController routes to BuildsModel
3. BuildsModel::getItems():
   - Gets state: data_source = "local"
   - Calls parent::getItems()
4. Parent (ListModel) executes SQL query:
   SELECT DISTINCT a.id, a.component_name, a.version, 
                   a.build_date, t.name AS extension_type
   FROM #__ra_builds a
   LEFT JOIN #__ra_extensions e ON e.name = a.component_name
   LEFT JOIN #__ra_extension_types t ON t.id = e.extension_type_id
5. Returns array of objects to template
6. Template renders HTML table with records
7. Logs: [LOCAL] Query executed - X records retrieved
```

## Data Flow: Remote Mode

```
1. User visits frontend Builds page (data_source="remote", remote_site_id=1)
2. BuildsController routes to BuildsModel
3. BuildsModel::getItems():
   - Gets state: data_source = "remote"
   - Calls getRemoteItems()
4. getRemoteItems():
   a) Fetches remote site config from #__ra_api_sites:
      - URL: http://remote-site.com
      - Token: eyJ0eXAi...
      - background_color: #f0f0f0
   b) Constructs URL:
      http://remote-site.com/api/index.php/v1/ra_develop/builds
      ?limit=25&start=0&filter_order=a.build_date&filter_order_Dir=DESC
   c) Creates HTTP request with headers:
      - X-Joomla-Token: Bearer eyJ0eXAi...
      - Accept: application/vnd.api+json
   d) HttpFactory::getHttp()->get()
5. Remote server receives request → Route dispatcher → 
   api/src/Controller/BuildsController::display()
6. Remote controller invokes api/src/Model/BuildsModel::getItems()
7. Remote model queries local #__ra_builds, returns JSON:API:
   {
     "data": [
       {"type": "builds", "id": "1", "attributes": {"..."}},
       {"type": "builds", "id": "2", "attributes": {"..."}}
     ]
   }
8. Local client parses JSON, extracts records
9. Returns array of objects to template
10. Template renders HTML table with records
11. Logs (local): [REMOTE] API call successful - 25 records
12. Logs (remote): [API SERVER] Request processed
```

## Key Configuration

### Menu Item Settings (site/tmpl/builds/default.xml)

```xml
<field name="data_source" 
        type="list" 
        default="local" 
        label="Data Source">
    <option value="local">Local Database</option>
    <option value="remote">Remote API</option>
</field>

<field name="remote_site_id" 
        type="sql" 
        label="Remote Site"
        query="SELECT id, name FROM #__ra_api_sites WHERE state=1">
</field>
```

### Remote Site Registry (#__ra_api_sites table)

```sql
CREATE TABLE #__ra_api_sites (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  url VARCHAR(255) NOT NULL,
  token VARCHAR(500) NOT NULL,
  background_color VARCHAR(7) DEFAULT '#ffffff',
  state TINYINT DEFAULT 1
);

-- Example:
INSERT INTO #__ra_api_sites VALUES 
(1, 'Remote Dev Server', 'http://dev.example.com', 
 'eyJ0eXAi...', '#f0f0f0', 1);
```

### HTTP Request Headers

```
GET /api/index.php/v1/ra_develop/builds HTTP/1.1
Host: remote-site.com
X-Joomla-Token: Bearer eyJ0eXAi...
Accept: application/vnd.api+json
Content-Type: application/json
```

## Logging Infrastructure

### Client-Side Log (`/administrator/logs/builds_model_debug.log`)

```
2026-01-31 15:32:45 [LOCAL] BuildsModel::populateState() - reading menu config
2026-01-31 15:32:45 [LOCAL] data_source from menu: local
2026-01-31 15:32:45 [LOCAL] BuildsModel::getItems() - data_source state: local
2026-01-31 15:32:45 [LOCAL] Routing to parent::getItems()
2026-01-31 15:32:45 [LOCAL] Query executed - retrieved 12 records from database
```

OR

```
2026-01-31 15:33:12 [REMOTE] BuildsModel::populateState() - reading menu config
2026-01-31 15:33:12 [REMOTE] data_source from menu: remote
2026-01-31 15:33:12 [REMOTE] remote_site_id from menu: 1
2026-01-31 15:33:12 [REMOTE] BuildsModel::getRemoteItems() invoked
2026-01-31 15:33:12 [REMOTE] Fetching remote site config from #__ra_api_sites
2026-01-31 15:33:12 [REMOTE] Remote URL: http://remote-site.com
2026-01-31 15:33:12 [REMOTE] Constructed API URL: http://remote-site.com/api/index.php/v1/ra_develop/builds?limit=25&start=0
2026-01-31 15:33:12 [REMOTE] Adding X-Joomla-Token header
2026-01-31 15:33:12 [REMOTE] HTTP GET request sent
2026-01-31 15:33:12 [REMOTE] HTTP Response: 200 OK
2026-01-31 15:33:12 [REMOTE] JSON response parsed successfully
2026-01-31 15:33:12 [REMOTE] Records extracted from JSON:API format
2026-01-31 15:33:12 [REMOTE] Returning 15 items to template
```

### Server-Side Log (`/administrator/logs/api_builds_debug.log`)

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

## Error Handling

### Local Mode Issues
- **Empty #__ra_builds table** → Empty results (no error, expected)
- **SQL query error** → Error in logs, empty results
- **User permissions** → Shows published only if user lacks edit permission

### Remote Mode Issues
- **Invalid URL** → Connection failed, error logged
- **Invalid token** → 403 Forbidden from remote
- **Remote API down** → Connection timeout/refused
- **Missing X-Joomla-Token header** → API may reject request
- **Wrong URL format** → 404 from remote

All errors logged with context for debugging.

## Security Considerations

1. **X-Joomla-Token stored in database** 
   - Encrypted in #__ra_api_sites.token
   - Only admin can configure
   - Token stored as "Bearer {token}" format

2. **Menu Settings Permissions**
   - Only users with component permission can change menu settings
   - Menu items require frontend article manager access

3. **API Access**
   - Remote API protected by X-Joomla-Token header
   - Server validates token before processing request
   - Logs all API access

4. **Data Sensitivity**
   - Both local and remote show published builds only (unless admin)
   - User permissions respected in both modes

## Performance Considerations

- **Local mode**: Single query, fast (same as any Joomla list)
- **Remote mode**: 
  - Network latency added
  - Pagination reduces data per request
  - Caching recommended for repeated calls
  - Token lookup from database (small overhead)

## Future Enhancements

Possible future versions:
- Response caching on client-side
- Batch API calls for multiple remote sites
- Fallback to local if remote unavailable
- Real-time sync of remote builds to local cache
- Multi-site federation view (single page from multiple remotes)

---
**Component Version:** 1.0.11
**Last Updated:** 2026-01-31
**Status:** Production Ready
