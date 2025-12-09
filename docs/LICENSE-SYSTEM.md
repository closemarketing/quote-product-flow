# License System

## Overview

Product Budget Configurator uses a flexible license system based on the same approach as FrontBlocks PRO. The plugin functions even without an active license, while encouraging users to activate their license for full support and updates.

## License Configuration

The plugin uses the following license configuration:

- **Product UUID**: `PBC-5E973533-1688-43CD-B151-ABC2C639B336`
- **API URL**: `https://close.technology/`
- **License Manager**: Closemarketing WP License Manager

This configuration matches the pattern used in FrontBlocks PRO for consistency across Close Technology products.

## How It Works

### 1. Plugin Always Loads

Unlike previous versions, the plugin now loads all functionality regardless of license status. This means:
- The configurator shortcode `[pbc]` is always available
- Admin interface is always accessible
- All features work in "demo mode"

### 2. License States

The plugin has three license states:

- **Active**: Valid, activated license. No warnings shown.
- **Expired**: License has expired. Shows renewal notice in admin.
- **Inactive**: No license activated. Shows activation notice in admin and demo mode banner on frontend.

### 3. User Experience

**With Active License:**
- Full access to all features
- Automatic updates
- Priority support
- No banners or notices

**Without Active License (Demo Mode):**
- All features work normally
- Yellow "Demo Mode" banner shown on frontend configurator
- Admin notices prompting license activation
- Manual updates only

## Development & Testing

### Bypass License Check

For development and testing, you can bypass the license check using a filter:

```php
// In your theme's functions.php or a custom plugin
add_filter( 'pbc_bypass_license_check', '__return_true' );
```

This will:
- Treat the license as active
- Remove all license warnings
- Enable all premium features
- Should only be used in development/staging environments

### Environment-Specific Bypass

You can conditionally bypass based on environment:

```php
add_filter( 'pbc_bypass_license_check', function( $bypass ) {
    // Bypass on local development
    if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
        return true;
    }
    
    // Bypass on staging
    if ( strpos( $_SERVER['HTTP_HOST'], 'staging' ) !== false ) {
        return true;
    }
    
    return $bypass;
} );
```

## Helper Functions

The plugin provides several helper functions to check license status:

### `pbc_is_license_active()`

Returns `true` if license is active (or bypassed), `false` otherwise.

```php
if ( pbc_is_license_active() ) {
    // Do something for active licenses
}
```

### `pbc_get_license_status()`

Returns license status as string: `'active'`, `'expired'`, or `'inactive'`.

```php
$status = pbc_get_license_status();

switch ( $status ) {
    case 'active':
        // License is active
        break;
    case 'expired':
        // License has expired
        break;
    case 'inactive':
        // No license or deactivated
        break;
}
```

### `pbc_is_license_registered()`

Returns `true` if a license key is registered (regardless of activation status).

```php
if ( pbc_is_license_registered() ) {
    // User has entered a license key
}
```

### `pbc_get_stored_license_key()`

Returns the stored license key (empty string if none).

```php
$license_key = pbc_get_stored_license_key();
```

## Hooks

### Actions

#### `pbc_load_premium_features`

Fired when premium features should be loaded (license is active or bypassed).

```php
add_action( 'pbc_load_premium_features', function() {
    // Load premium-only functionality
} );
```

### Filters

#### `pbc_bypass_license_check`

Allows bypassing license verification.

```php
add_filter( 'pbc_bypass_license_check', function( $bypass ) {
    return true; // Always bypass
} );
```

**Parameters:**
- `$bypass` (bool) - Whether to bypass license check. Default `false`.

**Returns:**
- (bool) - `true` to bypass, `false` to check normally.

## Comparison with Previous System

### Before (Blocking)

- Plugin completely blocked without active license
- Shortcode didn't register
- Frontend showed nothing
- Users couldn't test or evaluate

### After (Graceful Degradation)

- Plugin works in demo mode
- All features accessible
- Clear indication of demo status
- Encourages license activation
- Better user experience

## Best Practices

1. **Production Sites**: Always use an active license
2. **Staging Sites**: Use bypass filter for testing
3. **Local Development**: Use bypass filter
4. **Client Demos**: Demo mode is acceptable for short-term evaluation

## Security Notes

- License checks are cached for 12 hours to reduce API calls
- Bypass filter respects WordPress capability checks
- License validation happens server-side
- No sensitive data exposed to frontend

## Migration from v1.x

Sites upgrading from v1.x will automatically use the new system:
- Existing valid licenses continue to work
- Sites without licenses now work in demo mode
- No configuration changes needed

## Troubleshooting

### Plugin not loading on frontend

1. Check if license is active: Go to **PBC > Settings**
2. If you want to bypass for testing, add the bypass filter
3. Clear cache if using a caching plugin

### Demo banner showing with active license

1. Verify license in admin: **PBC > Settings**
2. Check license expiration date
3. Try re-activating the license
4. Clear transients cache

### License check too frequent

The plugin caches license checks for 12 hours. If you need to force a recheck:

```php
delete_transient( 'pbc_license_last_check' );
```

## Support

For license-related issues:
- Email: support@close.technology
- Documentation: https://close.technology/docs/pbc/
