# Development Setup

This document explains how to set up Quote Product Flow for local development and testing.

## Bypassing License Check

During development, you don't want to deal with license activation. You can bypass the license check in several ways:

### Method 1: Add Filter to Theme

Add this code to your theme's `functions.php`:

```php
/**
 * Bypass PBC license check in development.
 * Remove this on production!
 */
add_filter( 'pbc_bypass_license_check', '__return_true' );
```

### Method 2: Environment-Based Bypass

For a more sophisticated approach, bypass only in specific environments:

```php
/**
 * Bypass PBC license check based on environment.
 */
add_filter( 'pbc_bypass_license_check', function( $bypass ) {
    // Check if we're on localhost
    if ( in_array( $_SERVER['HTTP_HOST'], array( 'localhost', '127.0.0.1' ) ) ) {
        return true;
    }
    
    // Check for .local or .loc domains
    if ( preg_match( '/\.(local|loc)$/', $_SERVER['HTTP_HOST'] ) ) {
        return true;
    }
    
    // Check for staging subdomain
    if ( strpos( $_SERVER['HTTP_HOST'], 'staging' ) === 0 ) {
        return true;
    }
    
    // Check for custom WP_ENVIRONMENT_TYPE constant
    if ( function_exists( 'wp_get_environment_type' ) ) {
        $env = wp_get_environment_type();
        if ( in_array( $env, array( 'local', 'development', 'staging' ) ) ) {
            return true;
        }
    }
    
    return $bypass;
} );
```

### Method 3: wp-config.php Constant

Define a constant in `wp-config.php`:

```php
// Add this to wp-config.php
define( 'WP_PBC_DEV_MODE', true );
```

Then in your theme's `functions.php`:

```php
add_filter( 'pbc_bypass_license_check', function( $bypass ) {
    if ( defined( 'WP_PBC_DEV_MODE' ) && WP_PBC_DEV_MODE ) {
        return true;
    }
    return $bypass;
} );
```

### Method 4: Must-Use Plugin

Create a must-use plugin at `wp-content/mu-plugins/pbc-dev-mode.php`:

```php
<?php
/**
 * Plugin Name: PBC Development Mode
 * Description: Bypasses PBC license check for development
 * Version: 1.0
 */

// Only load if PBC is active
if ( ! function_exists( 'qpfw_is_license_active' ) ) {
    return;
}

// Bypass license check
add_filter( 'pbc_bypass_license_check', '__return_true' );

// Optional: Add admin notice to indicate dev mode
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>PBC Development Mode:</strong> License check is bypassed. ';
        echo 'Remove wp-content/mu-plugins/pbc-dev-mode.php on production!';
        echo '</p></div>';
    }
} );
```

## Testing License States

You can test different license states without actually changing license activation:

```php
// Force inactive state
add_filter( 'pbc_get_license_status', function() {
    return 'inactive';
} );

// Force expired state
add_filter( 'pbc_get_license_status', function() {
    return 'expired';
} );

// Force active state
add_filter( 'pbc_get_license_status', function() {
    return 'active';
} );
```

## Development Workflow

### Initial Setup

1. Clone/download the plugin to your local WordPress installation
2. Activate the plugin
3. Add bypass filter (use Method 4 - Must-Use Plugin recommended)
4. Start developing!

### Testing License Features

When you need to test license-related features:

1. **Remove or comment out** the bypass filter temporarily
2. Test the license activation flow
3. Test expired license state
4. Test inactive license warnings
5. Re-enable bypass filter when done

### Pre-Production Checklist

Before deploying to production:

- [ ] Remove all bypass filters
- [ ] Delete any mu-plugins created for dev mode
- [ ] Remove WP_PBC_DEV_MODE constant from wp-config.php
- [ ] Test that license notice appears in admin
- [ ] Test that demo mode banner appears on frontend
- [ ] Activate production license

## Common Development Tasks

### Enable Debug Mode

Add to wp-config.php:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

