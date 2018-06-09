<?php

namespace App\Modules;

use DiDom\Document;
use DiDom\Element;

class RichText
{
    private static $instance;

    private function __construct()
    {
    }

    /**
     * @param string $html
     * @return array
     */
    public static function load(string $html)
    {
        $document = new Document($html);

        $body = $document->first('body');

        $instance = static::getInstance();;

        $nodes = array_filter(
            array_map(
                function (Element $element) use ($instance) {
                    return $instance->parseElement($element);
                },
                $body->children()
            )
        );

        return $nodes;
    }

    /**
     * @return RichText
     */
    public static function getInstance()
    {
        if (!(static::$instance instanceof RichText)) {
            static::$instance = new RichText;
        }
        return static::$instance;
    }

    /**
     * @param Element $element
     * @return array|bool
     */
    protected function parseElement(Element $element)
    {
        $node = [];
        if ($element->isElementNode()) {
            $node['type'] = 'node';
            $node['name'] = $element->tag;

            $attributes = $element->attributes();
            if ($node['name'] === 'img') {
                $attributes['style'] = trim(($attributes['style'] ?? '') . ' max-width:100%; height:auto;');
            }

            if (count($attributes) > 0) {
                $node['attrs'] = $attributes;
            }

            $children = array_filter(
                array_map(
                    function (Element $element) {
                        return $this->parseElement($element);
                    },
                    $element->children()
                )
            );
            if (count($children) > 0) {
                $node['children'] = $children;
            }

        } else if ($element->isTextNode()) {
            $node['type'] = 'text';
            $node['text'] = $element->text();

        } else {
            return false;
        }

        return $node;
    }

    private function __clone()
    {
    }
}