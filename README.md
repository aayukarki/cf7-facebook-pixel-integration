# CF7 Facebook Pixel Integration

This plugin integrates Facebook Pixel tracking with Contact Form 7 submissions in WordPress.

## Description

The CF7 Facebook Pixel Integration plugin allows you to track form submissions from Contact Form 7 using Facebook Pixel. This integration helps you measure the effectiveness of your contact forms and track conversions on your WordPress website.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/cf7-facebook-pixel-integration` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

1. After activation, go to the WordPress admin panel.
2. Navigate to "Settings" > "CF7 Facebook Pixel" in the left sidebar.
3. Enter your Facebook Pixel ID in the provided field.
4. Click "Save Changes" to store your Pixel ID.

## Configuration

1. Edit the Contact Form 7 form you want to track.
2. In the form editor, add the following hidden field to your form:
   ```
   [hidden pixel-event-name "Contact Form Submission"]
   ```
3. You can customize the event name by changing "Contact Form Submission" to any other name you prefer.

## How it Works

- When a user submits a Contact Form 7 form on your website, the plugin will automatically trigger a Facebook Pixel event.
- The event name will be the one you specified in the hidden field (default: "Contact Form Submission").
- You can view the tracked events in your Facebook Ads Manager or Facebook Analytics.

## Support

If you encounter any issues or have questions, please create an issue on the plugin's GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

---

Happy tracking!