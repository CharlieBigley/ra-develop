# Joomla API Plugin Generation Guide

## Overview
This document describes the process for generating Joomla API access plugins that enable RESTful API access to database tables.

## Components of API Access

Each API setup requires two parts:

### 1. Plugin (plg_ra_xxx)
- Located in: `{parent_project}/plg_ra_{tablename}`
- Registers API routes in the Joomla router
- Namespace: `Ramblers\Plugin\WebServices\Ra_{tablename}`
- Version: 1.0.0

### 2. Component API Code
- Located in: `{parent_project}/com_ra_{project}/src/Api`
- Contains Controllers and Views for handling API requests
- Namespace: `Ramblers\Component\Ra_{project}\Api`

## Generation Template Parameters

To generate a new API plugin, you need:

1. **Parent Project Folder** (e.g., `ra-develop`)
   - The root folder containing the component
   
2. **Table Name** (e.g., `Builds`)
   - The primary table being exposed via API
   - Used for plugin naming: `plg_ra_{tablename_lowercase}`
   
3. **SQL Query** (e.g., `SELECT DISTINCT a.*, t.name AS extension_type FROM ...`)
   - Query defining all fields exposed in READ mode
   - Base table fields are exposed in ADD/UPDATE modes
   - Joined/calculated fields are read-only

## Example Configuration

```
Project: ra-develop
Table: Builds
SQL: SELECT DISTINCT a.*, t.name AS extension_type 
     FROM `dev_ra_builds` AS a 
     LEFT JOIN dev_ra_extensions AS e ON e.name=a.component_name 
     LEFT JOIN dev_ra_extension_types AS t ON t.id=e.extension_type_id
```

This results in:
- Plugin: `plg_ra_develop`
- Component Code: `com_ra_develop/src/Api`

## Plugin File Structure

```
plg_ra_develop/
├── plg_ra_develop.xml          # Manifest
├── services/
│   └── provider.php             # Service provider
├── src/
│   └── Extension/
│       └── Ra_develop.php       # Main plugin class
└── language/
    └── en-GB/
        ├── plg_webservices_ra_develop.ini
        └── plg_webservices_ra_develop.sys.ini
```

## Component API File Structure

```
com_ra_develop/src/Api/
├── Controller/
│   └── BuildsController.php     # API controller for the table
└── View/
    └── Builds/
        └── JsonapiView.php      # JSON API view with field definitions
```

## Field Definitions

The `JsonapiView.php` file contains two field arrays:

### fieldsToRenderItem
- Fields exposed when fetching a single record
- Can include fields from joined tables
- Example:
  ```php
  protected $fieldsToRenderItem = [
      'id',
      'component_name',
      'version',
      'build_date',
      'extension_type',  // From joined table
  ];
  ```

### fieldsToRenderList
- Fields exposed when fetching multiple records
- Can include calculated fields
- Example:
  ```php
  protected $fieldsToRenderList = [
      'id',
      'component_name',
      'version',
      'build_date',
      'extension_type',
  ];
  ```

## SQL Query to Field Mapping

### Read Fields (from SQL query)
All fields returned by the SQL query are available in both fieldsToRenderItem and fieldsToRenderList.

### Write Fields (from base table)
Only fields from the primary table (the base table in FROM clause) are available for CREATE/UPDATE operations.

### Example Analysis

SQL: `SELECT a.*, t.name AS extension_type FROM #__ra_builds AS a LEFT JOIN #__ra_extension_types AS t`

- **Base table fields** (readable/writable):
  - id, component_name, version, build_date, notes, replace, build_date, etc.
  
- **Additional readable fields** (read-only):
  - extension_type (calculated from join)

## Step-by-Step Generation Process

### Step 1: Analyze the SQL Query
Identify:
- Base table name (from FROM clause)
- Base table fields
- Joined table fields and aliases
- Calculated fields

### Step 2: Create Plugin Structure
Create the plugin directory with:
- Manifest file (plg_ra_develop.xml)
- Service provider
- Extension class

### Step 3: Create Component API Code
Create:
- API Controller (BuildsController.php)
- JSON API View (Builds/JsonapiView.php)

