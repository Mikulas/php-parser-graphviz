<?php

namespace Tests\Mikulas\PhpParser;

use Mikulas\PhpParser\GraphvizDumper;
use PhpParser;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';


class GraphvizDumperTest extends \Tester\TestCase
{

	public function testDump()
	{
		$parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7);
		$dumper = new GraphvizDumper;

		$stmts = $parser->parse(file_get_contents(__DIR__ . '/simple.php'));
		$dot = $dumper->dump($stmts);

		file_put_contents(__DIR__ . '/out.dot', $dot);
		Assert::matchFile(__DIR__ . '/ast.dot', $dot);
	}

}


\Tester\Environment::setup();
(new GraphvizDumperTest)->run();
