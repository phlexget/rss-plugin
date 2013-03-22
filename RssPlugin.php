<?php

namespace Phlexget\RssPlugin;

use Phlexget\Event\Task;
use Phlexget\Plugin\AbstractPlugin;

use SimplePie;

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
        $torrents = array();
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

            $simplepie = new SimplePie();
            $simplepie->set_raw_data($data);
            $simplepie->enable_cache(false);
            $simplepie->init();

            foreach ($simplepie->get_items() as $item) {
                $torrents[] = array(
                    'title' => $item->get_title(),
                    'description' => $item->get_description(),
                    'date' => $item->get_date(),
                    'link' => $item->get_enclosure(0)->get_link(),
                    'size' => $item->get_enclosure(0)->get_length(),
                );
            }

            $task['torrents'] = isset($task['torrents']) ?
                array_merge($task['torrents'], $torrents) :
                $torrents;
        }
    }
}