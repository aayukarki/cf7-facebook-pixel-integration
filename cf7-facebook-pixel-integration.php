<?php
/**
 * Plugin Name: Contact Form 7 Facebook Pixel Integration
 * Description: Sends Contact Form 7 submissions to a Facebook Pixel conversion endpoint with form-specific settings.
 * Version: 1.1
 * Author: Aayush Karki
 */

// Define global constants for default token and pixel_id
// Define global constants for default token and pixel_id
define('FB_PIXEL_DEFAULT_TOKEN', ''); // Will be populated from user input
define('FB_PIXEL_DEFAULT_PIXEL_ID', ''); // Will be populated from user input

// Add settings page to allow users to input their own values
// add_action('admin_menu', 'cf7_fb_pixel_add_admin_menu');
add_action('admin_init', 'cf7_fb_pixel_settings_init');

// function cf7_fb_pixel_add_admin_menu() {
//     add_options_page('CF7 Facebook Pixel Settings', 'CF7 FB Pixel', 'manage_options', 'cf7_fb_pixel', 'cf7_fb_pixel_options_page');
// }

function cf7_fb_pixel_settings_init() {
    register_setting('cf7FbPixelPage', 'cf7_fb_pixel_settings');
    
    add_settings_section(
        'cf7_fb_pixel_section',
        'Facebook Pixel Settings',
        'cf7_fb_pixel_section_callback',
        'cf7FbPixelPage'
    );

    add_settings_field(
        'fb_pixel_token',
        'Facebook Pixel Token',
        'cf7_fb_pixel_token_render',
        'cf7FbPixelPage',
        'cf7_fb_pixel_section'
    );

    add_settings_field(
        'fb_pixel_id',
        'Facebook Pixel ID',
        'cf7_fb_pixel_id_render',
        'cf7FbPixelPage',
        'cf7_fb_pixel_section'
    );
}

function cf7_fb_pixel_section_callback() {
    echo 'Enter your Facebook Pixel settings below. These settings will apply to all Contact Form 7 forms:';
}

function cf7_fb_pixel_token_render() {
    $options = get_option('cf7_fb_pixel_settings');
    $token = isset($options['fb_pixel_token']) ? $options['fb_pixel_token'] : '';
    ?>
    <input type='text' name='cf7_fb_pixel_settings[fb_pixel_token]' value='<?php echo esc_attr($token); ?>' class='regular-text'>
    <?php
}

function cf7_fb_pixel_id_render() {
    $options = get_option('cf7_fb_pixel_settings');
    $pixel_id = isset($options['fb_pixel_id']) ? $options['fb_pixel_id'] : '';
    ?>
    <input type='text' name='cf7_fb_pixel_settings[fb_pixel_id]' value='<?php echo esc_attr($pixel_id); ?>' class='regular-text'>
    <?php
}

function cf7_fb_pixel_options_page() {
    ?>
    <div class="wrap">
        <h1>Contact Form 7 Facebook Pixel Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('cf7FbPixelPage');
            do_settings_sections('cf7FbPixelPage');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Update global constants when settings are saved
add_action('update_option_cf7_fb_pixel_settings', 'cf7_fb_pixel_update_constants', 10, 2);

function cf7_fb_pixel_update_constants($old_value, $new_value) {
    if (isset($new_value['fb_pixel_token'])) {
        define('FB_PIXEL_DEFAULT_TOKEN', $new_value['fb_pixel_token']);
    }
    if (isset($new_value['fb_pixel_id'])) {
        define('FB_PIXEL_DEFAULT_PIXEL_ID', $new_value['fb_pixel_id']);
    }
}

// Hook into the Contact Form 7 submission
add_action('wpcf7_mail_sent', 'cf7_send_to_fb_pixel_conversion');

function cf7_send_to_fb_pixel_conversion($cf7) {
    // Log the start of the function
    error_log('CF7 submission received for form ID: ' . $cf7->id());

    // Get form-specific settings
    $form_id = $cf7->id();
    $options = get_option('cf7_fb_pixel_options_' . $form_id, array());

    // Get global settings
    $global_options = get_option('cf7_fb_pixel_settings', array());

    // Set data for Facebook Pixel tracking
    $pixelData = array(
        'token'      => !empty($options['fb_pixel_token']) ? $options['fb_pixel_token'] : (!empty($global_options['fb_pixel_token']) ? $global_options['fb_pixel_token'] : ''),
        'pixel_id'   => !empty($options['fb_pixel_id']) ? $options['fb_pixel_id'] : (!empty($global_options['fb_pixel_id']) ? $global_options['fb_pixel_id'] : ''),
        'event_name' => !empty($options['event_name']) ? $options['event_name'] : 'Lead',
        'fbp'        => isset($_COOKIE['_fbp']) ? $_COOKIE['_fbp'] : '',
        'fbc'        => isset($_COOKIE['_fbc']) ? $_COOKIE['_fbc'] : '',
        'first_name' => '',
        'last_name'  => '',
        'email'      => '',
        'mobile'     => '',
        'city'       => '',
        'postcode'   => ''
    );

    // Log the initial pixelData
    error_log('Initial pixelData: ' . print_r($pixelData, true));

    // Populate pixel data from the form submission
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();
        
        // Handle full name if provided
        if (!empty($options['full_name']) && isset($posted_data[$options['full_name']])) {
            $full_name = $posted_data[$options['full_name']];
            $name_parts = explode(' ', $full_name);
            $pixelData['last_name'] = array_pop($name_parts);
            $pixelData['first_name'] = implode(' ', $name_parts);
        }
        
        // Handle individual first name and last name fields
        if (!empty($options['first_name']) && isset($posted_data[$options['first_name']])) {
            $pixelData['first_name'] = $posted_data[$options['first_name']];
        }
        if (!empty($options['last_name']) && isset($posted_data[$options['last_name']])) {
            $pixelData['last_name'] = $posted_data[$options['last_name']];
        }
        
        // Handle other fields
        foreach (['email', 'mobile', 'city', 'postcode'] as $field) {
            if (!empty($options[$field]) && isset($posted_data[$options[$field]])) {
                $pixelData[$field] = $posted_data[$options[$field]];
            }
        }
    }

    // Log the final pixelData
    error_log('Final pixelData: ' . print_r($pixelData, true));

    // Send data to the conversion endpoint
    $result = send_to_fb_conversion($pixelData);

    // Increment the request count for this form
    $request_count = get_option('cf7_fb_pixel_request_count_' . $form_id, 0);
    update_option('cf7_fb_pixel_request_count_' . $form_id, $request_count + 1);

    // Log the result for debugging
    error_log('FB Pixel Conversion Result for Form ' . $form_id . ': ' . print_r($result, true));
}

