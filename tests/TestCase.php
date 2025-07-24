<?php

namespace CTGF\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;

/**
 * Base test case for all plugin tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Reset global state
        global $wpdb;
        $wpdb->prefix = 'wp_';
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Helper method to create a mock form entry
     */
    protected function createMockEntry($overrides = [])
    {
        return array_merge([
            'id' => '1',
            'form_id' => '1',
            '1' => 'test@example.com',
            '2' => 'John Doe',
            'date_created' => '2024-01-01 12:00:00'
        ], $overrides);
    }

    /**
     * Helper method to create a mock form
     */
    protected function createMockForm($overrides = [])
    {
        return array_merge([
            'id' => '1',
            'title' => 'Test Form',
            'fields' => [
                (object) [
                    'id' => '1',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'inputType' => 'email'
                ],
                (object) [
                    'id' => '2',
                    'type' => 'text',
                    'label' => 'Name',
                    'inputType' => 'text'
                ]
            ]
        ], $overrides);
    }
}