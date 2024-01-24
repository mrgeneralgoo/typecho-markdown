<?php

require_once __DIR__ . './../MarkdownParse.php';

use TypechoPlugin\MarkdownParse\MarkdownParse;

$markdownParser = MarkdownParse::getInstance();

$markdownParser->setIsTocEnable(true);

echo $markdownParser->parse('Hello World!');
