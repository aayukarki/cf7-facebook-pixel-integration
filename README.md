# CF7 Facebook Pixel Integration

## Installation

2. Click on the "Code" button and select "Download ZIP".
3. Download the ZIP file to your local machine.
4. Upload the zip file to the `/wp-content/plugins/` directory of your WordPress installation.
5. Activate the plugin through the 'Plugins' screen in WordPress.

This plugin integrates Facebook Pixel tracking with Contact Form 7 submissions in WordPress.

## Description

The CF7 Facebook Pixel Integration plugin allows you to track form submissions from Contact Form 7 using Facebook Pixel. This integration helps you measure the effectiveness of your contact forms and track conversions on your WordPress website.

## Installation

1. Upload the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

1. After activation, go to the WordPress admin panel.
2. Navigate to "Settings" > "CF7 Facebook Pixel" in the left sidebar.
3. Enter your Facebook Pixel ID in the provided field.
4. Click "Save Changes" to store your Pixel ID.

## Configuration

1. Edit the Contact Form 7 form you want to track.
2. In the form editor, add input name details to corresponding fields. For example:
   - Event Name is Lead by default
   - Full Name Input Name - your-name (leave blank if using First Name and Last Name)
   - First Name Input Name - your-first-name (leave blank if using Full Name)
   - Last Name Input Name - your-last-name (leave blank if using Full Name)
   - Email Input Name - your-email
   - Phone Input Name - your-phone
   - City Input Name - your-city
   - Postcode Input Name - your-postcode
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
