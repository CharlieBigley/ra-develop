# RA Develop - Component Builder

A Joomla component for managing and building installable packages for components and extensions.

## Overview

RA Develop is a Joomla 4+ component that provides an administrative interface for:
- Creating and managing software builds
- Building installable ZIP packages for Joomla components
- Tracking component versions and build dates
- Managing extensions and subsystems
- Publishing and archiving build records

## Features

- **Build Management**: Create and manage builds for different components
- **Version Tracking**: Track versions using semantic versioning (X.Y.Z format)
- **Automatic Build Date**: Automatically set build dates on creation
- **Component Organization**: Organize components by subsystem
- **Build History**: View all builds sorted by date (most recent first)
- **Extension Types**: Categorize extensions by type
- **Sortable Lists**: Sort builds and extensions by any column

## Installation

1. Extract the component package to your Joomla installation
2. Navigate to System → Install → Extensions
3. Upload or select the component package
4. Follow the installation wizard
5. Configure the component parameters with your git repository base path

## Configuration

### Component Parameters

Navigate to System → Manage → Components → RA Develop and configure:

- **Git Base Path**: The root directory where all git repositories are located (e.g., `/Users/charlie/git/`)

## Usage

### Creating a Build

1. Navigate to Components → RA Develop → Builds
2. Click "New" to create a new build
3. Fill in the build details:
   - **Component Name**: Select the component to build
   - **Version**: Enter the version number (e.g., 1.2.3)
   - **Build Date**: The current date is set automatically
   - **Version Note**: Optional notes about this version
4. Click "Save" to create the build and generate the installable package

### Managing Extensions

1. Navigate to Components → RA Develop → Extensions
2. View all registered extensions
3. Click on an extension to view details including:
   - Subsystem
   - Extension type
   - Maximum version (latest build)

### Viewing Build History

The builds list shows all builds sorted by date (newest first), with details:
- Build date
- Component name
- Extension type
- Version number

## Database Structure

### Main Tables

- `#__ra_builds`: Stores build records with version and date information
- `#__ra_extensions`: Stores component/extension definitions
- `#__ra_extension_types`: Categorizes extensions
- `#__ra_sub_systems`: Organizes subsystems (groupings of related components)

## Directory Structure

```
com_ra_develop/
├── administrator/          # Backend admin interface
│   ├── access.xml         # Access control list
│   ├── config.xml         # Component configuration
│   ├── forms/             # Form definitions
│   ├── src/               # Backend source code
│   │   ├── Controller/    # Admin controllers
│   │   ├── Model/         # Data models
│   │   ├── Table/         # Database tables
│   │   └── View/          # View classes
│   ├── sql/               # Database schema
│   └── tmpl/              # Admin templates
├── site/                  # Frontend interface
│   ├── forms/             # Frontend forms
│   ├── src/               # Frontend source code
│   ├── tmpl/              # Frontend templates
│   └── languages/         # Language files
├── media/                 # Assets (CSS, JS, images)
└── webservices/           # API endpoints
```

## API

The component provides REST API endpoints for programmatic access to builds and extensions.

## Support

For issues, questions, or contributions, please visit the GitHub repository:
[https://github.com/CharlieBigley/ra-develop](https://github.com/CharlieBigley/ra-develop)

## License

GNU General Public License version 2 or later

## Author

Charlie Bigley

## Changelog

### Version 1.0.3
- Fixed build date assignment (now automatically set to current date)
- Improved column sorting in build and extension lists
- Enhanced version display formatting in extension list
- Fixed manifest path handling in build process

### Version 1.0.2
- Initial stable release
- Build creation functionality
- Component management
- Build history tracking
