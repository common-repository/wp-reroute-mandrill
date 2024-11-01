<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/**
 * Plugin Name: WP Reroute Mandrill
 * Plugin URI: http://leah.ellasol.com/wp-reroute-mandrill.zip
 * Description: Method to reroute Mandrill Mail for development testing.
 * Version: 0.2.0
 * Author: Rex Keal
 * Author URI: http://ellasol.com
 * License: GPL2
 */

// Look, this is my first time out - this is still beta stuff :)


add_action('admin_menu', 'rexak_add_admin_menu');
add_action('admin_init', 'rexak_settings_init');


function rexak_add_admin_menu() {

    add_options_page(
        'WP ReRoute Mandrill',
        'WP ReRoute Mandrill',
        'manage_options',
        'rexak_wp_reroute_mandrill',
        'wp_reroute_mandrill_options_page'
    );

}

function rexak_settings_init() {

    register_setting('pluginPage', 'rexak_settings');

    add_settings_section(
        'rexak_pluginPage_section',
        __('Settings for WP Reroute Mandrill',
            'wordpress'
        ),
        'rexak_settings_section_callback',
        'pluginPage'
    );

    add_settings_field(
        'rexak_checkbox_field_0',
        __('Enable the reroute to the address listed below',
            'wordpress'
        ),
        'rexak_checkbox_field_0_render',
        'pluginPage',
        'rexak_pluginPage_section'
    );

    add_settings_field(
        'rexak_text_field_1',
        __('Email address to reroute all Mandrill mail to<br> <span style="color:blue;font-size:12px;">(use comma separated list if you need multiples, no spaces.)</span>',
            'wordpress'
        ),
        'rexak_text_field_1_render',
        'pluginPage',
        'rexak_pluginPage_section'
    );


}


function rexak_checkbox_field_0_render() {

    $options = get_option('rexak_settings');
    ?>
    <input type='checkbox'
           name='rexak_settings[rexak_checkbox_field_0]' <?php checked($options['rexak_checkbox_field_0'], 1); ?>
           value='1'>
    <?php

}


function rexak_text_field_1_render() {

    $options = get_option('rexak_settings');
    ?>
    <input type='text' name='rexak_settings[rexak_text_field_1]' value='<?php echo $options['rexak_text_field_1']; ?>'>
    <?php

}


function rexak_settings_section_callback() {

    echo __('<p style="color:red;">If enabled, but no email address has been entered, mail will route normally.</p>', 'wordpress');

}


function wp_reroute_mandrill_options_page() {

    ?>
    <form action='options.php' method='post'>

        <h2>WP ReRoute Mandrill</h2>

        <?php
        settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        submit_button();
        ?>

    </form>
    <?php

}



//do the work:
add_filter('mandrill_payload', 'intercept_and_reroute_mandrill', 99);
function intercept_and_reroute_mandrill($message) {

    //get the options
    $options = get_option('rexak_settings');

    //check if enabled and has address
    if($options['rexak_checkbox_field_0'] == false || empty($options['rexak_text_field_1']) ){
        return $message;
    } else {
        $dev_receiver = $options['rexak_text_field_1'];
    }

    //split comma list, if any
    if(strpos($options['rexak_text_field_1'], ',') !== false){
        $email_address = explode(',', $dev_receiver);
    }else{
        $email_address = array($dev_receiver);
    }


    //simple verify if it is and email address
    foreach($email_address as $receiver){
        if(filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
            // valid address
            $stop = 1;
        } else {
            // invalid address
            return $message;
        }
    }



    //get original email receivers:
    foreach ($message['to'] as $to) {
        $original_to[] = $to;
    }

    //clear the mandrill receivers list:
    unset($message['to']);

    //set the new receiver(s):
    $i=0;
    foreach($email_address as $receiver){
        $message['to'][$i]['email'] = $receiver;
        $i++;

    }

    //add a testing info to the message body:
    $message['html'] .= '<p>Dev testing!!!</p><p style="color:red;"><em> Intercepted for Development Testing</em></p>';
    $message['html'] .= '<p>Original Recipients:</p>';

    // add a list of the original receivers to the message body:
    foreach ($original_to as $list) {
        $message['html'] .= '<p>' . $list['email'] . '</p>';
    }

    // return new receiver and appended message:
    return $message;
}