### Step 4: Register API Routes
In the plugin's Extension class, use:
```php
$router->createCRUDRoutes('v1/ra_develop/builds', 'builds', ['component' => 'com_ra_develop']);
```

### Step 5: Test the API
Verify endpoints:
- `GET /api/index.php/v1/ra_develop/builds` - List
- `GET /api/index.php/v1/ra_develop/builds/{id}` - Get single
- `POST /api/index.php/v1/ra_develop/builds` - Create
- `PATCH /api/index.php/v1/ra_develop/builds/{id}` - Update
- `DELETE /api/index.php/v1/ra_develop/builds/{id}` - Delete

## Template Files

### Plugin Manifest (plg_ra_develop.xml)
```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="webservices" method="upgrade">
    <name>PLG_WEBSERVICES_PLG_RA_DEVELOP</name>
    <author>Charlie Bigley</author>
    <creationDate>16 Feb 2026</creationDate>
    <copyright>2026 Charlie Bigley</copyright>
    <license>GNU General Public License version 2 or later; see LICENSE.txt</license>
    <authorEmail>charlie@bigley.me.uk</authorEmail>
    <version>1.0.0</version>
    <description>PLG_WEBSERVICES_PLG_RA_DEVELOP_XML_DESCRIPTION</description>
    <namespace path="src">Ramblers\Plugin\WebServices\Ra_develop</namespace>
    <files>
        <folder plugin="ra_develop">services</folder>
        <folder>src</folder>
    </files>
    <languages>
        <language tag="en-GB">language/en-GB/plg_webservices_ra_develop.ini</language>
        <language tag="en-GB">language/en-GB/plg_webservices_ra_develop.sys.ini</language>
    </languages>
</extension>
```

### Plugin Extension Class (src/Extension/Ra_develop.php)
```php
<?php
namespace Ramblers\Plugin\WebServices\Ra_develop\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

class Ra_develop extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();
        $router->createCRUDRoutes('v1/ra_develop/builds', 'builds', ['component' => 'com_ra_develop']);
    }
}
```

### Plugin Service Provider (services/provider.php)
```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Ramblers\Plugin\WebServices\Ra_develop\Extension\Ra_develop;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new Ra_develop(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('webservices', 'ra_develop')
                );
                $plugin->setApplication(Factory::getApplication());
                return $plugin;
            }
        );
    }
};
```

### API Controller (src/Api/Controller/BuildsController.php)
```php
<?php
namespace Ramblers\Component\Ra_develop\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

class BuildsController extends ApiController
{
    protected $contentType = 'builds';
    protected $default_view = 'builds';
}
```

### JSON API View (src/Api/View/Builds/JsonapiView.php)
```php
<?php
namespace Ramblers\Component\Ra_develop\Api\View\Builds;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

class JsonapiView extends BaseApiView
{
    protected $fieldsToRenderItem = [
        'id',
        'component_name',
        'version',
        'build_date',
        'notes',
        'replace',
        'extension_type',
    ];

    protected $fieldsToRenderList = [
        'id',
        'component_name',
        'version',
        'build_date',
        'extension_type',
    ];
}
```

## Configuration for ra-develop Training Example

**Input Parameters:**
- Parent Project: `ra-develop`
- Table: `Builds`
- SQL: `SELECT DISTINCT a.*, t.name AS extension_type FROM #__ra_builds AS a LEFT JOIN #__ra_extensions AS e ON e.name=a.component_name LEFT JOIN #__ra_extension_types AS t ON t.id=e.extension_type_id`

**Derived Values:**
- Plugin Name: `plg_ra_develop`
- Component Name: `com_ra_develop`
- Base Table: `#__ra_builds`
- Read-only Fields: `extension_type`
- Writable Fields: All fields from `#__ra_builds` table

**API Route:** `/api/index.php/v1/ra_develop/builds`

## Regeneration Instructions for Future Sessions

To regenerate this API plugin in a future session:

1. Provide the parent project folder: `/Users/charlie/git/ra-develop`
2. Specify the table: `Builds`
3. Supply the SQL: The query above
4. The system will recreate:
   - `com_ra_develop/plg_ra_develop/` - The plugin
   - `com_ra_develop/src/Api/` - The component API code

All files will be generated using the templates above, with values substituted appropriately.
