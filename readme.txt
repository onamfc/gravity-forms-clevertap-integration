=== CleverTap Gravity Forms Integration ===
Contributors: Brandon Estrella
Tags: gravity forms, clevertap, analytics, forms, marketing
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.3.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate Gravity Forms with CleverTap for user tracking and tagging.

== Description ==

This plugin provides seamless integration between Gravity Forms and CleverTap, allowing you to automatically tag users and send events to CleverTap when forms are submitted.

**Features:**
* Easy configuration through WordPress admin
* Form-specific settings for email field mapping and tagging
* Flexible property mapping - send any form field to CleverTap
* Flexible event data mapping - send custom event data with each event
* Custom event names for each form
* Automatic user attribute updates in CleverTap
* Event tracking with customizable tags
* Custom event data with unlimited key-value pairs
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
7. Enter the event name you want to send to CleverTap
8. Add property mappings to send additional form fields to CleverTap
9. Add event data mappings to send custom data with the CleverTap event
10. Save the form

== Frequently Asked Questions ==

= How do I get my CleverTap API credentials? =

Log in to your CleverTap dashboard, go to Settings > Project Settings, and you'll find your Account ID and Passcode.

= Can I use different tags for different forms? =

Yes! Each form can have its own tag configuration in the form settings.

= Can I use different event names for different forms? =

Yes! Each form can have its own custom event name. For example, you might use "Newsletter Signup" for a newsletter form and "Contact Form Submission" for a contact form.

= Can I send multiple form fields to CleverTap? =

Yes! You can create custom property mappings to send any form field to CleverTap as a profile property. For example, you can send phone numbers, company names, or any other form data directly to CleverTap.

= Can I send custom data with CleverTap events? =

Yes! You can create custom event data mappings to send any form field as event data. This allows you to include additional context like lead source, campaign information, referrer data, or any other relevant information with your CleverTap events.

= What happens if the email field changes position? =

No problem! You can easily update the email field mapping, property mappings, and event data mappings in the form settings without touching any code.