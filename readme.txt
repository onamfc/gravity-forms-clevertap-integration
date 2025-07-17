=== CleverTap Gravity Forms Integration ===
Contributors: Brandon Estrella
Tags: gravity forms, clevertap, analytics, forms, marketing
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate Gravity Forms with CleverTap for user tracking and tagging.

== Description ==

This plugin provides seamless integration between Gravity Forms and CleverTap, allowing you to automatically tag users and send events to CleverTap when forms are submitted.

**Features:**
* Easy configuration through WordPress admin
* Form-specific settings for email field mapping and tagging
* Automatic user attribute updates in CleverTap
* Event tracking with customizable tags
* Built-in logging for debugging
* API connection testing
* Delayed event sending (matches your current 4-minute delay)

**Requirements:**
* WordPress 5.0 or higher
* Gravity Forms plugin
* CleverTap account

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clevertap-gravityforms/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your CleverTap API credentials in Forms > CleverTap Integration
4. Configure individual forms in their form settings

== Configuration ==

**Global Settings:**
1. Go to Forms > CleverTap Integration
2. Enter your CleverTap Account ID and Passcode
3. Test the connection to ensure it's working
4. Optionally enable logging for debugging

**Form Settings:**
1. Edit any Gravity Form
2. Go to Form Settings
3. Look for the "CleverTap Integration" section
4. Enable the integration for this form
5. Select the email field from your form
6. Enter the tag you want to apply in CleverTap
7. Save the form

== Frequently Asked Questions ==

= How do I get my CleverTap API credentials? =

Log in to your CleverTap dashboard, go to Settings > Project Settings, and you'll find your Account ID and Passcode.

= Can I use different tags for different forms? =

Yes! Each form can have its own tag configuration in the form settings.

= What happens if the email field changes position? =

No problem! You can easily update the email field mapping in the form settings without touching any code.

== Changelog ==

= 1.0.0 =
* Initial release
* Basic CleverTap integration with Gravity Forms
* Form-specific configuration
* API connection testing
* Logging support