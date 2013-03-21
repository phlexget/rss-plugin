<?php

namespace Phlexget\RssPlugin;

use Phlexget\Event\Task;
use Phlexget\Plugin\AbstractPlugin;

use Buzz\Browser as Buzz;
use Buzz\Client\Curl;

class RssPlugin extends AbstractPlugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'phlexget.input' => array('onPhlexgetInput', 0),
        );
    }

    public function onPhlexgetInput(Task $task)
    {
        $config = $task->getConfig();
        if (!isset($config['rss'])) {
            return;
        }

        $task->getOutput()->writeln('<comment>Rss Plugin</comment>:');

        $buzz = $this->get('buzz');
        $xml = array();
        foreach ($config['rss'] as $rss) {
            $cache = $this->get('cache');

            $key = 'input.rss.' . md5($rss);
            $data = $cache->fetch($key);
            if (!$data = $cache->fetch($key)) {
                $task->getOutput()->writeln(sprintf(' - Loading rss <info>%s</info> from internet.', $rss));
                $response = $buzz->get($rss);
                $data = $response->getContent();
                $cache->save($key, $data, 24 * 3600);
            } else {
                $task->getOutput()->writeln(sprintf(' - Loading rss <info>%s</info> from cache.', $rss));
            }

            $xml[] = $data;
        }

        var_dump($xml);
    }
}