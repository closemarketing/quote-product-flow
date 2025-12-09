# Migration to New License System

## Overview

PBC has been updated to use a new license system that matches FrontBlocks PRO's approach. This document explains the changes and how to use the new system.

## What Changed?

### ✅ License Configuration (Updated)

**Old System:**
```php
'product_id' => 2635,
'api_url'    => 'https://close.technology/',
```

**New System:**
```php
'product_uuid'     => 'PBC-5E973533-1688-43CD-B151-ABC2C639B336',
'rest_api_key'     => 'ck_857ef2cf419641b2741ed4ea4d5a750aa979113a',
'rest_api_secret'  => 'cs_851fd6126de05a967fc8abb949afe74344faee71',
'api_url'          => 'https://close.technology/',
```

### ✅ Always-On Plugin

**Before:** Plugin completely blocked without active license
**After:** Plugin works in "demo mode" without license, shows discreet notice to admins

### ✅ New Helper Functions

```php
pbc_is_license_active()     // Check if license is active
pbc_get_license_status()    // Get status: 'active', 'expired', 'inactive'
pbc_is_license_registered() // Check if license key exists
pbc_get_stored_license_key()// Get the license key
pbc_get_license_data()      // Get all license data
pbc_has_license_expired()   // Check if license expired
```

### ✅ Development Filter

```php
// Bypass license check for development
add_filter( 'pbc_bypass_license_check', '__return_true' );
```

### ✅ Premium Features Hook

```php
// Load premium features only when license active
add_action( 'pbc_load_premium_features', function() {
    // Your premium features here
} );
```

## How to Test

### 1. Test Without License (Demo Mode)

1. Make sure no license is activated
2. Visit a page with `[pbc]` shortcode
3. **Expected Result:**
   - ✅ Configurator displays and works
   - ✅ Admins see purple banner: "Demo Mode"
   - ✅ Regular users see no banner
   - ✅ Admin panel shows warning notice

### 2. Test With Development Bypass

Create file: `wp-content/mu-plugins/pbc-dev-mode.php`

```php
<?php
/**
 * Plugin Name: PBC Development Mode
 * Description: Bypasses PBC license check
 */

add_filter( 'pbc_bypass_license_check', '__return_true' );

// Optional: Show notice in admin
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-info">';
        echo '<p><strong>PBC Dev Mode:</strong> License check bypassed.</p>';
        echo '</div>';
    }
} );
```

**Expected Result:**
- ✅ No license banners anywhere
- ✅ Plugin works as if license is active
- ✅ Optional dev notice in admin

### 3. Test With Active License

1. Go to **PBC > Settings**
2. Enter valid license key
3. Click "Activate License"
4. Visit configurator page

**Expected Result:**
- ✅ No banners or warnings
- ✅ Full functionality
- ✅ Green "Active" status in settings

## Configuration Constants

The plugin now uses these constants (defined in `pbc.php`):

```php
WPPBC_LICENSE_API_URL        = 'https://close.technology/'
WPPBC_LICENSE_API_KEY        = 'ck_857ef2cf419641b2741ed4ea4d5a750aa979113a'
WPPBC_LICENSE_API_SECRET     = 'cs_851fd6126de05a967fc8abb949afe74344faee71'
WPPBC_LICENSE_PRODUCT_UUID   = 'PBC-5E973533-1688-43CD-B151-ABC2C639B336'
```

## Database Options

The plugin stores license data using these option keys:

```
product-budget-configurator_license_apikey      // License key
product-budget-configurator_license_activated   // Status: Activated/Deactivated/Expired
```

## API Compatibility

### Old License Manager API
- Used `product_id`
- Required separate instance activation
- Different settings integration

### New License Manager API (Current)
- Uses `product_uuid`
- Uses REST API credentials
- Simplified activation flow
- Better error handling
- Consistent with FrontBlocks PRO

## Frontend Behavior

### Without Active License

