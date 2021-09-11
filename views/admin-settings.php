<form action='options.php' method='post'>

    <?php settings_fields('cowpay');
    do_settings_sections('cowpay');
    submit_button(); ?>
</form>