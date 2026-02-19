# Dual-Mode Site Builds View Implementation

## Overview
The site view for Builds has been enhanced to support two modes: **Local** (querying the local database) and **Remote** (querying a remote Joomla site's API). This allows viewing builds from any configured remote site with identical functionality and appearance.

## Changes Made

### 1. Form (buildform.xml)
**File:** [com_ra_develop/site/forms/buildform.xml](com_ra_develop/site/forms/buildform.xml)

Added two new form fields at the top of the fieldset:
- **data_source**: Radio button field allowing selection between "Local Database" (default) and "Remote Site API"
- **remote_site_id**: Conditional SQL dropdown field populated from #__ra_api_sites table, only shown when "Remote" is selected via `showon="data_source:remote"` attribute

### 2. Site BuildsModel (Dual-Mode Support)
**File:** [com_ra_develop/site/src/Model/BuildsModel.php](com_ra_develop/site/src/Model/BuildsModel.php)

**New Properties:**
- `$dataSource`: Tracks current mode ('local' or 'remote')
- `$remoteSiteId`: Stores selected remote site ID
- `$remoteSiteUrl`: Caches the remote site's URL

**Updated populateState():**
- Captures `data_source` parameter from form input
- Retrieves remote site URL from database when remote mode is selected
- Maintains existing pagination and search filter state

**Updated getListQuery():**
- Returns `null` for remote mode (API data doesn't use SQL queries)
- Builds local SQL query for local mode with existing logic (joins with extensions and extension_types tables)

**New getRemoteItems() Method:**
- Constructs API endpoint URL: `{remote_site_url}/api/v1/ra_develop/builds`
- Sends HTTP GET request with query parameters (limit, start, sort, filter)
- Parses JSON response and converts items to objects
- Standardizes field names for compatibility with local mode
- Returns empty array and logs errors gracefully if API request fails

**Updated getItems():**
- Routes to `getRemoteItems()` when in remote mode
- Routes to parent `getItems()` when in local mode
- Ensures consistent interface regardless of data source

### 3. Site HtmlView (Remote Site Data)
**File:** [com_ra_develop/site/src/View/Builds/HtmlView.php](com_ra_develop/site/src/View/Builds/HtmlView.php)

**New Property:**
- `$site`: Holds remote site object with metadata (url, background_color, etc.)

**Updated display() Method:**
- Calls `getSiteData()` to populate `$this->site` with remote site information

**New getSiteData() Method:**
- Queries #__ra_api_sites table for the selected remote site
- Returns site object with all available fields (url, background_color, etc.)
- Only retrieves data when in remote mode; returns null for local mode
- Handles database errors gracefully

### 4. Site Template (default.php)
**File:** [com_ra_develop/site/tmpl/builds/default.php](com_ra_develop/site/tmpl/builds/default.php)

**Background Styling:**
- Added logic to apply remote site's background_color to the table container
- Generates inline style: `style="background-color: {$this->site->background_color};"`
- Style only applied when in remote mode and site has background_color defined
- Wraps table in conditional styled `<div class="table-responsive">`

**Column Structure:**
- Maintains identical columns in both modes:
  - Date (build_date)
  - Extension (component_name)
  - Type (version field display)
  - Version (version field)
- Sorting and search functionality works identically in both modes

### 5. API BuildsModel (New)
**File:** [com_ra_develop/api/src/Model/BuildsModel.php](com_ra_develop/api/src/Model/BuildsModel.php)

Created dedicated API model for the Builds table:
- Provides consistent data access for REST API consumption
- Implements proper pagination, filtering, sorting
- Selects relevant fields including extension type joins
- Ensures data returned from API matches local queries
- Configured field arrays compatible with JsonapiView

## Data Flow

### Local Mode Flow:
```
Form (buildform.xml)
  ↓
BuildsModel.getItems()
  ↓
BuildsModel.getListQuery() [SQL]
  ↓
Local #__ra_builds table + joins
  ↓
Template renders with local data
```

### Remote Mode Flow:
```
Form (buildform.xml) + remote_site_id selection
  ↓
BuildsModel.populateState() [fetches remote URL]
  ↓
BuildsModel.getRemoteItems()
  ↓
HTTP request to {remote_url}/api/v1/ra_develop/builds
  ↓
HttpFactory processes request
  ↓
JSON response parsed and standardized
  ↓
HtmlView.getSiteData() [fetches site styling]
  ↓
Template renders with remote data + background color
```

## API Integration

The remote builds query uses the existing API endpoint:
- **Endpoint:** `/api/v1/ra_develop/builds`
- **Method:** GET
- **Parameters:**
  - `limit`: Pagination limit (default: 25)
  - `start`: Pagination start (default: 0)
  - `sort`: Sort field and direction (e.g., "build_date:desc")
  - `filter`: Search filter string
- **Expected Response:** JSON array or JSON:API formatted array of build objects

## Database Requirements

The implementation requires the #__ra_api_sites table with at least these fields:
- `id` (primary key)
- `url` (site URL)
- `background_color` (optional - hex color code for styling)

## Features

✅ **Local/Remote Toggle:** Simple radio button selection between data sources
✅ **Conditional UI:** Remote site dropdown only shows when Remote mode selected
✅ **Identical Columns:** Both modes display same columns with same sorting/search
✅ **Visual Distinction:** Remote results can have background color styling from site config
✅ **Error Handling:** Graceful fallback to empty results if API request fails
✅ **Pagination:** Supports pagination for both local and remote data
✅ **Sorting:** Maintains ordering across both modes
✅ **Search:** Search filters work identically in local and remote modes
✅ **State Persistence:** User's selected mode persists in session

## Usage

1. Navigate to site Builds view
2. Select data source: "Local Database" (default) or "Remote Site API"
3. If Remote selected, choose the remote site from the dropdown
4. Builds from selected source display with identical interface
5. Use search, sorting, and pagination as normal - works with both sources
6. Remote site's background color (if configured) automatically applies to table

## Testing Checklist

- [ ] Local mode displays local builds
- [ ] Remote mode dropdown populated correctly from #__ra_api_sites
- [ ] Remote mode successfully queries API endpoint
- [ ] Columns identical in both modes
- [ ] Sorting works in both modes
- [ ] Search filtering works in both modes
- [ ] Pagination works in both modes
- [ ] Background color applies correctly for remote sites
- [ ] Error messages display gracefully if API unavailable
- [ ] Mode selection persists in user state