PBC will use these settings to enable additional logging.

### Clear License Cache

During development, you might need to clear the license check cache:

```php
// Clear license cache
delete_transient( 'qpfw_license_last_check' );

// Or via WP-CLI
wp transient delete qpfw_license_last_check
```

### Reset All PBC Data

To completely reset the plugin (useful for testing fresh installs):

```php
// WARNING: This deletes all PBC data!
function pbc_dev_reset_all_data() {
    global $wpdb;
    
    // Delete all phases
    $phases = get_posts( array(
        'post_type' => 'phases',
        'numberposts' => -1,
        'post_status' => 'any'
    ) );
    foreach ( $phases as $phase ) {
        wp_delete_post( $phase->ID, true );
    }
    
    // Delete all variations
    $variations = get_posts( array(
        'post_type' => 'variation',
        'numberposts' => -1,
        'post_status' => 'any'
    ) );
    foreach ( $variations as $variation ) {
        wp_delete_post( $variation->ID, true );
    }
    
    // Delete all options (replace PREFIX with actual prefix from database)
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'pbc_%'" );
    
    // Clear transients
    delete_transient( 'qpfw_license_last_check' );
    
    echo "PBC data reset complete!";
}

// Run this from a temporary admin page or WP-CLI
```

## WP-CLI Commands

Useful WP-CLI commands for development:

```bash
# Check plugin status
wp plugin status quote-product-flow

# List all phases
wp post list --post_type=phases

# List all variations
wp post list --post_type=variation

# Export PBC data
wp export --post_type=phases,variation

# Check option values
wp option get qpfw_license_activated
wp option get qpfw_license_apikey

# Clear transients
wp transient delete qpfw_license_last_check
```

## Debugging Tips

### Check License Status

Add this to any page to see current license status:

```php
<?php
if ( function_exists( 'pbc_get_license_status' ) ) {
    echo 'License Status: ' . pbc_get_license_status() . '<br>';
    echo 'Is Active: ' . ( qpfw_is_license_active() ? 'Yes' : 'No' ) . '<br>';
    echo 'Is Registered: ' . ( qpfw_is_license_registered() ? 'Yes' : 'No' ) . '<br>';
    echo 'License Key: ' . pbc_get_stored_license_key() . '<br>';
}
?>
```

### Frontend Debugging

Add query parameter to enable frontend debugging:

```
yoursite.com/configurator/?pbc_debug=1
```

Then in your code:

```php
if ( isset( $_GET['pbc_debug'] ) && current_user_can( 'manage_options' ) ) {
    // Show debug info
}
```

## Security Notes

**IMPORTANT:** Never deploy bypass filters to production!

- Bypass filters should only exist in development environments
- Use environment checks to ensure they don't accidentally run in production
- Always test license activation before deploying
- Document any custom license handling for your team

## Troubleshooting

### Bypass filter not working

1. Make sure the filter is added before `plugins_loaded` priority 100
2. Check that the function exists: `function_exists( 'qpfw_is_license_active' )`
3. Clear all caches (WordPress, opcache, object cache)
4. Check for typos in filter name

### License cache persisting

```php
// Force clear all PBC transients
delete_transient( 'qpfw_license_last_check' );
wp_cache_flush();
```

### Multiple environments conflicting

Use environment-specific bypass (Method 2) to prevent conflicts between local, staging, and production.

## Best Practices

1. **Use mu-plugins for bypass**: Cleanest approach, easy to remove
2. **Never commit bypass code**: Add to .gitignore or keep separate
3. **Document bypass method**: Tell your team where the bypass is
4. **Test without bypass**: Periodically test the real license flow
5. **Use environment checks**: Never hardcode `return true`

## Resources

- [Main Documentation](LICENSE-SYSTEM.md)
- [WordPress Filter Reference](https://developer.wordpress.org/reference/functions/add_filter/)
- [WP Environment Type](https://developer.wordpress.org/reference/functions/wp_get_environment_type/)
