<?php

require_once 'phar://' . __DIR__ . '/../vendor.phar/MarkdownParse.php';

use TypechoPlugin\MarkdownParse\MarkdownParse;

$markdownParser = MarkdownParse::getInstance();

$markdownParser->setIsTocEnable(true);

echo $markdownParser->parse('Hello World!');
