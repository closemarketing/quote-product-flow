# License Manager Integration - Product Budget Configurator

## Overview

The Product Budget Configurator now uses the `closemarketing/wp-plugin-license-manager` library for license management. The license fields are automatically rendered within the existing metabox.

## How It Works

### 1. Library Initialization (`pbc.php`)

The license manager is initialized as a global variable:

```php
global $pbc_license_manager;

$pbc_license_manager = new \Closemarketing\WPLicenseManager\License(
    array(
        'api_url'     => WPPBC_URL_API,
        'file'        => WPPBC_PLUGIN,
        'version'     => WPPBC_VERSION,
        'slug'        => 'pbc',
        'name'        => WPPBC_ITEM_NAME,
        'text_domain' => 'pbc',
    )
);
```

### 2. Metabox Integration (`class-pbc-admin-plugin.php`)

The metabox uses the library's settings automatically:

```php
public function license_meta_box_callback() {
    global $pbc_license_manager;
    
    // Render the form using WordPress Settings API
    settings_fields( $pbc_license_manager->get_option_group() );
    do_settings_sections( $pbc_license_manager->get_settings_section() );
}
```

### 3. What's Rendered Automatically

The library automatically creates these fields:
- ✅ **License API Key** - Text input
- ✅ **Product ID** - Text input  
- ✅ **License Status** - Status indicator (Activated/Deactivated)
- ✅ **Deactivate Checkbox** - Option to deactivate license

All fields include:
- Proper labels
- Help text
- Validation
- Sanitization
- Activation/deactivation logic

## Database Options

The library uses these option keys:
- `pbc_license_apikey` - API key
- `pbc_license_product_id` - Product ID
- `pbc_license_activated` - Status (Activated/Deactivated)
- `pbc_license_instance` - Unique instance ID
- `pbc_license_deactivate_checkbox` - Deactivation checkbox state

## Public Methods

### Check License Status

```php
global $pbc_license_manager;

if ( $pbc_license_manager && $pbc_license_manager->is_license_active() ) {
    // License is active - enable premium features
}
```

### Get Option Values

```php
global $pbc_license_manager;

// Get instance ID
$instance = $pbc_license_manager->get_option_value( 'instance' );

// Get API key
$api_key = $pbc_license_manager->get_option_value( 'apikey' );

// Get product ID
$product_id = $pbc_license_manager->get_option_value( 'product_id' );
```

### Get Option Keys

```php
global $pbc_license_manager;

// Get the full option key
$apikey_option = $pbc_license_manager->get_option_key( 'apikey' );
// Returns: 'pbc_license_apikey'
```

## Automatic Features

### 1. Plugin Updates
The library automatically:
- Checks for plugin updates
- Downloads and installs updates
- Shows update notifications

### 2. License Validation
The library handles:
- License activation
- License deactivation
- Status checking
- Error handling

### 3. External Blocking Detection
Automatically detects if WordPress is blocking external requests and shows a warning.

## WooCommerce API Manager

The library connects to your WooCommerce store at `https://close.technology/` using the WooCommerce API Manager.

### Required on Server:
- WooCommerce
- WooCommerce API Manager plugin
- Product configured with API management

## Customization

### Change API URL

Edit in `pbc.php`:

```php
define( 'WPPBC_URL_API', 'https://your-store.com/' );
```

### Custom Text Domain

Already configured to use `'pbc'` text domain for translations.

### Custom Styling

Add your own CSS in `includes/assets/admin.css` to style the license fields.

## Troubleshooting

### License Not Showing
- Check that `vendor/autoload.php` exists
- Run `composer install` if vendor folder is missing
- Check for PHP errors in debug log

### Fields Not Saving
- Verify form action points to `options.php`
- Check user has `manage_options` capability
- Ensure nonce is being generated

### Updates Not Working
- Activate license first
- Verify API URL is correct
- Check WooCommerce API Manager is configured on server

## Migration from Old Code

All old license methods have been removed:
- ❌ `validate_license()`
- ❌ `license_activate()`
- ❌ `license_deactivate()`
- ❌ `get_api_key_status()`
- ❌ `license_key_status()`
- ❌ `update_check()`
- ❌ All other license-related methods

These are now handled automatically by the library.

## Support

- Library Documentation: `/vendor/closemarketing/wp-plugin-license-manager/README.md`
- Issues: Contact david@closemarketing.es
- Store: https://close.technology/

