<?php

namespace WebPExpress;

class SelfTest
{

    public static function processAjax()
    {
        if (!check_ajax_referer('webpexpress-ajax-self-test-nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security nonce (it has probably expired - try refreshing)');
            wp_die();
        }

        // Check input
        // --------------
        try {
            // Check "filename"
            $checking = '"testId" argument';
            Validate::postHasKey('testId');

            $testId = sanitize_text_field(stripslashes($_POST['testId']));

        } catch (Exception $e) {
            wp_send_json_error('Validation failed for ' . $checking . ': '. $e->getMessage());
            wp_die();
        }
        $result = '';
        $next = '';
        switch ($testId) {
            case 'first':
                $result = 'first test!';
                $next = 'second';
                break;
            case 'second':
                $result = 'second test!';
                $next = 'done';
                break;
        }

        $response = [
            'result' => $result,
            'next' => $next
        ];
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
