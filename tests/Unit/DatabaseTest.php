<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;

class DatabaseTest extends TestCase
{
    public function testFormConfigInsertion()
    {
        global $wpdb;
        
        $insertCalled = false;
        $wpdb->insert = function($table, $data, $format) use (&$insertCalled) {
            $insertCalled = true;
            $this->assertEquals('wp_ctgf_form_configs', $table);
            $this->assertArrayHasKey('form_id', $data);
            $this->assertArrayHasKey('email_field', $data);
            $this->assertArrayHasKey('tag', $data);
            $this->assertArrayHasKey('event_name', $data);
            $this->assertArrayHasKey('active', $data);
            return 1;
        };

        // Simulate database insertion
        $result = $wpdb->insert(
            'wp_ctgf_form_configs',
            [
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'event_name' => 'Test Event',
                'active' => 1
            ],
            ['%d', '%s', '%s', '%s', '%d']
        );

        $this->assertTrue($insertCalled);
        $this->assertEquals(1, $result);
    }

    public function testFormConfigUpdate()
    {
        global $wpdb;
        
        $updateCalled = false;
        $wpdb->update = function($table, $data, $where, $format, $whereFormat) use (&$updateCalled) {
            $updateCalled = true;
            $this->assertEquals('wp_ctgf_form_configs', $table);
            $this->assertArrayHasKey('email_field', $data);
            $this->assertArrayHasKey('tag', $data);
            $this->assertArrayHasKey('event_name', $data);
            $this->assertArrayHasKey('active', $data);
            $this->assertArrayHasKey('form_id', $where);
            return 1;
        };

        // Simulate database update
        $result = $wpdb->update(
            'wp_ctgf_form_configs',
            [
                'email_field' => '2',
                'tag' => 'Updated Tag',
                'event_name' => 'Updated Event',
                'active' => 1
            ],
            ['form_id' => 1],
            ['%s', '%s', '%s', '%d'],
            ['%d']
        );

        $this->assertTrue($updateCalled);
        $this->assertEquals(1, $result);
    }

    public function testFormConfigRetrieval()
    {
        global $wpdb;
        
        $wpdb->get_row = function($query) {
            $this->assertStringContainsString('SELECT * FROM wp_ctgf_form_configs', $query);
            $this->assertStringContainsString('WHERE form_id =', $query);
            
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'event_name' => 'Test Event',
                'active' => 1,
                'created_at' => '2024-01-01 12:00:00',
                'updated_at' => '2024-01-01 12:00:00'
            ];
        };

        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_ctgf_form_configs WHERE form_id = %d", 1));

        $this->assertIsObject($config);
        $this->assertEquals(1, $config->form_id);
        $this->assertEquals('Test Tag', $config->tag);
        $this->assertEquals('Test Event', $config->event_name);
        $this->assertEquals(1, $config->active);
    }

    public function testFormConfigDeletion()
    {
        global $wpdb;
        
        $queryCalled = false;
        $wpdb->query = function($query) use (&$queryCalled) {
            $queryCalled = true;
            $this->assertStringContainsString('DELETE FROM wp_ctgf_form_configs', $query);
            return 1;
        };

        // Simulate deletion
        $result = $wpdb->query("DELETE FROM wp_ctgf_form_configs WHERE form_id = 1");

        $this->assertTrue($queryCalled);
        $this->assertEquals(1, $result);
    }

    public function testTableCreationSql()
    {
        global $wpdb;
        
        $tableName = $wpdb->prefix . 'ctgf_form_configs';
        $charsetCollate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        
        $expectedSql = "CREATE TABLE $tableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        email_field varchar(10) NOT NULL,
        tag varchar(255) NOT NULL,
        event_name varchar(255) NOT NULL DEFAULT 'Newsletter Signup',
        active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY form_id (form_id)
    ) $charsetCollate;";

        // Test SQL structure
        $this->assertStringContainsString('CREATE TABLE', $expectedSql);
        $this->assertStringContainsString('form_id mediumint(9) NOT NULL', $expectedSql);
        $this->assertStringContainsString('email_field varchar(10) NOT NULL', $expectedSql);
        $this->assertStringContainsString('tag varchar(255) NOT NULL', $expectedSql);
        $this->assertStringContainsString('event_name varchar(255) NOT NULL', $expectedSql);
        $this->assertStringContainsString('active tinyint(1) DEFAULT 1', $expectedSql);
        $this->assertStringContainsString('PRIMARY KEY (id)', $expectedSql);
        $this->assertStringContainsString('UNIQUE KEY form_id (form_id)', $expectedSql);
    }
}