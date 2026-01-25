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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',          'S3Oiahk?yh&!<*.q$K(3;iXM,rhK8VRQ^QuWZtQlt:8^*kSa1Nks5Omv3TwQ1C<B' );
define( 'SECURE_AUTH_KEY',   '#L|LhnFr5aC?Q({hK@KOrTXI`%gb{fGrzSaPBa=w -0YP%I25@h@qE[dE*NiF@$=' );
define( 'LOGGED_IN_KEY',     '6ig~SA{wb6asYbed/~4o5bcS{c(U06to]t,,@LgOo9(a}x[/#jwMzOGme1V:d%9#' );
define( 'NONCE_KEY',         'H|}q01{fZHfJ5/A~,E~@iyXZz*hhF|3#q5CvyMA/285nM.>y5_Q$N#| $tf~h{.A' );
define( 'AUTH_SALT',         'w^tXP^nR.!#OAI}iZUg}@p y(U8 `[N0k9]dukmQq96D}&vFb~AqfB:Zz>$jDka<' );
define( 'SECURE_AUTH_SALT',  'b5_p>u3x~OjP2X34|L:lUZ[By?xGz$c( R9[)fM(*W.9w  ;PDQMfa1e5~BqN7,>' );
define( 'LOGGED_IN_SALT',    'wBl<f k8V+*m@cEx3*Giw0UQ=FU`OA72#=BR|g7UMP*b[x4PK#&!?{l|~,`@ sC3' );
define( 'NONCE_SALT',        'd|D ]r NRc((LQL}1v7 +X%oiCgy}f|,-f;I*2w3tX1s|8Cv2]$5#^WKq?BN/5P(' );
define( 'WP_CACHE_KEY_SALT', 'AD<l6gaU~8_@oVj.?<N6q)5 qe9KZ#zf;~f]su.x^a`yC^Vxpymy-O7k5r6lE;n#' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
