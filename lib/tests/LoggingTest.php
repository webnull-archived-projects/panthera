<?php
/**
 * Panthera Framework 2 logging test cases
 *
 * @package Panthera\logging\tests
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class LoggingTest extends PantheraFrameworkTestCase
{
    /**
     * Check logging displaying messages
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return void
     */
    public function testOutput()
    {
        $this->setup();
        $this->app->logging->dateFormat = '';
        $this->app->logging->format = '%message';
        $this->app->logging->enabled = true;
        $this->assertEquals('testMessage', $this->app->logging->output('testMessage'));
    }

    /**
     * Test working clear() function
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return void
     */
    public function testClear()
    {
        $this->setup();
        $this->app->logging->enabled = true;
        $this->app->logging->output('testMessage');
        $this->app->logging->clear();
        $this->assertSame(array(), $this->app->logging->messages);
    }

    /**
     * Check if enabling logging works
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     * @return void
     */
    public function testEnabled()
    {
        $this->setup();
        $this->app->logging->enabled = false;
        $this->app->logging->output('testMessage');
        $this->assertSame(array(), $this->app->logging->messages);
    }


}