```html
<!-- Only shown to logged-in administrators -->
<div class="pbc-license-notice">
    Demo Mode - Activate your license to remove this notice
</div>

<!-- Configurator works normally -->
<div class="page-configurator">
    <!-- Full functionality available -->
</div>
```

### With Active License

```html
<!-- No license notice -->
<div class="page-configurator">
    <!-- Full functionality available -->
</div>
```

## Admin Behavior

### Without Active License

- ⚠️ Warning notice on PBC pages
- Message: "Please activate your license..."
- Settings page shows license form
- All features still accessible

### With Expired License

- ❌ Error notice on PBC pages
- Message: "Your license has expired..."
- Link to renewal page
- All features still accessible

### With Active License

- ✅ No notices shown
- Green "Active" badge in settings
- All features accessible

## Code Examples

### Check License in Your Code

```php
// Simple check
if ( pbc_is_license_active() ) {
    // Do something
}

// Detailed check
$status = pbc_get_license_status();
switch ( $status ) {
    case 'active':
        // License is active
        break;
    case 'expired':
        // License expired
        break;
    case 'inactive':
        // No license
        break;
}
```

### Load Premium Features

```php
add_action( 'pbc_load_premium_features', function() {
    // This only runs when license is active (or bypassed)
    require_once __DIR__ . '/premium-feature.php';
} );
```

### Environment-Based Bypass

```php
add_filter( 'pbc_bypass_license_check', function( $bypass ) {
    // Bypass on local/staging
    $host = $_SERVER['HTTP_HOST'];
    if ( strpos( $host, 'localhost' ) !== false ||
         strpos( $host, 'staging' ) !== false ||
         strpos( $host, '.local' ) !== false ) {
        return true;
    }
    return $bypass;
} );
```

## Troubleshooting

### Issue: Configurator Not Showing

**Cause:** Plugin files not loaded
**Solution:** Check that bypass filter is working or license is active

### Issue: Demo Banner Not Showing

**Cause:** User not logged in as admin or license is active
**Solution:** This is expected behavior - banner only shows for admins

### Issue: License Activation Fails

**Cause:** Invalid key or API connection issue
**Solution:**
1. Verify license key is correct
2. Check API credentials in `pbc.php`
3. Verify product UUID matches
4. Check server can reach `https://close.technology/`

### Issue: "Bypass Not Working"

**Cause:** Filter added too late or not at all
**Solution:**
1. Verify filter is in mu-plugin or theme
2. Check filter priority (should be early)
3. Verify function exists: `function_exists('pbc_is_license_active')`

## Migration Checklist

- [x] Update license configuration with UUID
- [x] Add REST API credentials
- [x] Remove old product_id system
- [x] Implement graceful degradation
- [x] Add helper functions
- [x] Add bypass filter
- [x] Add premium features hook
- [x] Update admin notices
- [x] Add frontend demo banner
- [x] Update documentation

## Comparison Table

| Feature | Old System | New System |
|---------|-----------|------------|
| Plugin loads | ❌ Only with license | ✅ Always |
| Configurator works | ❌ Only with license | ✅ Always |
| License identifier | product_id (2635) | product_uuid (PBC-5E973533...) |
| API authentication | Basic | REST API credentials |
| Dev bypass | ❌ No | ✅ Yes (filter) |
| Admin notices | ❌ Blocking error | ⚠️ Warning |
| Frontend notice | ❌ None | ✅ Demo banner (admin only) |
| Expired handling | ❌ Blocks | ⚠️ Works with warning |
| Helper functions | ❌ Limited | ✅ Complete set |

## Support

For issues with the new license system:

- **Documentation**: `/docs/LICENSE-SYSTEM.md`
- **Development**: `/docs/DEVELOPMENT-SETUP.md`
- **Email**: support@close.technology
- **Website**: https://close.technology/

## Version History

- **v2.0.0**: Initial implementation of new license system
- **v2.0.1**: UI improvements
- **v2.0.2**: License system updated to match FrontBlocks PRO pattern
