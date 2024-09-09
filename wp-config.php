<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Ecommerce' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

//  Added by MR nitesh Raya
define('FS_METHOD','direct');
define("FTP_HOST", "localhost");
define("FTP_USER", "admin");
define("FTP_PASS", "admin");
define('FS_CHMOD_DIR', 0755);
define('FS_CHMOD_FILE', 0644);

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!=XcbKp&^0]-K$ !;PhH*6|s(n10jniU@[a`tz0gTOU,l|wUv1zDhuQHlDJS&0<]' );
define( 'SECURE_AUTH_KEY',  ',LzIK`[)MCSx|ZS^jmR9Lp}bCDpxD^lu&|9Kp,;&3;@qK<UqZ-oamH#D!)kKYjH*' );
define( 'LOGGED_IN_KEY',    'fd}o*`)3&J/V/#Uj2#/P[hb!1h~AzS*m?B2L3O8r$vv2gU+a9z}ILSe_7+JR?=WS' );
define( 'NONCE_KEY',        'X(AT^O CF}dTFw)<$(:i0?cV1]=m0QnF/2oj#nzor){K=6IL[aJ;Ej)M1|J;OLq(' );
define( 'AUTH_SALT',        'mqzzL7YY-f{2irW@)+7p,V/`*=+ ^vYrzp_NE-!I?+% i0~ 0f?ve[]>RYT:~tbs' );
define( 'SECURE_AUTH_SALT', 'utN;=HFUJ2<5MT^l!A50IG4h7p4?iB4v9}uG9Ta+NMmi4d~?WaYojN0jYcfqkOvy' );
define( 'LOGGED_IN_SALT',   '}7c%[7ZwSe7J^JrSbNip8Ty]ZE`qyrzIm5XJ_r.xGKvy]Av1;J&Kn]lZNrPrrB[k' );
define( 'NONCE_SALT',       'Rs#S<@XC[UC7!0>3{NmA4x|Kc>!odV3T7@r1<Y0S} PeBh@tOHK&oz6IP8 (3_{,' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