function send_to_fb_conversion($data) {
    $url = 'https://innovate.aiims.com.au/fb_conversion';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        error_log("cURL Error: " . $error);
        curl_close($ch);
        return array('success' => false, 'error' => $error);
    } else {
        curl_close($ch);
        return array('success' => true, 'response' => $response);
    }
}

// Admin page to set form-specific input mappings
add_action('admin_menu', 'cf7_fb_pixel_settings_menu');

function cf7_fb_pixel_settings_menu() {
    add_options_page(
        'CF7 Facebook Pixel Settings', 
        'CF7 Pixel Settings', 
        'manage_options', 
        'cf7-fb-pixel-settings', 
        'cf7_fb_pixel_settings_page'
    );
}

function cf7_fb_pixel_settings_page() {
    // Check if Contact Form 7 is active
    if (!class_exists('WPCF7_ContactForm')) {
        echo '<div class="error"><p>Contact Form 7 is not active. Please install and activate Contact Form 7 to use this plugin.</p></div>';
        return;
    }

    // Get list of Contact Form 7 forms
    $forms = WPCF7_ContactForm::find();

    // Handle form submissions
    if (isset($_POST['cf7_fb_pixel_save']) || isset($_POST['cf7_fb_pixel_global_save'])) {
        if (!isset($_POST['cf7_fb_pixel_nonce']) || !wp_verify_nonce($_POST['cf7_fb_pixel_nonce'], 'cf7_fb_pixel_save')) {
            echo '<div class="error"><p>Security check failed. Please try again.</p></div>';
        } else {
            if (isset($_POST['cf7_fb_pixel_global_save'])) {
                // Save global settings
                $global_options = array(
                    'fb_pixel_token' => sanitize_text_field($_POST['fb_pixel_token']),
                    'fb_pixel_id' => sanitize_text_field($_POST['fb_pixel_id']),
                );
                update_option('cf7_fb_pixel_settings', $global_options);
                echo '<div class="updated"><p>Global settings saved.</p></div>';
            } elseif (isset($_POST['cf7_fb_pixel_save']) && isset($_POST['cf7_fb_pixel_form_id'])) {
                // Save form-specific settings
                $form_id = sanitize_text_field($_POST['cf7_fb_pixel_form_id']);
                $options = array(
                    'event_name' => sanitize_text_field($_POST['event_name']),
                    'full_name'  => sanitize_text_field($_POST['full_name']),
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name'  => sanitize_text_field($_POST['last_name']),
                    'email'      => sanitize_text_field($_POST['email']),
                    'mobile'     => sanitize_text_field($_POST['mobile']),
                    'city'       => sanitize_text_field($_POST['city']),
                    'postcode'   => sanitize_text_field($_POST['postcode']),
                );
                $result = update_option('cf7_fb_pixel_options_' . $form_id, $options);
                if ($result) {
                    echo '<div class="updated"><p>Settings saved for Form ID ' . esc_html($form_id) . '</p></div>';
                } else {
                    echo '<div class="error"><p>Failed to save settings for Form ID ' . esc_html($form_id) . '. The data might be unchanged.</p></div>';
                }
                // Debug information
                // echo '<div class="notice notice-info"><p>Debug: Attempted to save settings for Form ID ' . esc_html($form_id) . '. Data: ' . esc_html(json_encode($options)) . '</p></div>';
            }
        }
    }

    // Get all form options
    $all_form_options = array();
    foreach ($forms as $form) {
        $form_id = $form->id();
        $all_form_options[$form_id] = get_option('cf7_fb_pixel_options_' . $form_id, array());
    }

    // Get current global settings
    $global_options = get_option('cf7_fb_pixel_settings', array());

    // Output the settings form
    ?>
    <div class="wrap">
        <h1>Contact Form 7 Facebook Pixel Settings</h1>
        <form method="post" id="cf7_fb_pixel_global_form">
            <?php wp_nonce_field('cf7_fb_pixel_save', 'cf7_fb_pixel_nonce'); ?>
            
            <h2>Global Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="fb_pixel_token">Facebook Pixel Token</label></th>
                    <td><input name="fb_pixel_token" type="text" id="fb_pixel_token" class="regular-text" value="<?php echo esc_attr($global_options['fb_pixel_token'] ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="fb_pixel_id">Facebook Pixel ID</label></th>
                    <td><input name="fb_pixel_id" type="text" id="fb_pixel_id" class="regular-text" value="<?php echo esc_attr($global_options['fb_pixel_id'] ?? ''); ?>"></td>
                </tr>
            </table>
            <p><input type="submit" name="cf7_fb_pixel_global_save" class="button-primary" value="Save Global Settings"></p>
        </form>

        <h2>Form-Specific Settings</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Form Name</th>
                    <th>Form ID</th>
                    <th>Request Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): 
                    $form_id = $form->id();
                    $form_options = get_option('cf7_fb_pixel_options_' . $form_id);
                    $request_count = get_option('cf7_fb_pixel_request_count_' . $form_id, 0);
                ?>
                    <tr>
                        <td><?php echo esc_html($form->title()); ?></td>
                        <td><?php echo esc_html($form_id); ?></td>
                        <td><?php echo esc_html($request_count); ?></td>
                        <td>
                            <button type="button" class="button" onclick="editForm(<?php echo $form_id; ?>)"><?php echo $form_options ? 'Edit' : 'Add'; ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" id="cf7_fb_pixel_form">
            <?php wp_nonce_field('cf7_fb_pixel_save', 'cf7_fb_pixel_nonce'); ?>
            <div id="form_settings" style="display: none;">
                <h3>Form Settings</h3>
                <input type="hidden" name="cf7_fb_pixel_form_id" id="cf7_fb_pixel_form_id">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="event_name">Event Name</label></th>
                        <td><input name="event_name" type="text" id="event_name" class="regular-text" value="Lead"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="full_name">Full Name Input Name</label></th>
                        <td><input name="full_name" type="text" id="full_name" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="first_name">First Name Input Name</label></th>
                        <td><input name="first_name" type="text" id="first_name" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="last_name">Last Name Input Name</label></th>
                        <td><input name="last_name" type="text" id="last_name" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email Input Name</label></th>
                        <td><input name="email" type="text" id="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mobile">Mobile Input Name</label></th>
                        <td><input name="mobile" type="text" id="mobile" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="city">City Input Name</label></th>
                        <td><input name="city" type="text" id="city" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="postcode">Postcode Input Name</label></th>
                        <td><input name="postcode" type="text" id="postcode" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" name="cf7_fb_pixel_save" class="button-primary" value="Save Form Settings"></p>
            </div>
        </form>
    </div>

    <script>
    function editForm(formId) {
        document.getElementById('cf7_fb_pixel_form_id').value = formId;
        document.getElementById('form_settings').style.display = 'block';
        
        var formOptions = <?php echo json_encode($all_form_options); ?>;
        console.log('All form options:', formOptions); // Debug log
        
        var options = formOptions[formId] || {};
        console.log('Options for form ' + formId + ':', options); // Debug log
        
        // Populate form fields with existing data
        document.getElementById('event_name').value = options.event_name || 'Lead';
        document.getElementById('full_name').value = options.full_name || '';
        document.getElementById('first_name').value = options.first_name || '';
        document.getElementById('last_name').value = options.last_name || '';
        document.getElementById('email').value = options.email || '';
        document.getElementById('mobile').value = options.mobile || '';
        document.getElementById('city').value = options.city || '';
        document.getElementById('postcode').value = options.postcode || '';
        
        console.log('Form populated with:', {
            event_name: document.getElementById('event_name').value,
            full_name: document.getElementById('full_name').value,
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            mobile: document.getElementById('mobile').value,
            city: document.getElementById('city').value,
            postcode: document.getElementById('postcode').value
        }); // Debug log
    }
    </script>
    <?php
}
