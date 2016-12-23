<?php declare(strict_types = 1);

namespace Mikulas\PhpParser;

use PhpParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class VisualizeCommand extends Command
{

	const ARG_FILE = 'file';

	protected function configure()
	{
		$this->setName('php-parser:visualize');
		$this->setDescription('Visualize source AST as dot file');
		$this->addArgument(self::ARG_FILE, InputArgument::REQUIRED);
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$file = $input->getArgument(self::ARG_FILE);

		if (!is_readable($file)) {
			throw new \InvalidArgumentException("Cannot read file '$file'.");
		}

		$parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7);
		$dumper = new GraphvizDumper;

		$stmts = $parser->parse(file_get_contents($file));
		$output->write($dumper->dump($stmts));
	}

}
