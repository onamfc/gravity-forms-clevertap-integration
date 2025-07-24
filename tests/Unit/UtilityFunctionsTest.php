<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;

class UtilityFunctionsTest extends TestCase
{
    public function testEmailValidation()
    {
        // Test valid emails
        Functions\when('is_email')->alias(function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });

        $this->assertTrue(is_email('test@example.com'));
        $this->assertTrue(is_email('user.name+tag@domain.co.uk'));
        $this->assertFalse(is_email('invalid-email'));
        $this->assertFalse(is_email(''));
        $this->assertFalse(is_email('@domain.com'));
    }

    public function testSanitization()
    {
        Functions\when('sanitize_text_field')->alias(function($text) {
            return trim(strip_tags($text));
        });

        $this->assertEquals('Clean Text', sanitize_text_field('Clean Text'));
        $this->assertEquals('Text without tags', sanitize_text_field('<script>Text without tags</script>'));
        $this->assertEquals('Trimmed', sanitize_text_field('  Trimmed  '));
        $this->assertEquals('', sanitize_text_field(''));
    }

    public function testEscaping()
    {
        Functions\when('esc_attr')->alias(function($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        });

        Functions\when('esc_html')->alias(function($text) {
            return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        });

        $this->assertEquals('&lt;script&gt;', esc_html('<script>'));
        $this->assertEquals('&quot;quoted&quot;', esc_attr('"quoted"'));
        $this->assertEquals('Safe text', esc_attr('Safe text'));
        $this->assertEquals('Safe text', esc_html('Safe text'));
    }

    public function testNonceVerification()
    {
        Functions\when('wp_verify_nonce')->alias(function($nonce, $action) {
            return $nonce === 'valid_nonce' && $action === 'test_action';
        });

        Functions\when('wp_create_nonce')->alias(function($action) {
            return 'nonce_for_' . $action;
        });

        $this->assertTrue(wp_verify_nonce('valid_nonce', 'test_action'));
        $this->assertFalse(wp_verify_nonce('invalid_nonce', 'test_action'));
        $this->assertEquals('nonce_for_test_action', wp_create_nonce('test_action'));
    }

    public function testUserCapabilityCheck()
    {
        Functions\when('current_user_can')->alias(function($capability) {
            return $capability === 'manage_options';
        });

        $this->assertTrue(current_user_can('manage_options'));
        $this->assertFalse(current_user_can('edit_posts'));
        $this->assertFalse(current_user_can('invalid_capability'));
    }

    public function testScheduledEventHandling()
    {
        Functions\when('wp_schedule_single_event')->alias(function($timestamp, $hook, $args) {
            return $timestamp > time() && !empty($hook) && is_array($args);
        });

        Functions\when('wp_clear_scheduled_hook')->alias(function($hook) {
            return !empty($hook);
        });

        Functions\when('time')->justReturn(1640995200);

        $futureTime = time() + 300; // 5 minutes from now
        $this->assertTrue(wp_schedule_single_event($futureTime, 'test_hook', ['arg1', 'arg2']));
        $this->assertFalse(wp_schedule_single_event(time() - 300, 'test_hook', [])); // Past time
        $this->assertTrue(wp_clear_scheduled_hook('test_hook'));
        $this->assertFalse(wp_clear_scheduled_hook(''));
    }

    public function testGravityFormsFieldRetrieval()
    {
        Functions\when('rgar')->alias(function($array, $key, $default = '') {
            return isset($array[$key]) ? $array[$key] : $default;
        });

        $entry = [
            '1' => 'test@example.com',
            '2' => 'John Doe',
            '3' => ''
        ];

        $this->assertEquals('test@example.com', rgar($entry, '1'));
        $this->assertEquals('John Doe', rgar($entry, '2'));
        $this->assertEquals('', rgar($entry, '3'));
        $this->assertEquals('default', rgar($entry, '4', 'default'));
    }

    public function testFormFieldSelection()
    {
        Functions\when('selected')->alias(function($selected, $current, $echo = true) {
            $result = $selected == $current ? ' selected="selected"' : '';
            return $echo ? $result : $result;
        });

        Functions\when('checked')->alias(function($checked, $current, $echo = true) {
            $result = $checked == $current ? ' checked="checked"' : '';
            return $echo ? $result : $result;
        });

        $this->assertEquals(' selected="selected"', selected('1', '1', false));
        $this->assertEquals('', selected('1', '2', false));
        $this->assertEquals(' checked="checked"', checked(1, 1, false));
        $this->assertEquals('', checked(1, 0, false));
    }
}